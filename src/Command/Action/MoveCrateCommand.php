<?php declare(strict_types=1);

namespace App\Command\Action;

use App\Service\CratesService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MoveCrateCommand extends Command
{
    private $cratesService;
    private $logger;

    public function __construct(
        CratesService $cratesService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->cratesService = $cratesService;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('play:action:move-crate')
            ->setDescription('Move a crate into a port or ship')
            ->addArgument(
                'crateId',
                InputArgument::REQUIRED,
                'The crate ID to move'
            )
            ->addArgument(
                'destinationID',
                InputArgument::REQUIRED,
                'The ID of the destination'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        die('out of date. rework');
        $this->logger->debug(__CLASS__);
        $crateId = Uuid::fromString($input->getArgument('crateId'));
        $destinationId = Uuid::fromString($input->getArgument('destinationID'));

        $this->logger->info('Will be moving crate ' . $crateId . ' to ' . $destinationId);

        $this->cratesService->moveCrateToLocation($crateId, $destinationId);

        $this->logger->info('Done');
    }
}
