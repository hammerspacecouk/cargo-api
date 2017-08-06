<?php
namespace App\Command\Admin;

use App\Service\ShipsService;
use App\Service\UsersService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeShipCommand extends Command
{
    private $shipsService;
    private $usersService;

    public function __construct(ShipsService $shipsService, UsersService $usersService)
    {
        parent::__construct();
        $this->shipsService = $shipsService;
        $this->usersService = $usersService;
    }

    protected function configure()
    {
        $this
            ->setName('game:admin:make-ship')
            ->setDescription('Creates a new ship')
            ->addArgument(
                'userId',
                InputArgument::REQUIRED,
                'The user the ship will belong to'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Checking user');
        $output->writeln('Making a new ship');
        $user = $this->usersService->getById(Uuid::fromString($input->getArgument('userId')));
        if (!$user) {
            throw new \InvalidArgumentException('No such user');
        }

        $this->shipsService->makeNew($user);

        $output->writeln('Done');
    }
}
