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

    private function rankKey(Channel $channel): string
    {
        return substr($channel->getMinimumRank()->getId()->toString(), 0, 3);
    }

    private function channelKey(Channel $channel): string
    {
        return substr($channel->getId()->toString(), 0, 6);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
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
        $lines[] = [0, 0, $endX, $endY, $this->channelKey($firstChannel), $this->rankKey($firstChannel)];

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
                    [, $startX, $startY] = $ports[$originId];
                    $end = $channel->getDestination();
                } elseif (isset($ports[$destinationId])) {
                    $reversed = true;
                    [, $startX, $startY] = $ports[$destinationId];
                    $end = $channel->getOrigin();
                } else {
                    continue;
                }

                $endId = $end->getId()->toString();
                if (isset($ports[$endId])) {
                    [, $endX, $endY] = $ports[$endId];
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
                $lines[] = [$startX, $startY, $endX, $endY, $this->channelKey($channel), $this->rankKey($channel)];

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

        return 0;
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
        $lineLength = ($length + 1) * self::HEXAGON_WIDTH;

        $endX = $startX + (
                (\cos(\deg2rad($bearing->getDegreesFromHorizon())) * $lineLength) * $bearing->getXMultiplier()
            );
        $endY = $startY + (\sin(\deg2rad($bearing->getDegreesFromHorizon())) * $lineLength);

        return [$endX, $endY];
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

    private function buildSvg(
        array $lines,
        array $ports,
        string $viewBox
    ): string {
        $lineSvgs = \implode('', \array_map(static function (array $line) {
            return '<line x1="' .
                $line[0] . '" y1="' .
                $line[1] . '" x2="' .
                $line[2] . '" y2="' .
                $line[3] . '" stroke="black" />';
        }, $lines));

        $lineRatings = \implode('', \array_map(static function (array $line) {
            $halfX = (($line[2] - $line[0]) / 2) + $line[0];
            $halfY = ((($line[3] - $line[1]) / 2) + $line[1]) - 2;

            return '<text text-anchor="middle" y="' . $halfY . '" ' .
                'style="font-family:sans-serif;font-size: 8px;fill:blue;text-shadow:0 0 1px #fff">' .
                '<tspan x="' . $halfX . '" text-anchor="middle">' . $line[4] . '</tspan>' .
                '<tspan x="' . $halfX . '" text-anchor="middle" dy="10">' . $line[5] . '</tspan>' .
                '</text>';
        }, $lines));

        $hexSvgs = \implode('', \array_map(function (array $port) {
            $color = '#ff9999';
            if ($port[0]->isSafe()) {
                $color = '#ffff99';
            }
            if ($port[0]->isAHome()) {
                $color = '#99ff99';
            }
            return '<polygon data-id="' . $port[0]->getId() .
                '" points="' . $this->getHexPoints($port[1], $port[2]) . '"' .
                ' stroke="black" fill="' . $color . '" />';
        }, $ports));

        $texts = \implode('', \array_map(static function (array $port) {
            $lines = explode(' ', $port[0]->getName());
            $x = $port[1];
            $outputs = [];
            $outputs[] = '<tspan x="' . $x . '" text-anchor="middle">' . array_shift($lines) . '</tspan>';
            foreach ($lines as $line) {
                $outputs[] = '<tspan x="' . $x . '" text-anchor="middle" dy="12">' . $line . '</tspan>';
            }

            return '<text y="' . $port[2] . '" style="font-family:sans-serif;font-size: 10px;">' .
                implode($outputs) . '</text>';
        }, $ports));

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="$viewBox">
                $lineSvgs
                $hexSvgs
                $texts
                $lineRatings
            </svg>
        SVG;
    }

    private function getHexPoints(int $x, int $y): string
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
