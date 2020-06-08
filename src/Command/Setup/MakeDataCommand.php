<?php
declare(strict_types=1);

namespace App\Command\Setup;

use App\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function App\Functions\Json\jsonDecode;

class MakeDataCommand extends AbstractCommand
{
    private MakePlayerRanksCommand $playerRanksCommand;
    private MakeShipClassesCommand $makeShipClassesCommand;
    private MakePortsCommand $makePortsCommand;
    private MakeChannelsCommand $makeChannelsCommand;
    private MakeEffectsCommand $makeEffectsCommand;
    private MakeCrateTypesCommand $makeCrateTypesCommand;
    private MakeHintsCommand $makeHintsCommand;
    private UpdateDictionary $updateDictionary;
    private MakeAchievementsCommand $makeAchievementsCommand;
    private MakeRankAchievementsCommand $makeRankAchievementsCommand;
    private MakeCoordinatesCommand $makeCoordinatesCommand;

    public function __construct(
        MakeAchievementsCommand $makeAchievementsCommand,
        MakeCoordinatesCommand $makeCoordinatesCommand,
        MakePlayerRanksCommand $playerRanksCommand,
        MakeShipClassesCommand $makeShipClassesCommand,
        MakePortsCommand $makePortsCommand,
        MakeChannelsCommand $makeChannelsCommand,
        MakeEffectsCommand $makeEffectsCommand,
        MakeCrateTypesCommand $makeCrateTypesCommand,
        MakeRankAchievementsCommand $makeRankAchievementsCommand,
        MakeHintsCommand $makeHintsCommand,
        UpdateDictionary $updateDictionary
    ) {
        parent::__construct();
        $this->playerRanksCommand = $playerRanksCommand;
        $this->makeShipClassesCommand = $makeShipClassesCommand;
        $this->makePortsCommand = $makePortsCommand;
        $this->makeChannelsCommand = $makeChannelsCommand;
        $this->makeEffectsCommand = $makeEffectsCommand;
        $this->makeCrateTypesCommand = $makeCrateTypesCommand;
        $this->makeHintsCommand = $makeHintsCommand;
        $this->updateDictionary = $updateDictionary;
        $this->makeAchievementsCommand = $makeAchievementsCommand;
        $this->makeRankAchievementsCommand = $makeRankAchievementsCommand;
        $this->makeCoordinatesCommand = $makeCoordinatesCommand;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:init:make-data')
            ->setDescription('One off command for populating all data at once');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $mapUrl = $_SERVER['DATA_URL'];

        $data = \file_get_contents($mapUrl);
        if (!$data) {
            throw new \RuntimeException('Could not fetch map');
        }
        $map = jsonDecode($data);

        // assumes files of a specific name in the input folder
        // run in the right order
        $output->writeln('PlayerRank: ' . $map['PlayerRank']);
        $this->playerRanksCommand->run($this->getInput($map['PlayerRank']), $output);

        $output->writeln('ShipClass: ' . $map['ShipClass']);
        $this->makeShipClassesCommand->run($this->getInput($map['ShipClass']), $output);

        $output->writeln('Port: ' . $map['Port']);
        $this->makePortsCommand->run($this->getInput($map['Port']), $output);

        $output->writeln('Channel: ' . $map['Channel']);
        $this->makeChannelsCommand->run($this->getInput($map['Channel']), $output);

        $output->writeln('Effects: ' . $map['Effects']);
        $this->makeEffectsCommand->run($this->getInput($map['Effects']), $output);

        $output->writeln('Achievements: ' . $map['Achievement']);
        $this->makeAchievementsCommand->run($this->getInput($map['Achievement']), $output);

        $output->writeln('Rank Achievements: ' . $map['RankAchievement']);
        $this->makeRankAchievementsCommand->run($this->getInput($map['RankAchievement']), $output);

        $output->writeln('CrateContents: ' . $map['CrateContents']);
        $this->makeCrateTypesCommand->run(
            $this->getInput(
                $map['CrateContents']
            ),
            $output
        );
        $output->writeln('Hints: ' . $map['Hints']);
        $this->makeHintsCommand->run($this->getInput($map['Hints']), $output);

        $output->writeln('DictionaryShipName1: ' . $map['DictionaryShipName1']);
        $this->updateDictionary->run(
            new ArrayInput([
                'inputList' => $map['DictionaryShipName1'],
                'context' => 'shipName1',
            ]),
            $output
        );

        $output->writeln('DictionaryShipName2: ' . $map['DictionaryShipName2']);
        $this->updateDictionary->run(
            new ArrayInput([
                'inputList' => $map['DictionaryShipName2'],
                'context' => 'shipName2',
            ]),
            $output
        );

        $output->writeln('Making Coordinates');
        $this->makeCoordinatesCommand->run(new ArrayInput([]), $output);

        $output->writeln('');
        $output->writeln('Done ALL');

        return 0;
    }

    private function getInput(string $file): ArrayInput
    {
        return new ArrayInput(['inputList' => $file]);
    }
}
