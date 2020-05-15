<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\ValueObject\EmailAddress;
use App\Infrastructure\ApplicationConfig;
use Swift_Mailer;
use Swift_Message;

class EmailsService
{
    private const EMAIL_MIME_TYPE = 'text/html';

    private Swift_Mailer $mailer;
    private ApplicationConfig $applicationConfig;

    public function __construct(
        Swift_Mailer $mailer,
        ApplicationConfig $applicationConfig
    ) {
        $this->mailer = $mailer;
        $this->applicationConfig = $applicationConfig;
    }

    // this service has been kept as it is likely to want to send e-mails eventually (for e.g. invites)

    private function send(EmailAddress $to, string $title, string $body): void
    {
        $message = new Swift_Message(
            $title,
            $body,
            self::EMAIL_MIME_TYPE,
        );
        $message->addFrom(
            $this->applicationConfig->getEmailFromAddress(),
            $this->applicationConfig->getEmailFromName(),
        );
        $message->addTo((string)$to);

        $this->mailer->send($message);
    }
}
