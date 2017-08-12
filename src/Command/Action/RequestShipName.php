<?php
namespace App\Command\Action;

use App\Controller\Security\Traits\UserTokenTrait;
use App\Domain\ValueObject\Token\ShipNameToken;
use App\Domain\ValueObject\Token\UserIDToken;
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
    private $tokensService;
    private $shipsService;

    public function __construct(
        TokensService $tokensService,
        ActionsService $actionsService,
        ShipsService $shipsService
    ) {
        parent::__construct();
        $this->actionsService = $actionsService;
        $this->tokensService = $tokensService;
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
        $output->writeln('Checking user');
        $token = $this->tokensService->parseTokenFromString($userToken);
        $userIdToken = new UserIDToken($token);
        $userId = $userIdToken->getUuid();

        $shipId = Uuid::fromString($input->getArgument('shipID'));

        // check the ship exists and belongs to the user
        if (!$this->shipsService->shipOwnedBy($shipId, $userId)) {
            throw new \InvalidArgumentException('Ship supplied does not belong to owner supplied');
        }

        $name = $this->actionsService->requestShipName($userId);

        $token = $this->tokensService->makeToken(
            ShipNameToken::makeClaims(
                $shipId,
                $name
            )
        );

        $output->writeln('Name Offered: ' . $name);
        $output->writeln('Action token: ');
        $output->writeln((string) $token);
    }
}
