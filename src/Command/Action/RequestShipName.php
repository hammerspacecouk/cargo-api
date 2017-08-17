<?php
declare(strict_types = 1);
namespace App\Command\Action;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequestShipName extends Command
{
    use UserTokenTrait;

    private $shipsService;
    private $tokensService;
    private $logger;

    public function __construct(
        ShipsService $shipsService,
        TokensService $tokensService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->shipsService = $shipsService;
        $this->tokensService = $tokensService;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setName('play:action:request-ship-name')
            ->setDescription('Request a new name for a ship. Will cost you')
            ->addArgument(
                'userToken',
                InputArgument::REQUIRED,
                'User identifying token (who will be charged)'
            )
            ->addArgument(
                'shipID',
                InputArgument::REQUIRED,
                'Ship this name is intended for'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->logger->info(__CLASS__);

        $accessToken = $input->getArgument('userToken');
        $this->logger->debug('Input Token: ' . (string) $accessToken);

        $userId = $this->tokensService->getUserIdFromAccessTokenString($accessToken);
        $shipId = Uuid::fromString($input->getArgument('shipID'));
        $this->logger->info('User ID: ' . (string) $userId);
        $this->logger->info('Ship ID: ' . (string) $shipId);

        $this->logger->info('Requesting name');
        $name = $this->shipsService->requestShipName($userId, $shipId);
        $token = $this->tokensService->getRenameShipToken($shipId, $name);

        $output->writeln('Name Offered: ' . $name);
        $output->writeln('Action token: ');
        $output->writeln((string) $token);
    }
}
