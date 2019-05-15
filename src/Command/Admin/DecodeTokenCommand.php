<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Command\AbstractCommand;
use App\Data\TokenProvider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecodeTokenCommand extends AbstractCommand
{
    private $tokenProvider;

    public function __construct(TokenProvider $tokenProvider)
    {
        parent::__construct();
        $this->tokenProvider = $tokenProvider;
    }

    protected function configure(): void
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
        $tokenString = $this->getStringArgument($input, 'tokenString');
        $token = $this->tokenProvider->parseTokenFromString($tokenString, false);

        $output->writeln(
            \json_encode($token->getClaims(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
