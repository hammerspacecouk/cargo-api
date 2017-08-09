<?php
namespace App\Command\Action;

use App\ApplicationTime;
use App\Config\TokenConfig;
use App\Data\StaticData\ShipName\ShipName;
use App\Service\ShipsService;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RenameShipCommand extends Command
{
    private $shipsService;
    private $tokenConfig;

    public function __construct(
        ShipsService $shipsService,
        TokenConfig $tokenConfig
    ) {
        parent::__construct();
        $this->shipsService = $shipsService;
        $this->tokenConfig = $tokenConfig;
    }

    protected function configure()
    {
        $instruction = 'Use ' . ShipName::PLACEHOLDER_EMPTY . ' for empty, ' . ShipName::PLACEHOLDER_RANDOM . ' for random';

        $this
            ->setName('game:action:rename-ship')
            ->setDescription('Rename a ship')
            ->addArgument(
                'shipId',
                InputArgument::REQUIRED,
                'Ship ID'
            )
            ->addArgument(
                'token',
                InputArgument::REQUIRED,
                'User token'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $shipId = Uuid::fromString($input->getArgument('shipId'));
        $token = $this->parseToken($input->getArgument('token'));
        $userID = Uuid::fromString($token->getClaim('userUUID'));
        // todo - validate userID and that the ship is yours
        $name = $token->getClaim('name');

        $output->writeln(
            sprintf(
                'Attempting to name ship %s to %s',
                $shipId->toString(),
                $name
            )
        );

        $newName = $this->shipsService->renameShip($shipId, $name);

        $output->writeln('Ship was named ' . $newName);

        $output->writeln('Done');
    }


    private function parseToken($token)
    {
        $token = (new Parser())->parse($token);

        $data = new ValidationData();
        $data->setIssuer($this->tokenConfig->getIssuer());
        $data->setAudience($this->tokenConfig->getAudience());
        $data->setId($this->tokenConfig->getId());
        $data->setCurrentTime(ApplicationTime::getTime()->getTimestamp());

        $signer = new Sha256();
        if (!$token->verify($signer, $this->tokenConfig->getPrivateKey()) ||
            !$token->validate($data)) {
            throw new AccessDeniedHttpException('Invalid credentials');
        }
        return $token;
    }
}
