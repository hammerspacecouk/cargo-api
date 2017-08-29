<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Command\ParseCSVTrait;
use App\Data\Database\Entity\Port;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakePortsCommand extends Command
{
    use ParseCSVTrait;

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('game:setup:make-ports')
            ->setDescription('Creates a new port and places it on the map')
            ->addArgument(
                'inputList',
                InputArgument::REQUIRED,
                'File path of data source (.csv)'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $output->writeln('Making or updating the ports');

        $filePath = $input->getArgument('inputList');
        $sourceData = $this->csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];
            $isSafeHaven = (bool) $data['isSafeHaven'];
            $isOpen = (bool) $data['isOpen'];

            /** @var Port $entity */
            $entity = $this->entityManager->getPortRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
                $entity->isOpen = $isOpen;
                $entity->isSafeHaven = $isSafeHaven;
            } else {
                $entity = new Port(
                    $id,
                    $name,
                    $isSafeHaven,
                    $isOpen
                );
            }

            $this->entityManager->persist($entity);
            $progress->advance();
        }

        $this->entityManager->flush();
        $progress->finish();

        $output->writeln('');
        $output->writeln('Done');
    }
}
