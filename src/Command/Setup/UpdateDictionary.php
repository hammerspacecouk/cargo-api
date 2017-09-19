<?php declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\ParseCSVTrait;
use App\Data\Database\Entity\Dictionary;
use App\Data\Database\EntityManager;
use App\Data\ID;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDictionary extends Command
{
    use ParseCSVTrait;

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
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Fetching source data');

        $filePath = $input->getArgument('inputList');
        $context = $input->getArgument('context');

        if (!array_key_exists($context, self::CONTEXT_MAP)) {
            throw new InvalidArgumentException('Not a valid context');
        }
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Not a valid file path');
        }

        $context = self::CONTEXT_MAP[$context];

        $inputData = $this->csvToArray($filePath);

        $output->writeln('Preparing to update dictionary');

        $total = count($inputData);

        $progress = new ProgressBar($output, $total);
        $progress->start();

        foreach ($inputData as $row) {
            $word = $row['word'];

            if (!$this->entityManager->getDictionaryRepo()->wordExistsInContext($word, $context)) {
                $dictionaryWord = new Dictionary(
                    ID::makeNewID(Dictionary::class),
                    $word,
                    $context
                );
                $this->entityManager->persist($dictionaryWord);
            }

            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');
    }
}
