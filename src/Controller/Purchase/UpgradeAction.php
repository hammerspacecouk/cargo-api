<?php
declare(strict_types=1);

namespace App\Controller\Purchase;

use App\Controller\AbstractUserAction;
use App\Service\AuthenticationService;
use App\Service\PurchasesService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class UpgradeAction extends AbstractUserAction
{
    private PurchasesService $purchasesService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/purchase/upgrade', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        PurchasesService $purchasesService,
        LoggerInterface $logger
    ) {
        parent::__construct($authenticationService, $logger);
        $this->purchasesService = $purchasesService;
    }

    public function invoke(
        Request $request
    ): array {
        if (!$this->user->isTrial() || $this->user->isAnonymous()) {
            // can't purchase again
            throw new NotFoundHttpException('Cannot purchase this');
        }
        return $this->purchasesService->getSessionForAccountUpgrade($this->user);
    }
}
