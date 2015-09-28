<?php

namespace AppBundle\Command;

use AppBundle\Viaf\RdfParser;
use AppBundle\Viaf\RdfProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDataCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
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

    }
}
