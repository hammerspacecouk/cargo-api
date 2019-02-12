<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\CrateType;
use App\Data\Database\EntityManager;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function App\Functions\Transforms\csvToArray;

class MakeCrateTypesCommand extends AbstractCommand
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:init:make-crate-types')
            ->setDescription('One off command for crate types table')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of source data (.txt)'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Fetching source data');

        $filePath = $this->getStringArgument($input, 'inputList');

        if (!\file_exists($filePath)) {
            throw new InvalidArgumentException('Not a valid file path');
        }

        $output->writeln('Deleting previous crate types');
        $this->entityManager->createQueryBuilder()->delete(CrateType::class, 'c')->getQuery()->execute();

        $output->writeln('Making new crate types');

        $inputData = csvToArray($filePath);
        $total = \count($inputData);

        $progress = new ProgressBar($output, $total);
        $progress->start();

        foreach ($inputData as $row) {
            $crateType = new CrateType(
                $row['contents'],
                (int)$row['abundance'],
                (int)$row['value'],
                (bool)$row['isGoal']
            );
            $this->entityManager->persist($crateType);

            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');
    }
}
