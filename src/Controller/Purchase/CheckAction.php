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

class CheckAction extends AbstractUserAction
{
    private PurchasesService $purchasesService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/purchase/{purchaseId}', [
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
        $purchaseId = $request->get('purchaseId');
        if (!$this->purchasesService->purchaseExistsForUser((string)$purchaseId, $this->user)) {
            throw new NotFoundHttpException('No such purchase');
        }

        return ['status' => 'ok'];
    }
}
