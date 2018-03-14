<?php
declare(strict_types=1);

namespace App\Command\Worker\Cleanup;

use App\Command\Worker\AbstractWorkerCommand;
use App\Service\AuthenticationService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class AuthTokensCommand extends AbstractWorkerCommand
{
    protected const BATCH_SIZE = 1000;
    private $authenticationService;

    public function __construct(
        AuthenticationService $authenticationService,
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ) {
        parent::__construct($currentTime, $logger);
        $this->authenticationService = $authenticationService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:cleanup:auth-tokens')
            ->setDescription('Cleanup used auth tokens');
    }

    protected function handle(DateTimeImmutable $now): int
    {
        return $this->authenticationService->cleanupExpired($now);
    }
}
