<?php
declare(strict_types=1);
namespace App\Command\Setup;

use App\Command\ParseCSVTrait;
use App\Data\Database\Entity\PlayerStanding;
use App\Data\Database\EntityManager;
use Doctrine\ORM\Query;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakePlayerStandingsCommand extends Command
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
            ->setName('game:setup:make-player-standings')
            ->setDescription('One off command for populating player ranks table')
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
        $output->writeln('Making the standings');

        $filePath = $input->getArgument('inputList');
        $sourceData = $this->csvToArray($filePath);

        $progress = new ProgressBar($output, count($sourceData));
        $progress->start();

        foreach ($sourceData as $data) {
            if (empty($data['name'])) {
                $progress->advance();
                continue;
            }

            $id = Uuid::fromString($data['uuid']);
            $name = $data['name'];
            $orderNumber = (int) $data['orderNumber'];
            $threshold = (int) $data['threshold'];

            /** @var PlayerStanding $entity */
            $entity = $this->entityManager->getPlayerStandingRepo()->getByID($id, Query::HYDRATE_OBJECT);

            if ($entity) {
                $entity->name = $name;
                $entity->orderNumber = $orderNumber;
                $entity->threshold = $threshold;
            } else {
                $entity = new PlayerStanding(
                    $id,
                    $name,
                    $orderNumber,
                    $threshold
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
