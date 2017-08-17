<?php
namespace App\Command\Action;

use App\Service\TokensService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RenameShipCommand extends Command
{
    private $actionsService;
    private $tokensService;

    public function __construct(
        TokensService $tokensService
    ) {
        parent::__construct();
        $this->tokensService = $tokensService;
    }

    protected function configure()
    {
        $this
            ->setName('play:action:rename-ship')
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
        $output->writeln('Renaming');
        $token = $this->tokensService->useRenameShipToken($input->getArgument('actionToken'));
        $output->writeln(
            sprintf(
                'Renamed ship %s to %s',
                (string) $token->getShipId(),
                $token->getShipName()
            )
        );
        $output->writeln('Done');
    }
}
