<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use App\Service\MapBuilder;
use App\Data\Database\Entity\Port as DbPort;
use App\Data\Database\EntityManager;
use App\Domain\Entity\Channel;
use App\Domain\ValueObject\Bearing;
use App\Service\ChannelsService;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_shift;
use function cos;
use function count;
use function deg2rad;
use function implode;
use function sin;

class MakeCoordinatesCommand extends AbstractCommand
{
    private EntityManager $entityManager;
    private ChannelsService $channelsService;

    public function __construct(EntityManager $entityManager, ChannelsService $channelsService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->channelsService = $channelsService;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:init:make-coords')
            ->setDescription('Sets all the map coordinates');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Making coordinates');
        foreach (range(0, count(Bearing::getEmptyBearingsList()) - 1) as $step) {
            $output->writeln('Rotation step ' . $step);
            $this->makeCoordinates($step, $output);
        }
        $output->writeln('Done');
        return 0;
    }

    private function makeCoordinates(int $rotationStep, OutputInterface $output): void
    {
        /** @var Channel[] $channels */
        $channels = $this->channelsService->getAll();
        $total = count($channels);

        // handle the first row separately to mark the centre
        $firstChannel = array_shift($channels);
        if (!$firstChannel) {
            throw new InvalidArgumentException('No channels');
        }

        $firstPort = $firstChannel->getOrigin();
        $secondPort = $firstChannel->getDestination();
        $portCoords = [];

        // first port
        $portCoords[$firstPort->getId()->toString()] = [$firstPort, 0, 0];
        $limits = $this->limits(0, 0);

        // second port
        [$endX, $endY] = $this->relativeCoords(
            0,
            0,
            $firstChannel->getBearing()->getRotated($rotationStep),
            $firstChannel->getDistance()
        );

        $portCoords[$secondPort->getId()->toString()] = [$secondPort, $endX, $endY];

        $limits = $this->limits($endX, $endY, $limits);

        $progress = new ProgressBar($output, $total);
        $progress->start();
        $progress->advance();


        // now loop the rest
        while (!empty($channels)) {
            foreach ($channels as $i => $channel) {
                $originId = $channel->getOrigin()->getId()->toString();
                $destinationId = $channel->getDestination()->getId()->toString();
                if (isset($portCoords[$originId])) {
                    $reversed = false;
                    [, $startX, $startY] = $portCoords[$originId];
                    $end = $channel->getDestination();
                } elseif (isset($portCoords[$destinationId])) {
                    $reversed = true;
                    [, $startX, $startY] = $portCoords[$destinationId];
                    $end = $channel->getOrigin();
                } else {
                    continue;
                }

                $endId = $end->getId()->toString();
                if (isset($portCoords[$endId])) {
                    [, $endX, $endY] = $portCoords[$endId];
                } else {
                    // calculate and make it
                    [$endX, $endY] = $this->relativeCoords(
                        $startX,
                        $startY,
                        $channel->getBearing()->getRotated($rotationStep),
                        $channel->getDistance(),
                        $reversed
                    );
                    $portCoords[$endId] = [$end, $endX, $endY];
                }

                $limits = $this->limits($endX, $endY, $limits);

                // remove this channel from the list
                unset($channels[$i]);
                $progress->advance();
            }
        }

        $progress->finish();
        $output->writeln('');

        $viewBox = implode(' ', [
            $limits[0] - MapBuilder::GRID_WIDTH,
            $limits[1] - MapBuilder::GRID_WIDTH,
            ($limits[2] - $limits[0]) + (MapBuilder::GRID_WIDTH * 2),
            ($limits[3] - $limits[1]) + (MapBuilder::GRID_WIDTH * 2),
        ]);
        $output->writeln('Viewbox: ' . $viewBox);
        $output->writeln('Writing coordinates');
        $progress = new ProgressBar($output, count($portCoords));
        $progress->start();
        foreach ($portCoords as $port) {
            $this->setPortCoordinates($port[0]->getId(), $rotationStep, $viewBox, $port[1], $port[2]);
            $progress->advance();
        }
        $this->entityManager->flush();
        $progress->finish();
        $output->writeln('');
    }

    private function limits(int $newX, int $newY, array $limits = [0, 0, 0, 0]): array
    {
        // sX, sY, bX, bY
        if ($newX < $limits[0]) {
            $limits[0] = $newX;
        }
        if ($newY < $limits[1]) {
            $limits[1] = $newY;
        }
        if ($newX > $limits[2]) {
            $limits[2] = $newX;
        }
        if ($newY > $limits[3]) {
            $limits[3] = $newY;
        }
        return $limits;
    }

    private function relativeCoords(
        int $startX,
        int $startY,
        Bearing $bearing,
        int $length,
        bool $reversed = false
    ): array {
        if ($reversed) {
            $bearing = $bearing->getOpposite();
        }
        $lineLength = ($length + 1) * MapBuilder::GRID_WIDTH;

        $endX = $startX + (
                (cos(deg2rad($bearing->getDegreesFromHorizon())) * $lineLength) * $bearing->getXMultiplier()
            );
        $endY = $startY + (sin(deg2rad($bearing->getDegreesFromHorizon())) * $lineLength);

        return [(int)$endX, (int)$endY];
    }

    private function setPortCoordinates(UuidInterface $id, int $step, string $viewBox, int $x, int $y): void
    {
        /** @var DbPort $entity */
        $entity = $this->entityManager->getPortRepo()->getByID($id, Query::HYDRATE_OBJECT);
        if ($step === 0) {
            $entity->coordinates = [];
        }

        $entity->coordinates[$step] = [
            'v' => $viewBox,
            'c' => [$x, $y],
        ];
        $this->entityManager->persist($entity);
    }
}
