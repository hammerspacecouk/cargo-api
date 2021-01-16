<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Crate as DbCrate;
use App\Data\Database\Entity\Port;
use App\Data\Database\Entity\PortVisit;
use App\Data\Database\Entity\ShipLocation;
use App\Data\Database\Entity\User as DbUser;
use App\Data\Database\EntityManager;
use App\Infrastructure\ApplicationConfig;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MockWinCommand extends AbstractCommand
{
    private EntityManager $entityManager;
    private ApplicationConfig $applicationConfig;

    public function __construct(
        ApplicationConfig $applicationConfig,
        EntityManager $entityManager
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->applicationConfig = $applicationConfig;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:admin:mock-win')
            ->setDescription(
                'Puts a user into a state where they win on the next turn. ' .
                'Only works in local state. This will modify data integrity'
            )
            ->addArgument(
                'passcode',
                InputArgument::REQUIRED,
                'Code to authorise this request'
            )
            ->addArgument(
                'userID',
                InputArgument::REQUIRED,
                'The UUID of the user to edit'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $code = $this->getStringArgument($input, 'passcode');
        if ($code !== $this->applicationConfig->getApplicationSecret() || getenv('APP_ENV') !== 'dev') {
            $output->writeln('Not in Debug. Invalid code. Aborting');
        }
        $userId = Uuid::fromString($this->getStringArgument($input, 'userID'));

        // how to fake it:
        // the user's reticulum shuttle needs to be placed at the goal so it can leave and come back to win
        // the user needs to have visited all the other planets
        /** @var DbUser $rawUser */
        $rawUser = $this->entityManager->getUserRepo()->getByID($userId, Query::HYDRATE_OBJECT);

        // find the reticulum shuttle
        /** @var ShipLocation $shipLocation */
        $shipLocation = $this->entityManager->getShipLocationRepo()->createQueryBuilder('tbl')
            ->join('tbl.ship', 'ship')
            ->join('ship.shipClass', 'shipClass')
            ->join('tbl.port', 'port')
            ->where('IDENTITY(ship.owner) = :user')
            ->andWhere('tbl.isCurrent = true')
            ->andWhere('shipClass.isStarterShip = true')
            ->setParameter('user', $userId->getBytes())
            ->getQuery()->getOneOrNullResult(Query::HYDRATE_OBJECT);

        /** @var Port $port */
        $port = $this->entityManager->getPortRepo()->findOneBy(['isDestination' => true]);

        $shipLocation->isCurrent = false;
        $newLocation = new ShipLocation(
            $shipLocation->ship,
            $port,
            null,
            new \DateTimeImmutable(),
        );
        $this->entityManager->persist($shipLocation);
        $this->entityManager->persist($newLocation);

        // put a saxophone there
        $crateContents = $this->entityManager->getCrateTypeRepo()->getGoalCrateContents();
        $crate = new DbCrate(
            $crateContents->contents,
            $crateContents->value,
        );
        $crate->isGoal = true;
        $this->entityManager->persist($crate);

        $this->entityManager->getCrateLocationRepo()->makeInPort(
            $crate,
            $port
        );

        $port->isDestination = true;
        $this->entityManager->persist($port);

        // clear this user's previous port visits
        $this->entityManager->createQueryBuilder()
            ->delete(PortVisit::class, 'pv')
            ->where('IDENTITY(pv.player) = :userID')
            ->setParameter('userID', $userId->getBytes())
            ->getQuery()->execute();

        // make new ones
        /** @var Port[] $ports */
        $ports = $this->entityManager->getPortRepo()->findAll();
        foreach ($ports as $newPort) {
            if ($newPort->id->equals($port->id)) {
                continue; // don't include the current port
            }
            $visit = new PortVisit(
                $rawUser,
                $newPort,
                new \DateTimeImmutable(),
            );
            $this->entityManager->persist($visit);
        }
        $this->entityManager->flush();

        $output->writeln('Done');

        return 0;
    }
}
