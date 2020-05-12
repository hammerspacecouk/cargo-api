<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\User;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAdminCommand extends AbstractCommand
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:admin:add')
            ->setDescription('Adds a user to the admin list')
            ->addArgument(
                'uuid',
                InputArgument::REQUIRED,
                'User uuid'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $uuid = Uuid::fromString($this->getStringArgument($input, 'uuid'));
        /** @var User|null $entity */
        $entity = $this->entityManager->getUserRepo()->getByID($uuid, Query::HYDRATE_OBJECT);

        if (!$entity) {
            throw new \InvalidArgumentException('No such user');
        }

        $entity->permissionLevel = 100;

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $output->writeln('Done');

        return 0;
    }
}
