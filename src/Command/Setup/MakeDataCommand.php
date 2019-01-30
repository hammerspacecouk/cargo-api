<?php
declare(strict_types=1);

namespace App\Command\Setup;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeDataCommand extends Command
{
    private $playerRanksCommand;
    private $makeShipClassesCommand;
    private $makeClustersCommand;
    private $makePortsCommand;
    private $makeChannelsCommand;
    private $makeEffectsCommand;
    private $makeCrateTypesCommand;
    private $makeHintsCommand;
    private $updateDictionary;

    public function __construct(
        MakePlayerRanksCommand $playerRanksCommand,
        MakeShipClassesCommand $makeShipClassesCommand,
        MakeClustersCommand $makeClustersCommand,
        MakePortsCommand $makePortsCommand,
        MakeChannelsCommand $makeChannelsCommand,
        MakeEffectsCommand $makeEffectsCommand,
        MakeCrateTypesCommand $makeCrateTypesCommand,
        MakeHintsCommand $makeHintsCommand,
        UpdateDictionary $updateDictionary
    ) {
        parent::__construct();
        $this->playerRanksCommand = $playerRanksCommand;
        $this->makeShipClassesCommand = $makeShipClassesCommand;
        $this->makeClustersCommand = $makeClustersCommand;
        $this->makePortsCommand = $makePortsCommand;
        $this->makeChannelsCommand = $makeChannelsCommand;
        $this->makeEffectsCommand = $makeEffectsCommand;
        $this->makeCrateTypesCommand = $makeCrateTypesCommand;
        $this->makeHintsCommand = $makeHintsCommand;
        $this->updateDictionary = $updateDictionary;
    }

    protected function configure()
    {
        $this
            ->setName('game:init:make-data')
            ->setDescription('One off command for populating all data at once')
            ->addArgument(
                'inputFolder',
                InputArgument::REQUIRED,
                'Folder containing all the csv files'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $folderPath = $input->getArgument('inputFolder');

        // assumes files of a specific name in the input folder
        // run in the right order
        $this->playerRanksCommand->run($this->getInput($folderPath . '/Source Dataset - PlayerRank.csv'), $output);
        $this->makeShipClassesCommand->run($this->getInput($folderPath . '/Source Dataset - ShipClass.csv'), $output);
        $this->makeClustersCommand->run($this->getInput($folderPath . '/Source Dataset - Cluster.csv'), $output);
        $this->makePortsCommand->run($this->getInput($folderPath . '/Source Dataset - Port.csv'), $output);
        $this->makeChannelsCommand->run($this->getInput($folderPath . '/Source Dataset - Channel.csv'), $output);
        $this->makeEffectsCommand->run($this->getInput($folderPath . '/Source Dataset - Effects.csv'), $output);
        $this->makeCrateTypesCommand->run(
            $this->getInput($folderPath . '/Source Dataset - CrateContents.csv'),
            $output
        );
        $this->makeHintsCommand->run($this->getInput($folderPath . '/Source Dataset - Hints.csv'), $output);

        $this->updateDictionary->run(
            new ArrayInput([
                'inputList' => $folderPath . '/Source Dataset - Dictionary - shipName1.csv',
                'context' => 'shipName1',
            ]),
            $output
        );

        $this->updateDictionary->run(
            new ArrayInput([
                'inputList' => $folderPath . '/Source Dataset - Dictionary - shipName2.csv',
                'context' => 'shipName2',
            ]),
            $output
        );

        $output->writeln('');
        $output->writeln('Done ALL');
    }

    private function getInput(string $file): ArrayInput
    {
        return new ArrayInput(['inputList' => $file]);
    }
}

