<?php
declare(strict_types=1);

namespace App\Controller\Purchase;

use App\Controller\AbstractUserAction;
use App\Controller\UserAuthenticationTrait;
use App\Service\AuthenticationService;
use App\Service\PurchasesService;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

class ReceiptAction
{
    use UserAuthenticationTrait;

    private AuthenticationService $authenticationService;
    private PurchasesService $purchasesService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/purchases/{purchaseId}', [
            '_controller' => self::class,
        ]);
    }

    public function __construct(
        AuthenticationService $authenticationService,
        PurchasesService $purchasesService
    ) {
        $this->authenticationService = $authenticationService;
        $this->purchasesService = $purchasesService;
    }

    public function __invoke(
        Request $request
    ): Response {
        $user = $this->getUser($request, $this->authenticationService);
        $purchaseId = $request->get('purchaseId');
        $purchase = $this->purchasesService->getById(Uuid::fromString($purchaseId));
        if (!$purchase) {
            throw new NotFoundHttpException('No such purchase');
        }
        $isCurrentUser = $purchase->getUser() && $purchase->getUser()->equals($user);
        if (!$isCurrentUser && !$user->isAdmin()) {
            throw new NotFoundHttpException('No such purchase');
        }

        $styles = <<<STYLES
            body {
                margin: 32px auto;
                max-width: 800px;
                font-family: sans-serif;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 48px;
            }
            td, th {
              border: 1px solid #444;
              padding: 0.5rem;
              text-align: left;
            }
            .full {
                width: 100%;
            }
        STYLES;


        return new Response(<<<HTML
            <!DOCTYPE html><html lang="en"><head><title>Receipt</title><style>$styles</style></head><body>
                <h1>Saxopholis.com</h1>
                <table>
                <tbody>
                <tr>
                <th>Customer Number</th>
                <td>{$purchase->getUserId()}</td>
                </tr>
                <tr>
                <th>Receipt Number</th>
                <td>{$purchase->getId()->toString()}</td>
                </tr>
                <tr>
                <th>Date</th>
                <td>{$purchase->getPurchaseTime()->format('j M Y')}</td>
                </tr>
                </tbody>
                </table>

                <table>
                <thead>
                <tr>
                <th colspan="2" class="full">Product</th>
                <th>Cost</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                <td colspan="2">{$purchase->getProductName()}</td>
                <td>{$purchase->getCost()}</td>
                </tr>

                <tr>
                <td colspan="3"></td>
                </tr>

                <tr>
                <td></td>
                <th>Sub-total</th>
                <td>{$purchase->getCost()}</td>
                </tr>
                <tr>
                <td></td>
                <th>VAT (20%)</th>
                <td>{$purchase->getTax()}</td>
                </tr>
                <tr>
                <td></td>
                <th>Total</th>
                <td>{$purchase->getTotal()}</td>
                </tr>
                </tbody>
                </table>

                <small>Saxopholis.com is wholly owned by Hammerspace LTD. VAT Registration number: 261229226</small>
            </body></html>
        HTML);
    }
}
