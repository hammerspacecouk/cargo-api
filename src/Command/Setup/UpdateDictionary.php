<?php
namespace App\Command\Setup;

use App\Data\Database\Entity\Dictionary;
use App\Data\Database\Entity\ShipClass;
use App\Data\ID;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDictionary extends Command
{
    private const CONTEXT_MAP = [
        'shipName1' => Dictionary::CONTEXT_SHIP_NAME_1,
        'shipName2' => Dictionary::CONTEXT_SHIP_NAME_2,
    ];

    private $entityManager;



    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('game:setup:update-dictionary')
            ->setDescription('One off command for populating enum table')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of words to add to the dictionary (.txt)'
            )
            ->addArgument(
                'context',
                InputArgument::REQUIRED,
                'Context to add the words to'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Fetching source file');

        $filePath = $input->getArgument('inputList');
        $context = $input->getArgument('context');

        if (!array_key_exists($context, self::CONTEXT_MAP)) {
            throw new InvalidArgumentException('Not a valid context');
        }
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Not a valid file path');
        }

        $context = self::CONTEXT_MAP[$context];

        $inputData = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $output->writeln('Preparing to update dictionary');

        $total = count($inputData);

        $output->writeln('Removing current entries from context: ' . $context);
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(Dictionary::class, 'd')
            ->where('d.context = :context')
            ->setParameter('context', $context);

        $count = $qb->getQuery()->execute();

        $output->writeln('Deleted ' . $count);

        $progress = new ProgressBar($output, $total);
        $progress->start();

        foreach ($inputData as $word) {
            $shipClass = new Dictionary(
                ID::makeNewID(Dictionary::class),
                $word,
                $context
            );
            $shipClass->uuid = (string) $shipClass->id;
            $this->entityManager->persist($shipClass);

            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');
        $output->writeln('Flush all');
        $this->entityManager->flush();

        $output->writeln('Done');
    }
}
