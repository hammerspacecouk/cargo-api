<?php
declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Command\AbstractCommand;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeUUIDsCommand extends AbstractCommand
{
    private $uuidFactory;

    public function __construct(
        UuidFactoryInterface $uuidFactory
    ) {
        parent::__construct();
        $this->uuidFactory = $uuidFactory;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:maintenance:uuids')
            ->setDescription('Generate lots of UUIDs')
            ->addArgument(
                'number',
                InputArgument::OPTIONAL,
                'How many to generate',
                '10'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $count = $input->getArgument('number');
        if (is_array($count)) {
            throw new \InvalidArgumentException('Arrays not allowed');
        }
        $count = (int)$count;
        $a = [];
        while ($count > 0) {
            $a[] = $this->uuidFactory->uuid6()->toString();
            $count--;
        }
        foreach ($a as $b) {
            $output->writeln($b);
        }
        return 0;
    }
}
