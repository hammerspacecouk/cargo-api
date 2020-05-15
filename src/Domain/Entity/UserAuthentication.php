<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class UserAuthentication extends Entity implements \JsonSerializable
{
    private ?User $user;
    private DateTimeImmutable $creationTime;
    private DateTimeImmutable $lastUsed;
    private DateTimeImmutable $expiry;

    public function __construct(
        UuidInterface $id,
        DateTimeImmutable $creationTime,
        DateTimeImmutable $lastUsed,
        DateTimeImmutable $expiry,
        ?User $user
    ) {
        parent::__construct($id);
        $this->creationTime = $creationTime;
        $this->lastUsed = $lastUsed;
        $this->expiry = $expiry;
        $this->user = $user;
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }

    public function getLastUsed(): DateTimeImmutable
    {
        return $this->lastUsed;
    }

    public function getExpiry(): DateTimeImmutable
    {
        return $this->expiry;
    }

    public function getUser(): User
    {
        if ($this->user === null) {
            throw new DataNotFetchedException('User was not in original query');
        }
        return $this->user;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'creationTime' => DateTimeFactory::toJson($this->getCreationTime()),
            'lastUsed' => DateTimeFactory::toJson($this->getLastUsed()),
            'expiry' => DateTimeFactory::toJson($this->getExpiry()),
        ];
        if ($this->user) {
            $data['user'] = $this->getUser();
        }
        return $data;
    }
}
