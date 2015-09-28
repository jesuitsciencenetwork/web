<?php

namespace AppBundle\Command;

use AppBundle\Viaf\RdfParser;
use AppBundle\Viaf\RdfProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchViafNamesCommand extends Command
{
    private $rdfProvider;

    public function __construct(RdfProviderInterface $rdfProvider)
    {
        parent::__construct();
        $this->rdfProvider = $rdfProvider;
    }

    protected function configure()
    {
        $this
            ->setName('jsn:viaf:fetch-names')
            ->setDescription('Fetch alternate names from viaf database')
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
        $ids = $this->idProvider->getIds();
        foreach ($ids as $id) {

        }
        $rdf = $this->rdfProvider->getRdf(8962210);
        $parser = new RdfParser($rdf);
        $names = $parser->getAlternateNames();

    }
}
