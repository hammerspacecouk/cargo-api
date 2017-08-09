<?php
namespace App\Command\Action;

use App\ApplicationTime;
use App\Config\TokenConfig;
use App\Service\ShipsService;
use Firebase\JWT\JWT;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequestShipName extends Command
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
        $this
            ->setName('game:action:request-ship-name')
            ->setDescription('Request a new name for a ship. Will cost you')
            ->addArgument(
                'userToken',
                InputArgument::REQUIRED,
                'User identifying token (who will be charged)'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $userToken = $input->getArgument('userToken');

        // todo - move this validation to the controller that will handle the incoming POST
        $output->writeln('Checking user');
        // todo - validate token
        // todo - check the user has enough credits

        $output->writeln('Deducting cost');
        // todo - deduct credits
        $userId = Uuid::uuid4(); // todo - obviously

        $name = $this->shipsService->getRandomName();

        $token = $this->makeToken($userId, $name->getParts());

        // todo - convert to json web token
        $output->writeln('Name Chosen: ' . $name->getFullName());
        $output->writeln('First word: ' . $name->getFirstWord());
        $output->writeln('Second word: ' . $name->getSecondWord());
        $output->writeln('Token to return: ');
        $output->writeln((string) $token);
    }

    private function makeToken(UuidInterface $userId, array $nameParts)
    {
        $signer = new Sha256();
        $token = (new Builder())->setIssuer($this->tokenConfig->getIssuer())
            ->setAudience($this->tokenConfig->getAudience())
            ->setId($this->tokenConfig->getId(), true)
            ->setIssuedAt(ApplicationTime::getTime()->getTimestamp())
            ->setExpiration(ApplicationTime::getTime()->add(new \DateInterval('P1D'))->getTimestamp())
            ->set('userUUID', (string) $userId)
            ->set('nameParts', $nameParts)
            ->sign($signer, $this->tokenConfig->getPrivateKey())
            ->getToken();

        return $token;
    }
}
