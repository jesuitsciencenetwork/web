<?php

namespace AppBundle\Command;

use AppBundle\Helper;
use AppBundle\Pdr\IdProvider;
use AppBundle\Pdr\PdrConnector;
use AppBundle\Viaf\ViafConnector;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDataCommand extends Command
{
    private $connector;
    private $viaf;
    private $idProvider;
    private $em;

    public function __construct(EntityManager $em, PdrConnector $connector, ViafConnector $viafConnector, IdProvider $idProvider)
    {
        parent::__construct();
        $this->em = $em;
        $this->connector = $connector;
        $this->viaf = $viafConnector;
        $this->idProvider = $idProvider;
    }

    protected function configure()
    {
        $this
            ->setName('jsn:import')
            ->setDescription('Import data from PDR')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tablesToTruncate = array(
            'alternate_name',
            'aspect_subject',
            'aspect',
            'person_subject',
            'subject',
            'person',
        );

        $connection = $this->em->getConnection();
        $connection->setAutoCommit(true);
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tablesToTruncate as $tableName) {
            $q = $dbPlatform->getTruncateTableSql($tableName);
            $connection->executeUpdate($q);
        }
        $connection->query('SET FOREIGN_KEY_CHECKS=1');

        $connection = $connection->getWrappedConnection();

        $subjectStatement = $connection->prepare(
            'INSERT INTO subject (title, slug) VALUES (:title, :slug)'
        );
        $refStatement = $connection->prepare(
            'INSERT INTO relations (person_id, other_person_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE person_id=person_id'
        );
        $personStatement = $connection->prepare(
            'INSERT INTO person (id, first_name, name_link, last_name, title, is_jesuit, viaf_id, date_of_birth, date_of_death) VALUES (:id, :firstName, :nameLink, :lastName, :title, :isJesuit, :viafId, :dateOfBirth, :dateOfDeath)'
        );
        $nameStatement = $connection->prepare(
            'INSERT INTO alternate_name (person_id, display_name) VALUES (:personId, :displayName)'
        );
        $personSubjectStatement = $connection->prepare(
            'INSERT INTO person_subject (person_id, subject_id) VALUES (:personId, :subjectId)'
        );
        $aspectStatement = $connection->prepare(
            'INSERT INTO aspect (id, person_id, type, date_exact, date_from, date_to, place_name, latitude, longitude, description) VALUES (:id, :personId, :type, :dateExact, :dateFrom, :dateTo, :placeName, :latitude, :longitude, :description) ON DUPLICATE KEY UPDATE id=id'
        );
        $aspectSubjectStatement = $connection->prepare(
            'INSERT INTO aspect_subject (aspect_id, subject_id) VALUES (:aspectId, :subjectId) ON DUPLICATE KEY UPDATE aspect_id=aspect_id'
        );

        $subjectsToImport = array();
        $subjectMap = array();
        $personsToImport = array();
        $sourcesToImport = array();
        $personRefsToImport = array();

        $output->writeln('Processing IDI and VIAF data...');

        $ids = $this->idProvider->getIds();
        $progress = new ProgressBar($output, count($ids));
        $progress->display();
        foreach ($ids as $id) {
            $data = $this->connector->processIdi($id);
            $data = $this->mergeViafNames($data);
            $personsToImport[] = $data;
            $subjectsToImport = array_merge($subjectsToImport, $data['subjects']);
            $personRefsToImport = array_merge($personRefsToImport, $data['personRefs']);
            $progress->advance();
        }

        gc_collect_cycles();

        $output->writeln("");
        $output->writeln("Now filling database...");

        foreach ($subjectsToImport as $slug => $subjectTitle) {
            if (array_key_exists($slug, $subjectMap)) {
                continue;
            }
            $subjectStatement->execute(array(
                ':title' => ucfirst($subjectTitle),
                ':slug' => $slug
            ));
            $subjectId = $connection->lastInsertId();
            $subjectMap[$slug] = $subjectId;
        }

        $progress = new ProgressBar($output, count($personsToImport));
        $progress->display();
        foreach ($personsToImport as $personData) {
            $po = Helper::pdr2num($personData['pdrId']);
            $personStatement->execute(array(
                ':id' => $po,
                ':firstName' => $personData['firstName'],
                ':nameLink' => $personData['nameLink'],
                ':lastName' => $personData['lastName'],
                ':title' => $personData['title'],
                ':viafId' => $personData['viaf'],
                ':dateOfBirth' => $personData['beginningOfLife'],
                ':dateOfDeath' => $personData['endOfLife'],
                ':isJesuit' => $personData['nonjesuit'] ? false : true
            ));

            foreach ($personData['alternateNames'] as $alternateName) {
                $nameStatement->execute(
                    array(
                        ':personId' => $po,
                        ':displayName' => $alternateName
                    )
                );
            }

            $subjectsAdded = array();
            foreach ($personData['subjects'] as $subject) {
                $slug = Helper::slugify($subject);
                if (array_key_exists($slug, $subjectsAdded)) {
                    continue;
                }
                $subjectId = $subjectMap[$slug];

                $personSubjectStatement->execute(array(
                    ':personId' => $po,
                    ':subjectId' => $subjectId
                ));
                $subjectsAdded[$slug] = true;
            }

            foreach ($personData['aspects'] as $aspectData) {
                $ao = Helper::pdr2num($aspectData['aoId']);

                $aspectStatement->execute(array(
                    ':id' => $ao,
                    ':personId' => $po,
                    ':type' => $aspectData['type'],
                    ':placeName' => $aspectData['placeName'],
                    ':latitude' => $aspectData['lat'],
                    ':longitude' => $aspectData['lng'],
                    ':dateExact' => $aspectData['dateExact'],
                    ':dateFrom' => $aspectData['dateFrom'],
                    ':dateTo' => $aspectData['dateTo'],
                    ':description' => $aspectData['description'],
                ));

                $subjectsAdded = array();
                foreach ($aspectData['subjects'] as $subject) {
                    $slug = Helper::slugify($subject);
                    if (array_key_exists($slug, $subjectsAdded)) {
                        continue;
                    }
                    $subjectId = $subjectMap[$slug];

                    $aspectSubjectStatement->execute(array(
                        ':aspectId' => $ao,
                        ':subjectId' => $subjectId
                    ));
                }
            }

            $progress->advance();
        }

        // insert bidirectional reference
        $output->writeln('Writing relations...');
        $progress = new ProgressBar($output, count($personRefsToImport));
        $progress->display();
        foreach ($personRefsToImport as $ref) {
            if (!$ref[0] || !$ref[1]) {
                continue;
            }
            try {
                $refStatement->execute(array($ref[0], $ref[1]));
                $refStatement->execute(array($ref[1], $ref[0]));
            } catch (\PDOException $e) {
                $output->writeln('failed to insert relation: '. $ref[0] . ' <-> '.$ref[1]);
            }
            $progress->advance();
        }
    }

    protected function mergeViafNames($data)
    {
        if (!array_key_exists('viaf', $data)) {
            return $data;
        }

        $viafNames = $this->viaf->getAlternateNames($data['viaf']);

        $alternateNames = array_merge($data['alternateNames'], $viafNames);

        $data['alternateNames'] = array_unique($alternateNames);

        return $data;
    }
}
