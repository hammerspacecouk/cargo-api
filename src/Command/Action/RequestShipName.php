<?php
namespace App\Command\Action;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ShipsService;
use App\Service\TokensService;
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

    public function __construct(
        ShipsService $shipsService,
        TokensService $tokensService
    ) {
        parent::__construct();
        $this->shipsService = $shipsService;
        $this->tokensService = $tokensService;
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
        $accessToken = $input->getArgument('userToken');

        $userId = $this->tokensService->getUserIdFromAccessTokenString($accessToken);
        $shipId = Uuid::fromString($input->getArgument('shipID'));

        $name = $this->shipsService->requestShipName($userId, $shipId);
        $token = $this->tokensService->getRenameShipToken($shipId, $name);

        $output->writeln('Name Offered: ' . $name);
        $output->writeln('Action token: ');
        $output->writeln((string) $token);
    }
}
