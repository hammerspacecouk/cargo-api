<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
use App\Infrastructure\DateTimeFactory;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

class UserAuthentication extends Entity implements \JsonSerializable
{
    private $user;
    private $creationTime;
    private $ipAddress;
    private $lastUsed;
    private $expiry;
    private $description;

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

    public function getIpAddress(): ?string
    {
        if (!empty($this->ipAddress)) {
            return $this->ipAddress;
        }
        return null;
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUser(): User
    {
        if ($this->user === null) {
            throw new DataNotFetchedException('User was not in original query');
        }
        return $this->user;
    }

    public function jsonSerialize()
    {
        $data = [
            'creationTime' => $this->getCreationTime()->format(DateTimeFactory::FULL),
            'lastUsed' => $this->getLastUsed()->format(DateTimeFactory::FULL),
            'expiry' => $this->getExpiry()->format(DateTimeFactory::FULL),
        ];
        if ($this->user) {
            $data['user'] = $this->getUser();
        }
        return $data;
    }
}
