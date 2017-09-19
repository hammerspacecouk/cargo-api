<?php declare(strict_types=1);

namespace App\Command\Action;

use App\Controller\Actions\RenameShipAction;
use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class RenameShipCommand extends Command
{
    private $logger;
    private $tokensService;
    private $shipsService;

    public function __construct(
        TokensService $tokensService,
        ShipsService $shipsService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->tokensService = $tokensService;
        $this->shipsService = $shipsService;
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
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->logger->debug(__CLASS__);
        $tokenString = $input->getArgument('actionToken');
        $this->logger->info('Building Request');

        $action = new RenameShipAction;

        $request = new Request(['token' => $tokenString]);
        $response = $action($request, $this->tokensService, $this->shipsService, $this->logger);

        $this->logger->info('Parsing response');
        $data = json_decode($response->getContent());

        $output->writeln(
            sprintf(
                'Renamed ship %s to %s',
                $data->shipId,
                $data->newName
            )
        );
    }
}
