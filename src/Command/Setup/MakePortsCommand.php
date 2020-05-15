<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Transforms\csvToArray;

class MakePortsCommand extends AbstractCommand
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
            ->setName('game:init:make-ports')
            ->setDescription('Creates all the ports from the source data')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of data source (.csv)'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Making or updating the ports');

        $filePath = $this->getStringArgument($input, 'inputList');
        $sourceData = csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            if ((bool)($data['ignore'] ?? false)) {
                $progress->advance();
                continue;
            }

            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];
            $isSafeHaven = (bool)$data['isSafeHaven'];
            $isOpen = (bool)$data['isOpen'];
            $isAHome = (bool)$data['isHome'];
            $isDestination = (bool)$data['isDestination'];

            /** @var Port|null $entity */
            $entity = $this->entityManager->getPortRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
                $entity->isSafeHaven = $isSafeHaven;
                $entity->isAHome = $isAHome;
                $entity->isOpen = $isOpen;
                $entity->isDestination = $isDestination;
            } else {
                $entity = new Port(
                    $name,
                    $isSafeHaven,
                    $isAHome,
                    $isDestination,
                    $isOpen
                );
                $entity->id = $id;
            }
            $entity->uuid = $id->toString();

            $this->entityManager->persist($entity);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');

        return 0;
    }
}
