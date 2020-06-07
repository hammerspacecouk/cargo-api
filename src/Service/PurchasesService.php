<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Purchase as DbPurchase;
use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\User;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Stripe\Checkout\Session;
use Stripe\Webhook;

class PurchasesService extends AbstractService
{
    private const PRODUCTS = [
        PurchasesService::PRODUCT_FULL_ACCOUNT => 'price_1Gq3KQI7mUkfbXAwhJtciLcv',
        PurchasesService::PRODUCT_NEW_SHUTTLE => 'price_1Gq3MqI7mUkfbXAwOLJg7Bwh',
    ];
    private const PRODUCT_FULL_ACCOUNT = 'full_account';
    private const PRODUCT_NEW_SHUTTLE = 'new_shuttle';

    private const VAT_MULTIPLIER = 1.2;

    public static function getKeyFromProductId(string $id): ?string
    {
        return array_flip(self::PRODUCTS)[$id] ?? null;
    }

    public static function getFullProductId(): string
    {
        return self::PRODUCTS[self::PRODUCT_FULL_ACCOUNT];
    }

    public static function getNewShuttleId(): string
    {
        return self::PRODUCTS[self::PRODUCT_NEW_SHUTTLE];
    }

    public function getSessionForAccountUpgrade(User $user): array
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => self::getFullProductId(),
                'quantity' => 1,
            ]
            ],
            'client_reference_id' => (string)$user->getId(),
            'mode' => 'payment',
            'success_url' =>
                $this->applicationConfig->getWebHostname() . '/play/profile?purchaseId={CHECKOUT_SESSION_ID}',
            'cancel_url' =>
                $this->applicationConfig->getWebHostname() . '/purchase/upgrade',
        ]);

        return [
            'sessionId' => $session->id,
        ];
    }

    public function handlePurchase(string $payload, string $signature): void
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            $this->applicationConfig->getStripeWebhookKey(),
        );

        if (!isset($event->type, $event->data->object) || $event->type !== 'checkout.session.completed') {
            throw new InvalidArgumentException('Unprocessable event type');
        }
        $session = $event->data->object;

        $userId = $session->client_reference_id;
        $checkoutId = $session->id;
        $productId = $session->payment_intent;

        $product = self::getKeyFromProductId($productId);
        if (!$product) {
            throw new InvalidArgumentException('Unrecognised product');
        }

        $user = $this->entityManager->getUserRepo()->getByID(Uuid::fromString($userId), Query::HYDRATE_OBJECT);

        // add purchase object
        $this->entityManager->transactional(function () use ($user, $product, $checkoutId, $productId) {
            switch ($product) {
                case self::PRODUCT_FULL_ACCOUNT:
                    $cost = 799;
                    $this->upgradeAccount($user);
                    break;
                case self::PRODUCT_NEW_SHUTTLE:
                    $cost = 299;
                    $this->addShuttle($user);
                    break;
                default:
                    throw new InvalidArgumentException('Unrecognised product');
            }

            $vat = (int)($cost - ceil(($cost / self::VAT_MULTIPLIER)));
            $this->entityManager->persist(new DbPurchase(
                $user,
                $checkoutId,
                $productId,
                $cost,
                $vat
            ));
        });
    }

    private function upgradeAccount(DbUser $user): void
    {
        $user->permissionLevel = User::PERMISSION_FULL;
        $this->entityManager->persist($user);
    }

    private function addShuttle(DbUser $user): void
    {
    }

    public function purchaseExistsForUser(string $purchaseId, User $user): bool
    {
        return $this->entityManager->getPurchaseRepo()->purchaseExistsForUserId($purchaseId, $user->getId());
    }
}
