<?php

namespace AppBundle\Command;

use AppBundle\Geocoder;
use AppBundle\Helper;
use AppBundle\Pdr\IdProvider;
use AppBundle\Pdr\PdrConnector;
use AppBundle\Viaf\ViafConnector;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ImportDataCommand extends Command
{
    private $connector;
    private $viaf;
    private $idProvider;
    private $em;
    private $geocoder;
    private $subjectGroupDefinitions;

    public function __construct(EntityManager $em, PdrConnector $connector, ViafConnector $viafConnector, IdProvider $idProvider, Geocoder $geocoder, $groupFile)
    {
        parent::__construct();
        $this->em = $em;
        $this->connector = $connector;
        $this->viaf = $viafConnector;
        $this->idProvider = $idProvider;
        $this->geocoder = $geocoder;

        $this->subjectGroupDefinitions = Yaml::parse(file_get_contents($groupFile));

        if (!$this->subjectGroupDefinitions) {
            throw new \RuntimeException('Could not read subject group definitions');
        }
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
        $masterProgress = new ProgressBar($output, 7); // @TODO max steps
        $masterProgress->setEmptyBarCharacter('░'); // light shade character \u2591
        $masterProgress->setProgressCharacter('');
        $masterProgress->setBarCharacter('▓'); // dark shade character \u2593
        $masterProgress->setFormat('%bar% %message%');
        $masterProgress->setRedrawFrequency(1);

        $masterProgress->setMessage('Initializing...');
        $masterProgress->display();

        $tablesToTruncate = array(
            'alternate_name',
            'aspect',
            'aspect_place',
            'aspect_subject',
            'person',
            'person_place',
            'person_source',
            'person_subject',
            'place',
            'relations',
            'source',
            'subject',
            'subject_group',
            'subject_group_subject',
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
        $placeStatement = $connection->prepare(
            'INSERT INTO place (place_name, slug, country, continent, latitude, longitude) VALUES (:placeName, :slug, :country, :continent, :latitude, :longitude)'
        );
        $aspectPlaceStatement = $connection->prepare(
            'INSERT INTO aspect_place (aspect_id, place_id) VALUES (:aspectId, :placeId) ON DUPLICATE KEY UPDATE aspect_id=aspect_id'
        );
        $personPlaceStatement = $connection->prepare(
            'INSERT INTO person_place (person_id, place_id) VALUES (:personId, :placeId)'
        );
        $refStatement = $connection->prepare(
            'INSERT INTO relations (source_id, target_id, class, context, `value`, aspect_id) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id'
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
        $personSourceStatement = $connection->prepare(
            'INSERT INTO person_source (person_id, source_id) VALUES (:personId, :sourceId) ON DUPLICATE KEY UPDATE person_id=person_id'
        );
        $aspectStatement = $connection->prepare(
            'INSERT INTO aspect (id, person_id, type, date_exact, date_from, date_to, comment, source_id, occupation, occupation_slug, raw_xml, description) VALUES (:id, :personId, :type, :dateExact, :dateFrom, :dateTo, :comment, :sourceId, :occupation, :occupationSlug, :rawXml, :description) ON DUPLICATE KEY UPDATE id=id'
        );
        $aspectSubjectStatement = $connection->prepare(
            'INSERT INTO aspect_subject (aspect_id, subject_id) VALUES (:aspectId, :subjectId) ON DUPLICATE KEY UPDATE aspect_id=aspect_id'
        );
        $sourceStatement = $connection->prepare(
            'INSERT INTO source (id, genre, title, series_title, authors, publisher, place, date_issued, date_captured, url, note, editors) VALUES (:id, :genre, :title, :seriesTitle, :authors, :publisher, :place, :dateIssued, :dateCaptured, :url, :note, :editors) ON DUPLICATE KEY UPDATE id=id'
        );
        $groupStatement = $connection->prepare(
            'INSERT INTO subject_group (title, slug, scheme) VALUES (:title, :slug, :scheme) ON DUPLICATE KEY UPDATE id=id'
        );
        $subjectGroupRefStatement = $connection->prepare(
            'INSERT INTO subject_group_subject (subject_id, subject_group_id) VALUES (:subjectId, :groupId) ON DUPLICATE KEY UPDATE subject_id=subject_id'
        );

        $subjectsToImport = array();
        $subjectMap = array();

        $placesToImport = array();
        $placeMap = array();

        $personsToImport = array();

        $sourcesToImport = array();

        $personRefsToImport = array();

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Processing IDI and VIAF data...');
        $masterProgress->display();

        $ids = $this->idProvider->getIds();

        $progress = new ProgressBar($output, count($ids));
        $progress->setEmptyBarCharacter('░'); // light shade character \u2591
        $progress->setProgressCharacter('');
        $progress->setBarCharacter('▓'); // dark shade character \u2593
        $progress->setFormat('%bar% %current%/%max%');
        $output->writeln('');
        $progress->display();

        foreach ($ids as $id) {
            $data = $this->connector->processIdi($id);
            $data = $this->mergeViafNames($data);
            $personsToImport[] = $data;
            $subjectsToImport = array_merge($subjectsToImport, $data['subjects']);
            $placesToImport = array_merge($placesToImport, $data['places']);
            $sourcesToImport = array_merge($sourcesToImport, $data['sources']);
            $personRefsToImport = array_merge($personRefsToImport, $data['personRefs']);
            $progress->advance();
        }

        $progress->clear();
        gc_collect_cycles();

        // up one line
        $output->write("\033[1A");
        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing subjects...');
        $masterProgress->display();

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

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing subject groups...');
        $masterProgress->display();

        foreach ($this->subjectGroupDefinitions as $subjectGroup) {
            $groupStatement->execute(array(
                ':title' => $subjectGroup['name'],
                ':scheme' => $subjectGroup['scheme'],
                ':slug' => Helper::slugify($subjectGroup['name'])
            ));
            $groupId = $connection->lastInsertId();

            foreach ($subjectGroup['subjects'] as $subject) {
                $subjectSlug = Helper::slugify($subject);
                if (!array_key_exists($subjectSlug, $subjectMap)) {
                    throw new \Exception("Subject $subject for group {$subjectGroup["name"]} was not found in map.");
                }
                $subjectId = $subjectMap[$subjectSlug];

                $subjectGroupRefStatement->execute(array(
                    ':groupId' => $groupId,
                    ':subjectId' => $subjectId
                ));
            }
        }

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing sources...');
        $masterProgress->display();

        foreach ($sourcesToImport as $sourceId => $sourceData) {
            $sourceStatement->execute(array(
                ':id' => Helper::pdr2num($sourceId),
                ':genre' => $sourceData['genre'],
                ':title' => $sourceData['title'],
                ':seriesTitle' => $sourceData['seriesTitle'],
                ':authors' => json_encode($sourceData['authors']),
                ':publisher' => $sourceData['publisher'],
                ':place' => $sourceData['place'],
                ':dateIssued' => $sourceData['dateIssued'],
                ':dateCaptured' => $sourceData['dateCaptured'],
                ':url' => $sourceData['url'],
                ':note' => $sourceData['note'],
                ':editors' => json_encode($sourceData['editors']),
            ));
        }

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing places...');
        $masterProgress->display();

        foreach ($placesToImport as $placeName) {
            if (array_key_exists($placeName, $placeMap)) {
                continue;
            }

            $pos = $this->geocoder->geocode($placeName);

            $placeStatement->execute(array(
                ':placeName' => $placeName,
                ':slug' => Helper::slugify($placeName),
                ':country' => $pos->getCountry(),
                ':continent' => $pos->getContinent(),
                ':latitude' => $pos->getLatitude(),
                ':longitude' => $pos->getLongitude(),
            ));

            $placeId = $connection->lastInsertId();
            $placeMap[$placeName] = $placeId;
        }

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing persons...');
        $masterProgress->display();

        $progress = new ProgressBar($output, count($personsToImport));
        $progress->setEmptyBarCharacter('░'); // light shade character \u2591
        $progress->setProgressCharacter('');
        $progress->setBarCharacter('▓'); // dark shade character \u2593
        $progress->setFormat('%bar% %current%/%max%');
        $output->writeln('');
        $progress->display();

        foreach ($personsToImport as $personData) {
            $po = Helper::pdr2num($personData['pdrId']);
            $personStatement->execute(array(
                ':id' => $po,
                ':firstName' => $personData['firstName'] ?: null ,
                ':nameLink' => $personData['nameLink'] ?: null,
                ':lastName' => $personData['lastName'] ?: null,
                ':title' => $personData['title'] ?: null,
                ':viafId' => $personData['viaf'] ?: null,
                ':dateOfBirth' => $personData['beginningOfLife'] ?: null,
                ':dateOfDeath' => $personData['endOfLife'] ?: null,
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

            $placesAdded = array();
            foreach ($personData['places'] as $placeName) {
                if (array_key_exists($placeName, $placesAdded)) {
                    continue;
                }
                $placeId = $placeMap[$placeName];

                $personPlaceStatement->execute(array(
                    ':personId' => $po,
                    ':placeId' => $placeId
                ));
                $placesAdded[$placeName] = true;
            }

            $personSources = array();
            foreach ($personData['aspects'] as $aspectData) {
                $ao = Helper::pdr2num($aspectData['aoId']);
                $sourceId = Helper::pdr2num($aspectData['source']);

                $personSources[] = $sourceId;

                $aspectStatement->execute(array(
                    ':id' => $ao,
                    ':personId' => $po,
                    ':type' => $aspectData['type'],
                    ':dateExact' => $aspectData['dateExact'],
                    ':dateFrom' => $aspectData['dateFrom'],
                    ':dateTo' => $aspectData['dateTo'],
                    ':comment' => implode(", ", $aspectData['comments']),
                    ':sourceId' => $sourceId,
                    ':occupation' => $aspectData['occupation'],
                    ':occupationSlug' => Helper::slugify($aspectData['occupation']),
                    ':rawXml' => $aspectData['raw'],
                    ':description' => $aspectData['description'],
                ));

                foreach ($aspectData['subjects'] as $subject) {
                    $slug = Helper::slugify($subject);
                    $subjectId = $subjectMap[$slug];

                    $aspectSubjectStatement->execute(array(
                        ':aspectId' => $ao,
                        ':subjectId' => $subjectId
                    ));
                }

                foreach (array_unique($aspectData['places']) as $placeName) {
                    $placeId = $placeMap[$placeName];

                    $aspectPlaceStatement->execute(array(
                        ':aspectId' => $ao,
                        ':placeId' => $placeId
                    ));
                }
            }

            foreach ($personSources as $sourceId) {
                $personSourceStatement->execute(array(
                    ':personId' => $po,
                    ':sourceId' => $sourceId
                ));
            }

            $progress->advance();
        }

        $progress->clear();
        // up one line
        $output->write("\033[1A");
        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing relations...');
        $masterProgress->display();

        $progress = new ProgressBar($output, count($personRefsToImport));
        $progress->setEmptyBarCharacter('░'); // light shade character \u2591
        $progress->setProgressCharacter('');
        $progress->setBarCharacter('▓'); // dark shade character \u2593
        $progress->setFormat('%bar% %current%/%max%');
        $output->writeln('');
        $progress->display();

        foreach ($personRefsToImport as $ref) {
            if (!$ref[0] || !$ref[1]) {
                continue;
            }
            try {
                $refStatement->execute($ref);
            } catch (\PDOException $e) {
                $output->writeln('failed to insert relation: '. $ref[0] . ' -> '.$ref[1]);
            }
            $progress->advance();
        }

        $progress->clear();
        // up one line
        $output->write("\033[1A");
        $masterProgress->advance();
        $output->writeln('');
        $output->writeln('Imported <info>' . count($personsToImport) . '</info> records.');
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
