<?php
declare(strict_types=1);

namespace App\Command\Admin;

use App\Command\AbstractCommand;
use App\Domain\ValueObject\EmailAddress;
use App\Infrastructure\ApplicationConfig;
use App\Service\AuthenticationService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoginTokenCommand extends AbstractCommand
{
    private $authenticationService;
    private $applicationConfig;

    public function __construct(AuthenticationService $authenticationService, ApplicationConfig $applicationConfig)
    {
        parent::__construct();
        $this->authenticationService = $authenticationService;
        $this->applicationConfig = $applicationConfig;
    }

    protected function configure(): void
    {
        $this
            ->setName('game:admin:token')
            ->setDescription('Generates an e-mail login token for an e-mail address')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'The e-mail address to create the token for'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $emailAddressInput = $this->getStringArgument($input, 'email');

        $emailAddress = new EmailAddress($emailAddressInput);
        $token = $this->authenticationService->makeEmailLoginToken($emailAddress);

        $output->writeln(
            $this->applicationConfig->getWebHostname() . '/login/email?token=' . (string)$token
        );
    }
}
