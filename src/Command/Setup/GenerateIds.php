<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Data\ID;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateIds extends Command
{
    protected function configure()
    {
        $this
            ->setName('game:setup:make-ids')
            ->setDescription('Generates a list of IDs for the Entity required and stores in a file')
            ->addArgument(
                'entityName',
                InputArgument::REQUIRED,
                'The entity class name to generate IDs for'
            )
            ->addOption(
                'total',
                't',
                InputArgument::OPTIONAL,
                'Total to generate',
                1000
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $class = $input->getArgument('entityName');
        $namespace = 'App\Data\Database\Entity';
        $class = $namespace . '\\' . $class;

        $number = (int)$input->getOption('total');

        for ($i = 0; $i < $number; $i++) {
            $id = ID::makeNewID($class);
            $output->writeln((string)$id);
        }
    }
}
