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
                10
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $count = (int)$input->getArgument('number');
        while ($count > 0) {
            $output->writeln($this->uuidFactory->uuid4()->toString());
            $count--;
        }
        return 0;
    }
}
