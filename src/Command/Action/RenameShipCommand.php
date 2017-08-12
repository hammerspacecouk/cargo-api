<?php
namespace App\Command\Action;

use App\Config\TokenConfig;
use App\Domain\ValueObject\Token\ShipNameToken;
use App\Service\ActionsService;
use App\Service\TokensService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RenameShipCommand extends Command
{
    private $actionsService;
    private $tokenConfig;
    private $tokensService;

    public function __construct(
        ActionsService $actionsService,
        TokensService $tokensService,
        TokenConfig $tokenConfig
    ) {
        parent::__construct();
        $this->actionsService = $actionsService;
        $this->tokenConfig = $tokenConfig;
        $this->tokensService = $tokensService;
    }

    protected function configure()
    {
        $this
            ->setName('game:action:rename-ship')
            ->setDescription('Rename a ship')
            ->addArgument(
                'ActionToken',
                InputArgument::REQUIRED,
                'Action token'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $token = $this->tokensService->parseTokenFromString($input->getArgument('ActionToken'));
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

        $newName = $this->actionsService->renameShip($tokenDetail);

        $output->writeln('Ship was named ' . $newName);

        $output->writeln('Done');
    }
}
