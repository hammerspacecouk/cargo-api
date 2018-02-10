<?php
declare(strict_types=1);

namespace App\Service;

use App\Domain\ValueObject\EmailAddress;
use App\Domain\ValueObject\Token\EmailLoginToken;
use App\Infrastructure\ApplicationConfig;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Swift_Mailer;
use Swift_Message;

class EmailsService
{
    private const EMAIL_MIME_TYPE = 'text/html';

    private $mailer;
    private $applicationConfig;
    private $currentTime;
    private $cache;
    private $logger;

    public function __construct(
        Swift_Mailer $mailer,
        ApplicationConfig $applicationConfig,
        DateTimeImmutable $currentTime,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->applicationConfig = $applicationConfig;
        $this->currentTime = $currentTime;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function sendLoginEmail(EmailAddress $emailAddress, EmailLoginToken $token)
    {
        $url = $this->applicationConfig->getWebHostname() . '/login/email?token=' . (string)$token;

        // todo - use twig
        $body = <<<EMAIL
<p>This link will work for 1 hour and will log you in once</p>
<p><a href="$url">$url</a></p>
EMAIL;

        $this->send($emailAddress, 'Login link', $body);
    }

    private function send(EmailAddress $to, string $title, string $body)
    {
        $message = new Swift_Message(
            $title,
            $body,
            self::EMAIL_MIME_TYPE
        );
        $message->addFrom(
            $this->applicationConfig->getEmailFromAddress(),
            $this->applicationConfig->getEmailFromName()
        );
        $message->addTo((string)$to);

        $this->mailer->send($message);
    }
}
