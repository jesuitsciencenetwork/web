<?php

namespace AppBundle\Command;

use AppBundle\Entity\AlternateName;
use AppBundle\Entity\Aspect;
use AppBundle\Entity\Person;
use AppBundle\Entity\Subject;
use AppBundle\Helper;
use AppBundle\Pdr\IdProvider;
use AppBundle\Pdr\PdrConnector;
use AppBundle\Viaf\RdfParser;
use AppBundle\Viaf\RdfProviderInterface;
use AppBundle\Viaf\ViafConnector;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

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
//            ->addArgument(
//                'name',
//                InputArgument::OPTIONAL,
//                'Who do you want to greet?'
//            )
//            ->addOption(
//                'yell',
//                null,
//                InputOption::VALUE_NONE,
//                'If set, the task will yell in uppercase letters'
//            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mapper = new NamespacedAttributeBag();

        $subjectsToImport = array();
        $personsToImport = array();
        $sourcesToImport = array();
        $personRefsToImport = array();

        foreach ($this->idProvider->getIds() as $id) {
            $data = $this->connector->processIdi($id);
            $data = $this->mergeViafNames($data);
            print_r($data);
            $personsToImport[] = $data;
            $output->writeln('===================================================');

        }

        foreach ($subjectsToImport as $subjectData) {
            $subject = new Subject();
            $subject->setTitle($subjectData['title']);
            $this->em->persist($subject);
            $this->em->flush();

            $mapper->set('subject/'.$subjectData['title'], $subject->getId());
        }

        foreach ($personsToImport as $personData) {
            $person = new Person();
            $person->setId(Helper::pdr2num($personData['pdrId']));
            $person->setFirstName($personData['firstName']);
            $person->setLastName($personData['lastName']);
            $person->setViafId($personData['viaf']);
            $person->setDateOfBirth($personData['beginningOfLife']);
            $person->setDateOfDeath($personData['endOfLife']);
            $this->em->persist($person);

            foreach ($personData['alternateNames'] as $alternateName) {
                $name = new AlternateName();
                $name->setPerson($person);
                $name->setDisplayName($alternateName);
                $person->addAlternateName($name);
                $this->em->persist($name);
            }

            foreach ($personData['aspects'] as $aspectData) {
                $aspect = new Aspect();
                $aspect->setId(Helper::pdr2num($aspectData['aoId']));
                $aspect->setPerson($person);
                $aspect->setType($aspectData['type']);
                $aspect->setDescription($aspectData['description']);
                $aspect->setPlaceName($aspectData['placeName']);
                $aspect->setLatitude($aspectData['lat']);
                $aspect->setLongitude($aspectData['lng']);
                $person->addAspect($aspect);
                $this->em->persist($aspect);
            }
        }
        $this->em->flush();
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
