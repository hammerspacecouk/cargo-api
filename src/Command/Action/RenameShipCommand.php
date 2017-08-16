<?php
namespace App\Command\Action;

use App\Data\TokenHandler;
use App\Domain\ValueObject\Token\ShipNameToken;
use App\Service\ActionsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RenameShipCommand extends Command
{
    private $actionsService;
    private $tokenHandler;

    public function __construct(
        ActionsService $actionsService,
        TokenHandler $tokenHandler
    ) {
        parent::__construct();
        $this->actionsService = $actionsService;
        $this->tokenHandler = $tokenHandler;
    }

    protected function configure()
    {
        $this
            ->setName('game:action:rename-ship')
            ->setDescription('Rename a ship')
            ->addArgument(
                'actionToken',
                InputArgument::REQUIRED,
                'Action token'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $token = $this->tokensService->parseTokenFromString($input->getArgument('actionToken'));
        $tokenDetail = new ShipNameToken($token);

        $shipId = $tokenDetail->getShipId();
        $name = $tokenDetail->getShipName();
        $output->writeln(
            sprintf(
                'Attempting to name ship %s to %s',
                $shipId->toString(),
                $name
            )
        );

        $this->shipsService->renameShip($shipId, $name);

        $output->writeln('Ship was named ' . $name);

        $output->writeln('Done');
    }
}
