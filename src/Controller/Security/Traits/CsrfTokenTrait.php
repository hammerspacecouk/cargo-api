<?php
declare(strict_types=1);

namespace App\Controller\Security\Traits;

use App\Domain\Exception\ExpiredTokenException;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Exception\TokenException;
use App\Service\TokensService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait CsrfTokenTrait
{
    public function checkCsrfToken(
        Request $request,
        string $context,
        TokensService $tokensService,
        LoggerInterface $logger
    ) {
        try {
            $token = $tokensService->getCsrfTokenFromRequest($request);
            if ($token->getContextKey() !== $context) {
                throw new InvalidTokenException('Token used in incorrect context');
            }
        } catch (TokenException $e) {
            if ($e instanceof ExpiredTokenException) {
                $logger->notice('[CSRF] [EXPIRED]');
            } else {
                $logger->notice('[CSRF] [FAIL]');
            }
            throw new BadRequestHttpException('Invalid request (' . $e->getMessage() . '). Please try again');
        }
    }
}
