<?php declare(strict_types=1);

namespace App\Command\Action;

use App\Controller\Actions\RequestShipNameAction;
use App\Controller\Security\Traits\UserTokenTrait;
use App\Service\ShipsService;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestShipNameCommand extends Command
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
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->logger->debug(__CLASS__);

        $userToken = $input->getArgument('userToken');
        $this->logger->info('User Token: ' . $userToken);

        $shipId = $input->getArgument('shipID');
        $this->logger->info('Ship Id: ' . $shipId);

        $this->logger->info('Building Request');

        $action = new RequestShipNameAction();

        $request = new Request(['shipId' => $shipId]);
        $request->headers->set('Authorization', 'Bearer ' . $userToken);

        $response = $action($request, $this->tokensService, $this->shipsService, $this->logger);

        $this->logger->info('Parsing response');
        $data = json_decode($response->getContent());

        $output->writeln('Name Offered: ' . $data->nameOffered);
        $output->writeln('Action token: ');
        $output->writeln($data->action->token);
    }
}
