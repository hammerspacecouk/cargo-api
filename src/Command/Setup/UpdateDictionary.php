<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Dictionary;
use App\Data\Database\EntityManager;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class UpdateDictionary extends AbstractCommand
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

    protected function configure(): void
    {
        $this
            ->setName('game:init:update-dictionary')
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
    ): int {
        $output->writeln('Fetching source data');

        $filePath = $this->getStringArgument($input, 'inputList');
        $context = $this->getStringArgument($input, 'context');

        if (!\array_key_exists($context, self::CONTEXT_MAP)) {
            throw new InvalidArgumentException('Not a valid context');
        }
        $inputData = csvToArray($filePath);

        $context = self::CONTEXT_MAP[$context];

        $output->writeln('Preparing to update dictionary');

        $total = \count($inputData);

        $progress = new ProgressBar($output, $total);
        $progress->start();

        foreach ($inputData as $row) {
            $word = $row['word'];

            if (!$this->entityManager->getDictionaryRepo()->wordExistsInContext($word, $context)) {
                $dictionaryWord = new Dictionary(
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

        return 0;
    }
}
