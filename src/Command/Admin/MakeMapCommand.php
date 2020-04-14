<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Command\AbstractCommand;
use App\Domain\Entity\Channel;
use App\Domain\Entity\Port;
use App\Domain\ValueObject\Coordinate;
use App\Infrastructure\ApplicationConfig;
use App\Service\ChannelsService;
use App\Service\MapBuilder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMapCommand extends AbstractCommand
{
    private const ROTATION_STEP = 0;

    private ApplicationConfig $applicationConfig;
    private ChannelsService $channelsService;

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

        $builder = new MapBuilder($this->applicationConfig->getApiHostname(), self::ROTATION_STEP);

        $progress = new ProgressBar($output, $total);
        $progress->start();
        foreach ($channels as $channel) {
            $builder->addLink($channel);
            $progress->advance();
        }

        $progress->finish();
        $outputPath = __DIR__ . '/../../../build/map.svg';

        $ports = $this->buildPorts($builder->getPorts());
        $lines = $this->buildLines($builder->getLinks());
        $labels = $this->buildLabels($builder->getLinks());

        $svg = <<<SVG
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="{$builder->getViewBox()}">
                    $lines
                    $ports
                    $labels
                </svg>
        SVG;

        \file_put_contents($outputPath, $svg);
        $output->writeln('');
        $output->writeln('Done. Map available at ' . \realpath($outputPath));

        return 0;
    }

    private function buildLines(array $lines): string
    {
        return \implode('', \array_map(static function (array $line) {
            /** @var Coordinate $from */
            $from = $line['from'];
            /** @var Coordinate $to */
            $to = $line['to'];
            return '<line x1="' .
                $from->getX() . '" y1="' .
                $from->getY() . '" x2="' .
                $to->getX() . '" y2="' .
                $to->getY() . '" stroke="black" />';
        }, $lines));
    }

    private function buildLabels(array $lines): string
    {
        return \implode('', \array_map(static function (array $line) {
            /** @var Coordinate $from */
            $from = $line['from'];
            /** @var Coordinate $to */
            $to = $line['to'];
            $halfX = (($to->getX() - $from->getX()) / 2) + $from->getX();
            $halfY = ((($to->getY() - $from->getY()) / 2) + $from->getY()) - 8;

            return '<text text-anchor="middle" y="' . $halfY . '" ' .
                'style="font-family:sans-serif;font-size: 10px;fill:blue;text-shadow:0 0 1px #fff">' .
                '<tspan x="' . $halfX . '" text-anchor="middle">' . $line['id'] . '</tspan>' .
                '</text>';
        }, $lines));
    }

    /**
     * @param Port[] $ports
     */
    private function buildPorts(array $ports): string
    {
        $hexSvgs = \implode('', \array_map(function (Port $port) {
            $color = '#ff9999';
            if ($port->isSafe()) {
                $color = '#ffff99';
            }
            if ($port->isAHome()) {
                $color = '#99ff99';
            }
            return '<polygon data-id="' . $port->getId() .
                '" points="' . $this->getHexPoints($port->getCoordinates(self::ROTATION_STEP)) . '"' .
                ' stroke="black" fill="' . $color . '" />';
        }, $ports));

        $texts = \implode('', \array_map(static function (Port $port) {
            $coord = $port->getCoordinates(self::ROTATION_STEP);
            $lines = explode(' ', $port->getName());
            $x = $coord->getX();
            $outputs = [];
            $outputs[] = '<tspan x="' . $x . '" text-anchor="middle">' . array_shift($lines) . '</tspan>';
            foreach ($lines as $line) {
                $outputs[] = '<tspan x="' . $x . '" text-anchor="middle" dy="12">' . $line . '</tspan>';
            }

            return '<text y="' . $coord->getY() . '" style="font-family:sans-serif;font-size: 16px;">' .
                implode($outputs) . '</text>';
        }, $ports));

        return $hexSvgs . $texts;
    }

    private function getHexPoints(Coordinate $coordinate): string
    {
        $x = $coordinate->getX();
        $y = $coordinate->getY();
        $height = MapBuilder::GRID_WIDTH / (\sqrt(3) / 2);
        $size = $height / 2;

        $points = [];
        foreach (\range(0, 5) as $i) {
            $angle = \deg2rad(60 * $i + 30);
            $points[] = \round($x + $size * \cos($angle), 6) . ',' . \round($y + $size * \sin($angle), 6);
        }
        return \implode(' ', $points);
    }
}
