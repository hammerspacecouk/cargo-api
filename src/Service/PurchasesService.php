<?php
declare(strict_types=1);

namespace App\Service;

use App\Data\Database\Entity\Purchase as DbPurchase;
use App\Data\Database\Entity\User as DbUser;
use App\Domain\Entity\Purchase;
use App\Domain\Entity\User;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stripe\Checkout\Session;
use Stripe\Webhook;

class PurchasesService extends AbstractService
{
    private const VAT_MULTIPLIER = 1.2;

    public static function getKeyFromProductId(string $id): ?string
    {
        return array_flip(Purchase::PRODUCTS)[$id] ?? null;
    }

    public static function getFullProductId(): string
    {
        return Purchase::PRODUCTS[Purchase::PRODUCT_FULL_ACCOUNT];
    }

    public static function getNewShuttleId(): string
    {
        return Purchase::PRODUCTS[Purchase::PRODUCT_NEW_SHUTTLE];
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
            'client_reference_id' => $user->getId() . ':' . self::getFullProductId(),
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

    public function getSessionForContinue(User $user): array
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => self::getNewShuttleId(),
                'quantity' => 1,
            ]
            ],
            'client_reference_id' => $user->getId() . ':' . self::getNewShuttleId(),
            'mode' => 'payment',
            'success_url' =>
                $this->applicationConfig->getWebHostname() . '/play/profile?purchaseId={CHECKOUT_SESSION_ID}',
            'cancel_url' =>
                $this->applicationConfig->getWebHostname() . '/purchase/continue',
        ]);

        return [
            'sessionId' => $session->id,
        ];
    }

    public function handlePurchase(string $payload, string $signature): void
    {
        $this->logger->debug('event data', [
            'payload' => $payload,
        ]);

        $event = Webhook::constructEvent(
            $payload,
            $signature,
            $this->applicationConfig->getStripeWebhookKey(),
        );

        $this->logger->debug('event data', [
            'type' => $event->type ?? null,
        ]);

        if (!isset($event->type, $event->data->object) || $event->type !== 'checkout.session.completed') {
            throw new InvalidArgumentException('Unprocessable event type');
        }
        $session = $event->data->object;

        $checkoutId = $session->id;
        [$userId, $productId] = explode(':', $session->client_reference_id);

        $user = $this->entityManager->getUserRepo()->getByID(Uuid::fromString($userId), Query::HYDRATE_OBJECT);
        if (!$user) {
            return;
        }

        $product = self::getKeyFromProductId($productId);
        if (!$product) {
            throw new InvalidArgumentException('Unrecognised product');
        }

        // add purchase object
        $this->entityManager->transactional(function () use ($user, $product, $checkoutId, $productId) {
            switch ($product) {
                case Purchase::PRODUCT_FULL_ACCOUNT:
                    $cost = 799;
                    $this->upgradeAccount($user);
                    break;
                case Purchase::PRODUCT_NEW_SHUTTLE:
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
        $starterShipClass = $this->entityManager->getShipClassRepo()->getStarter(Query::HYDRATE_OBJECT);
        $shipName = $this->entityManager->getDictionaryRepo()->getRandomShipName();

        // make the player an initial ship and place it in the home port
        $ship = $this->entityManager->getShipRepo()->createNewShip($shipName, $starterShipClass, $user);
        $this->entityManager->getShipLocationRepo()->makeInPort($ship, $user->homePort, true);
    }

    public function purchaseExistsForUser(string $purchaseId, User $user): bool
    {
        return $this->entityManager->getPurchaseRepo()->purchaseExistsForUserId($purchaseId, $user->getId());
    }

    public function getById(UuidInterface $id): ?Purchase
    {
        $purchase = $this->entityManager->getPurchaseRepo()->getByID($id);
        if (!$purchase) {
            return null;
        }


        return $this->mapperFactory->createPurchaseMapper()->getPurchase($purchase);
    }

    public function getAllForUser(User $user): array
    {
        $purchases = $this->entityManager->getPurchaseRepo()->getAllForUserId($user->getId());
        return array_map(
            fn($purchase) => $this->mapperFactory->createPurchaseMapper()->getPurchase($purchase),
            $purchases
        );
    }
}
