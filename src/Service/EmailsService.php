<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\EmailLoginToken;
use App\Infrastructure\ApplicationConfig;
use Swift_Mailer;
use Swift_Message;

// todo - remove me
class EmailsService
{
    private const EMAIL_MIME_TYPE = 'text/html';

    private $mailer;
    private $applicationConfig;

    public function __construct(
        Swift_Mailer $mailer,
        ApplicationConfig $applicationConfig
    ) {
        $this->mailer = $mailer;
        $this->applicationConfig = $applicationConfig;
    }

    public function sendLoginEmail(EmailAddress $emailAddress, EmailLoginToken $token): void
    {
        $url = $this->applicationConfig->getWebHostname() . '/login/email?token=' . $token;

        // todo - use twig
        $body = <<<EMAIL
            <p>This link will work for 1 hour and will log you in once</p>
            <p><a href="$url">$url</a></p>
        EMAIL;

        $this->send($emailAddress, 'Login link', $body);
    }

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
