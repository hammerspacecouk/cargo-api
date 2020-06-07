<?php
declare(strict_types=1);

namespace App\Controller\Purchase;

use App\Service\PurchasesService;
use InvalidArgumentException;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Route;
use UnexpectedValueException;

class HandleAction
{
    private PurchasesService $purchasesService;

    public static function getRouteDefinition(): Route
    {
        return new Route('/purchase/handle', [
            '_controller' => self::class,
        ], [], [], '', [], ['POST']);
    }

    public function __construct(
        PurchasesService $purchasesService
    ) {
        $this->purchasesService = $purchasesService;
    }

    public function __invoke(
        Request $request
    ): Response {

        $payload = $request->getContent() ?? '';
        $signature = $request->headers->get('Stripe-Signature', '');

        try {
            $this->purchasesService->handlePurchase((string)$payload, $signature);
        } catch (UnexpectedValueException $e) {
            throw new BadRequestHttpException('Invalid payload');
        } catch (SignatureVerificationException $e) {
            throw new BadRequestHttpException('Invalid signature ' . $e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException('Invalid event');
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
