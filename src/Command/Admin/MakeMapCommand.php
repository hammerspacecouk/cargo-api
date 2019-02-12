<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Command\AbstractCommand;
use App\Domain\Entity\Channel;
use App\Domain\ValueObject\Bearing;
use App\Infrastructure\ApplicationConfig;
use App\Service\ChannelsService;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMapCommand extends AbstractCommand
{
    private const HEXAGON_WIDTH = 60;

    private $applicationConfig;
    private $channelsService;

    public function __construct(ApplicationConfig $applicationConfig, ChannelsService $channelsService)
    {
        parent::__construct();
        $this->applicationConfig = $applicationConfig;
        $this->channelsService = $channelsService;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:admin:map')
            ->setDescription('Generates a map from the database')
            ->addArgument(
                'passcode',
                InputArgument::REQUIRED,
                'Code to authorise this request'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $code = $this->getStringArgument($input, 'passcode');
        if ($code !== $this->applicationConfig->getApplicationSecret()) {
            $output->writeln('Invalid code. Aborting');
        }

        /** @var Channel[] $channels */
        $channels = $this->channelsService->getAll();
        $total = \count($channels);
        $output->writeln($total . ' channels');

        $ports = [];
        $lines = [];

        // handle the first row separately to mark the centre
        $firstChannel = \array_shift($channels);
        if (!$firstChannel) {
            throw new \InvalidArgumentException('No channels');
        }

        $firstPort = $firstChannel->getOrigin();
        $secondPort = $firstChannel->getDestination();

        $ports[$firstPort->getId()->toString()] = [$firstPort, 0, 0];

        [$endX, $endY] = $this->relativeCoords(0, 0, $firstChannel->getBearing(), $firstChannel->getDistance());

        $ports[$secondPort->getId()->toString()] = [$secondPort, $endX, $endY];

        $limits = $this->limits($endX, $endY);
        $lines[] = [0, 0, $endX, $endY];

        $progress = new ProgressBar($output, $total);
        $progress->start();
        $progress->advance();

        // now loop the rest
        while (!empty($channels)) {
            foreach ($channels as $i => $channel) {
                $originId = $channel->getOrigin()->getId()->toString();
                $destinationId = $channel->getDestination()->getId()->toString();
                if (isset($ports[$originId])) {
                    $reversed = false;
                    [,$startX,$startY] = $ports[$originId];
                    $end = $channel->getDestination();
                } elseif (isset($ports[$destinationId])) {
                    $reversed = true;
                    [,$startX,$startY] = $ports[$destinationId];
                    $end = $channel->getOrigin();
                } else {
                    continue;
                }

                $endId = $end->getId()->toString();
                if (isset($ports[$endId])) {
                    [,$endX,$endY] = $ports[$endId];
                } else {
                    // calculate and make it
                    [$endX, $endY] = $this->relativeCoords(
                        $startX,
                        $startY,
                        $channel->getBearing(),
                        $channel->getDistance(),
                        $reversed
                    );
                    $ports[$endId] = [$end, $endX, $endY];
                }

                $limits = $this->limits($endX, $endY, $limits);
                $lines[] = [$startX, $startY, $endX, $endY];

                // remove this channel from the list
                unset($channels[$i]);
                $progress->advance();
            }
        }

        $progress->finish();
        $outputPath = __DIR__ . '/../../../build/map.svg';
        $viewBox = \implode(' ', [
            $limits[0] - self::HEXAGON_WIDTH,
            $limits[1] - self::HEXAGON_WIDTH,
            ($limits[2] - $limits[0]) + (self::HEXAGON_WIDTH * 2),
            ($limits[3] - $limits[1]) + (self::HEXAGON_WIDTH * 2),
        ]);
        \file_put_contents($outputPath, $this->buildSvg(
            $lines,
            $ports,
            $viewBox,
        ));
        $output->writeln('');
        $output->writeln('Done. Map available at ' . \realpath($outputPath));
    }

    private function relativeCoords($startX, $startY, Bearing $bearing, $length, $reversed = false): array
    {
        if ($reversed) {
            $bearing = $bearing->getOpposite();
        }
        $lineLength = ($length + 1) * self::HEXAGON_WIDTH;

        $endX = $startX + (
                (\cos(\deg2rad($bearing->getDegreesFromHorizon())) * $lineLength) * $bearing->getXMultiplier()
            );
        $endY = $startY + (\sin(\deg2rad($bearing->getDegreesFromHorizon())) * $lineLength);

        return [$endX, $endY];
    }

    private function limits($newX, $newY, $limits = [0, 0, 0, 0]): array
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

    private function buildSvg(
        array $lines,
        array $ports,
        string $viewBox
    ): string {
        $lineSvgs = \implode('', \array_map(function (array $line) {
            return '<line x1="' .
                $line[0] . '" y1="' .
                $line[1] . '" x2="' .
                $line[2] . '" y2="' .
                $line[3] . '" stroke="black" />';
        }, $lines));

        $hexSvgs = \implode('', \array_map(function (array $port) {
            return '<polygon data-id="' . $port[0]->getId() .
                '" points="' . $this->getHexPoints($port[1], $port[2]) . '"' .
                ' stroke="black" fill="white" />';
        }, $ports));

        $texts = \implode('', \array_map(function (array $port) {
            return '<text x="' . ($port[1] - (self::HEXAGON_WIDTH / 2.05)) .
                '" y="' . ($port[2] + 4) . '" style="font-family:sans-serif;font-size: 12px" textLength="' .
                self::HEXAGON_WIDTH * 0.95 . '" lengthAdjust="spacingAndGlyphs">' .
                $port[0]->getName() . '</text>';
        }, $ports));

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="$viewBox">
                $lineSvgs
                $hexSvgs
                $texts
            </svg>
        SVG;
    }

    private function getHexPoints($x, $y): string
    {
        $height = self::HEXAGON_WIDTH / (\sqrt(3) / 2);
        $size = $height / 2;

        $points = [];
        foreach (\range(0, 5) as $i) {
            $angle = \deg2rad(60 * $i + 30);
            $points[] = \round($x + $size * \cos($angle), 6) . ',' . \round($y + $size * \sin($angle), 6);
        }
        return \implode(' ', $points);
    }
}
