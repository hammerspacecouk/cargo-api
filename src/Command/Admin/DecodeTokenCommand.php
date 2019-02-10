<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Data\TokenProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecodeTokenCommand extends Command
{
    private $tokenProvider;

    public function __construct(TokenProvider $tokenProvider)
    {
        parent::__construct();
        $this->tokenProvider = $tokenProvider;
    }

    protected function configure()
    {
        $this
            ->setName('game:admin:decode')
            ->setDescription('Decodes a token string into JSON')
            ->addArgument(
                'tokenString',
                InputArgument::REQUIRED,
                'The string to decode'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $tokenString = $input->getArgument('tokenString');
        $token = $this->tokenProvider->parseTokenFromString($tokenString);

        $output->writeln(
            \json_encode($token->getClaims(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
