<?php
declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Command\AbstractCommand;
use App\Infrastructure\ApplicationConfig;
use App\Infrastructure\DateTimeFactory;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestEmailCommand extends AbstractCommand
{
    private $applicationConfig;
    private $mailer;
    private $logger;

    public function __construct(
        ApplicationConfig $applicationConfig,
        Swift_Mailer $mailer,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->applicationConfig = $applicationConfig;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('maintenance:test-email')
            ->setDescription('Test sending and e-mail')
            ->addArgument(
                'recipient',
                InputArgument::REQUIRED,
                'Email address to send to'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->logger->debug(__CLASS__);

        $emailAddress = $this->getStringArgument($input, 'recipient');
        $output->writeln('Sending to ' . $emailAddress);

        $message = new Swift_Message(
            'This is a test',
            'Sent at <i>' . DateTimeFactory::now()->format('c') . '</i>',
            'text/html',
        );
        $message->addFrom(
            $this->applicationConfig->getEmailFromAddress(),
            $this->applicationConfig->getEmailFromName(),
        );
        $message->addTo($emailAddress);

        $this->mailer->send($message);

        $this->logger->info('Done');

        return 0;
    }
}
