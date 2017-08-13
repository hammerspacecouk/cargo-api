<?php
namespace App\Command\Action;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Data\TokenHandler;
use App\Service\ActionsService;
use App\Service\ShipsService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequestShipName extends Command
{
    use UserTokenTrait;

    private $actionsService;
    private $tokenHandler;
    private $shipsService;

    public function __construct(
        TokenHandler $tokenHandler,
        ActionsService $actionsService,
        ShipsService $shipsService
    ) {
        parent::__construct();
        $this->actionsService = $actionsService;
        $this->tokenHandler = $tokenHandler;
        $this->shipsService = $shipsService;
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
        $userToken = $input->getArgument('userToken');
        $token = $this->tokenHandler->parseTokenFromString($userToken);

        $shipId = Uuid::fromString($input->getArgument('shipID'));

        $nameAction = $this->actionsService->requestShipName($token, $shipId);

        $output->writeln('Name Offered: ' . $nameAction->getName());
        $output->writeln('Action token: ');
        $output->writeln((string) $token);
    }
}
