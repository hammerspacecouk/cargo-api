<?php
declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Config\ApplicationConfig;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestEmailCommand extends Command
{
    private $applicationConfig;
    private $mailer;
    private $currentTime;
    private $logger;

    public function __construct(
        ApplicationConfig $applicationConfig,
        Swift_Mailer $mailer,
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->applicationConfig = $applicationConfig;
        $this->mailer = $mailer;
        $this->currentTime = $currentTime;
        $this->logger = $logger;
    }

    protected function configure()
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
    ) {
        $this->logger->debug(__CLASS__);

        $emailAddress = $input->getArgument('recipient');
        $output->writeln('Sending to ' . $emailAddress);

        $message = new Swift_Message(
            'This is a test',
            'Sent at <i>' . $this->currentTime->format(\DateTime::ISO8601) . '</i>',
            'text/html'
        );
        $message->addFrom(
            $this->applicationConfig->getEmailFromAddress(),
            $this->applicationConfig->getEmailFromName()
        );
        $message->addTo($emailAddress);

        $this->mailer->send($message);

        $this->logger->info('Done');
    }
}
