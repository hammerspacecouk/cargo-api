<?php
namespace App\Command\Action;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ActionsService;
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

    private $actionsService;
    private $shipsService;
    private $tokensService;

    public function __construct(
        ActionsService $actionsService,
        ShipsService $shipsService,
        TokensService $tokensService
    ) {
        parent::__construct();
        $this->actionsService = $actionsService;
        $this->shipsService = $shipsService;
        $this->tokensService = $tokensService;
    }

    protected function configure()
    {
        $this
            ->setName('game:action:request-ship-name')
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

        $shipNameToken = $this->actionsService->requestShipName($userId, $shipId);

        $output->writeln('Name Offered: ' . $shipNameToken->getShipName());
        $output->writeln('Action token: ');
        $output->writeln((string) $shipNameToken);
    }
}
