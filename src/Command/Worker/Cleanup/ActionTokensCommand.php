<?php
declare(strict_types=1);

namespace App\Command\Worker\Cleanup;

use App\Command\Worker\AbstractWorkerCommand;
use App\Service\UsersService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class ActionTokensCommand extends AbstractWorkerCommand
{
    protected const BATCH_SIZE = 1000;
    private $usersService;

    public function __construct(
        UsersService $usersService,
        DateTimeImmutable $currentTime,
        LoggerInterface $logger
    ) {
        parent::__construct($currentTime, $logger);
        $this->usersService = $usersService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('game:worker:cleanup:action-tokens')
            ->setDescription('Cleanup used action tokens');
    }

    protected function handle(DateTimeImmutable $now): int
    {
        return $this->usersService->cleanupActionTokens($now);
    }
}
