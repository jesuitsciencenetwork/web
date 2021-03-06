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
    private $mathNatMap;

    public function __construct(EntityManager $em, PdrConnector $connector, ViafConnector $viafConnector, IdProvider $idProvider, Geocoder $geocoder, $groupFile, $mathNatFile)
    {
        parent::__construct();
        $this->em = $em;
        $this->connector = $connector;
        $this->viaf = $viafConnector;
        $this->idProvider = $idProvider;
        $this->geocoder = $geocoder;

        $this->subjectGroupDefinitions = Yaml::parse(file_get_contents($groupFile));
        $this->mathNatMap = Yaml::parse(file_get_contents($mathNatFile));

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

        $tablesToTruncate = [
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
            'source_group',
            'subject',
            'subject_group',
            'subject_group_subject',
        ];

        $connection = $this->em->getConnection();
        $connection->setAutoCommit(true);
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tablesToTruncate as $tableName) {
            $q = $dbPlatform->getTruncateTableSQL($tableName);
            $connection->executeUpdate($q);
        }
        $connection->query('SET FOREIGN_KEY_CHECKS=1');

        $connection = $connection->getWrappedConnection();

        $subjectStatement = $connection->prepare(
            'INSERT INTO subject (title, slug, is_mathnat) VALUES (:title, :slug, :mathnat)'
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
            'INSERT INTO person (id, display_name, list_name, short_name, name_for_sorting, group_letter, is_jesuit, viaf_id, lastmod, date_of_birth, date_of_death) VALUES (:id, :displayName, :listName, :shortName, :nameForSorting, :groupLetter, :isJesuit, :viafId, :lastmod, :dateOfBirth, :dateOfDeath)'
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
            'INSERT INTO aspect (id, person_id, type, date_exact, date_from, date_to, comment, source_id, occupation, occupation_slug, affiliation, raw_xml, description) VALUES (:id, :personId, :type, :dateExact, :dateFrom, :dateTo, :comment, :sourceId, :occupation, :occupationSlug, :affiliation, :rawXml, :description) ON DUPLICATE KEY UPDATE id=id'
        );
        $aspectSubjectStatement = $connection->prepare(
            'INSERT INTO aspect_subject (aspect_id, subject_id) VALUES (:aspectId, :subjectId) ON DUPLICATE KEY UPDATE aspect_id=aspect_id'
        );
        $sourceStatement = $connection->prepare(
            'INSERT INTO source (id, genre, title, series_title, authors, publisher, place, date_issued, date_captured, url, note, editors, payload, source_group_id) VALUES (:id, :genre, :title, :seriesTitle, :authors, :publisher, :place, :dateIssued, :dateCaptured, :url, :note, :editors, :payload, :group) ON DUPLICATE KEY UPDATE id=id'
        );
        $sourceGroupStatement = $connection->prepare(
            'INSERT INTO source_group (title, slug, full_cite, color) VALUES (:title, :slug, :cite, :color)'
        );
        $groupStatement = $connection->prepare(
            'INSERT INTO subject_group (title, slug) VALUES (:title, :slug) ON DUPLICATE KEY UPDATE id=id'
        );
        $subjectGroupRefStatement = $connection->prepare(
            'INSERT INTO subject_group_subject (subject_id, subject_group_id) VALUES (:subjectId, :groupId) ON DUPLICATE KEY UPDATE subject_id=subject_id'
        );

        $subjectsToImport = [];
        $subjectMap = [];

        $placesToImport = [];
        $placeMap = [];

        $personsToImport = [];

        $sourcesToImport = [];

        $personRefsToImport = [];

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Processing IDI and VIAF data...');
        $masterProgress->display();

        $ids = $this->idProvider->getIds();
        $viafs = [];

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

            $title = ucfirst($subjectTitle);

            if (!array_key_exists($title, $this->mathNatMap)) {
                throw new \Exception(sprintf(
                    'Subject "%s" not listed in MathNat mapping.',
                    $title
                ));
            }

            $subjectStatement->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':mathnat' => (int)$this->mathNatMap[$title]
            ]);
            $subjectId = $connection->lastInsertId();
            $subjectMap[$slug] = $subjectId;
        }

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing subject groups...');
        $masterProgress->display();

        foreach ($this->subjectGroupDefinitions as $subjectGroup) {
            $groupStatement->execute([
                ':title' => $subjectGroup['name'],
                ':slug' => Helper::slugify($subjectGroup['name'])
            ]);
            $groupId = $connection->lastInsertId();

            foreach ($subjectGroup['subjects'] as $subject) {
                $subjectSlug = Helper::slugify($subject);
                if (!array_key_exists($subjectSlug, $subjectMap)) {
                    throw new \Exception("Subject $subject for group {$subjectGroup["name"]} was not found in map.");
                }
                $subjectId = $subjectMap[$subjectSlug];

                $subjectGroupRefStatement->execute(
                    [
                    ':groupId' => $groupId,
                    ':subjectId' => $subjectId
                    ]
                );
            }
        }

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing source groups...');
        $masterProgress->display();

        $sourceGroupMap = [];
        $sourceGroupsToImport = [
            'viaf' => ['VIAF/GND', 'VIAF/GND', ''],
            'sommervogel' => ['Bibliothèque de la Compagnie de Jésus', 'Sommervogel, Carlos et al., eds. (Reprint 1960). Bibliothèque de la Compagnie de Jésus. Vol. I-XII. Louvain: Éditions de la Bibliothèque S.J. : Collège Philosophique et Théologique.', ''],
            'dhcj' => ['Diccionario Histórico de la Compañía de Jesús', 'O\'Neill, Charles et al., eds. (2011). Diccionario Histórico de la Compañía de Jesús. Vol. I-IV. Madrid: Universidad Pontifica Comillas.', ''],
            'wp' => ['Wikipedia', 'Wikipedia', '']
        ];
        foreach ($sourceGroupsToImport as $shorthand => $groupData) {
            $sourceGroupStatement->execute(
                [
                    ':title' => $groupData[0],
                    ':cite' => $groupData[1],
                    ':color' => $groupData[2],
                    ':slug'  => $shorthand
                ]
            );
            $sourceGroupMap[$shorthand] = $connection->lastInsertId();
        }

        $masterProgress->advance();
        $masterProgress->clear();
        $masterProgress->setMessage('Writing sources...');
        $masterProgress->display();

        foreach ($sourcesToImport as $sourceId => $sourceData) {
            $sourceStatement->execute(
                [
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
                ':payload' => $sourceData['payload'],
                ':group' => $sourceData['group'] ? $sourceGroupMap[$sourceData['group']] : null,
                ]
            );
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

            $placeStatement->execute(
                [
                ':placeName' => $placeName,
                ':slug' => Helper::slugify($placeName),
                ':country' => $pos->getCountry(),
                ':continent' => $pos->getContinent(),
                ':latitude' => $pos->getLatitude(),
                ':longitude' => $pos->getLongitude(),
                ]
            );

            $placeId = $connection->lastInsertId();
            $placeMap[$placeName] = [$placeId, $pos];
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
            $personStatement->bindValue(':isJesuit', $personData['nonjesuit'] ? false : true, \PDO::PARAM_BOOL);
            $personStatement->bindValue(':id', $po, \PDO::PARAM_INT);
            $personStatement->bindValue(':displayName', $personData['displayName']);
            $personStatement->bindValue(':listName', $personData['listName']);
            $personStatement->bindValue(':shortName', $personData['shortName']);
            $personStatement->bindValue(':nameForSorting', $personData['nameForSorting']);
            $personStatement->bindValue(':groupLetter', $personData['groupLetter']);
            $personStatement->bindValue(':viafId', $personData['viaf'] ?: null);
            $personStatement->bindValue(':lastmod', $personData['lastmod']);
            $personStatement->bindValue(':dateOfBirth', $personData['beginningOfLife'] ?: null);
            $personStatement->bindValue(':dateOfDeath', $personData['endOfLife'] ?: null);

            $personStatement->execute();

            if ($personData['viaf']) {
                $viafs[] = $personData['viaf'];
            }

            foreach ($personData['alternateNames'] as $alternateName) {
                $nameStatement->execute(
                    [
                        ':personId' => $po,
                        ':displayName' => $alternateName
                    ]
                );
            }

            $subjectsAdded = [];
            foreach ($personData['subjects'] as $subject) {
                $slug = Helper::slugify($subject);
                if (array_key_exists($slug, $subjectsAdded)) {
                    continue;
                }
                $subjectId = $subjectMap[$slug];

                $personSubjectStatement->execute(
                    [
                    ':personId' => $po,
                    ':subjectId' => $subjectId
                    ]
                );
                $subjectsAdded[$slug] = true;
            }

            $placesAdded = [];
            foreach ($personData['places'] as $placeName) {
                if (array_key_exists($placeName, $placesAdded)) {
                    continue;
                }
                $placeId = $placeMap[$placeName][0];

                $personPlaceStatement->execute(
                    [
                    ':personId' => $po,
                    ':placeId' => $placeId
                    ]
                );
                $placesAdded[$placeName] = true;
            }

            $personSources = [];
            foreach ($personData['aspects'] as $aspectData) {
                $ao = Helper::pdr2num($aspectData['aoId']);
                $sourceId = Helper::pdr2num($aspectData['source']);

                $personSources[] = $sourceId;

                // resolve maps and subject links
                $aspectData['description'] = preg_replace_callback('/\{(S|M):(.+?)\|(.+?)\}/', function($matches) use ($placeMap, $subjectMap) {
                    if ('S' === $matches[1]) {
                        $id = $subjectMap[$matches[2]];
                        return '{S:'.$id.'|'.$matches[3].'}';
                    } else {
                        $pos = $placeMap[$matches[2]][1];
                        return '{M:'.$pos->getLatitude().','.$pos->getLongitude().'|'.$matches[3].'}';
                    }
                }, $aspectData['description']);

                $aspectStatement->execute([
                    ':id' => $ao,
                    ':personId' => $po,
                    ':type' => $aspectData['type'],
                    ':dateExact' => $aspectData['dateExact'],
                    ':dateFrom' => $aspectData['dateFrom'],
                    ':dateTo' => $aspectData['dateTo'],
                    ':comment' => implode(", ", $aspectData['comments']),
                    ':sourceId' => $sourceId,
                    ':occupation' => $aspectData['occupation'],
                    ':affiliation' => $aspectData['affiliation'],
                    ':occupationSlug' => Helper::slugify($aspectData['occupation']),
                    ':rawXml' => $aspectData['raw'],
                    ':description' => $aspectData['description'],
                ]);

                foreach ($aspectData['subjects'] as $subject) {
                    $slug = Helper::slugify($subject);
                    $subjectId = $subjectMap[$slug];

                    $aspectSubjectStatement->execute(
                        [
                        ':aspectId' => $ao,
                        ':subjectId' => $subjectId
                        ]
                    );
                }

                foreach (array_unique($aspectData['places']) as $placeName) {
                    $placeId = $placeMap[$placeName][0];

                    $aspectPlaceStatement->execute(
                        [
                        ':aspectId' => $ao,
                        ':placeId' => $placeId
                        ]
                    );
                }
            }

            foreach ($personSources as $sourceId) {
                $personSourceStatement->execute(
                    [
                    ':personId' => $po,
                    ':sourceId' => $sourceId
                    ]
                );
            }

            $progress->advance();
        }

        // update person's mathnat shorthand
        $connection->exec('UPDATE person SET is_math_nat = 0');
        $connection->exec('UPDATE person SET is_math_nat = 1 WHERE id IN (SELECT DISTINCT ps.person_id FROM person_subject ps INNER JOIN subject s ON ps.subject_id = s.id AND s.is_mathnat = 1)');

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

        $beacon = fopen(__DIR__ . '/../../../html/jsn-viaf.beacon', 'w');

        fwrite($beacon, '#FORMAT: BEACON
#PREFIX: http://viaf.org/viaf/
#TARGET: http://jesuitscience.net/viaf/{id}
#HOMEPAGE: http://jesuitscience.net/
#CONTACT: Dagmar Mrozik <dagmar.mrozik@jesuitscience.net>
#FEED: http://jesuitscience.net/jsn-viaf.beacon
');
        fwrite($beacon, "#TIMESTAMP: " . (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTime::ISO8601) . "\n\n");

        sort($viafs);
        foreach ($viafs as $viaf) {
            fwrite($beacon, "$viaf\n");
        }

        fclose($beacon);
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
