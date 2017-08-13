<?php
declare(strict_types = 1);
namespace App\Data\Database\EntityRepository;

use App\Data\Database\Entity\InvalidToken as TokenEntity;
use App\Data\Database\Entity\InvalidToken;
use DateTimeImmutable;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class InvalidTokenRepository extends AbstractEntityRepository
{
    public function markAsUsed(
        UuidInterface $tokenId,
        DateTimeImmutable $expiryTime
    ): void {
        // we can clean up used tokens once they meet their original expiry time and become invalid naturally
        $entity = new TokenEntity(
            $tokenId,
            $expiryTime,
            TokenEntity::STATUS_USED
        );

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function isInvalid(
        UuidInterface $tokenId
    ): bool {
        return !!$this->getByID($tokenId);
    }

    public function removeExpired(DateTimeImmutable $now): void
    {
        $sql = 'DELETE FROM ' . InvalidToken::class . ' t WHERE t.invalidUntil < :now';
        $query = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('now', $now)
        ;
        $query->execute();
    }

    private function uuidFromToken(
        Token $token
    ): UuidInterface {
        return Uuid::fromString($token->getClaim('jti'));
    }
}
