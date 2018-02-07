<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\DataNotFetchedException;
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
        string $ipAddress,
        DateTimeImmutable $creationTime,
        DateTimeImmutable $lastUsed,
        DateTimeImmutable $expiry,
        string $description,
        ?User $user
    ) {
        parent::__construct($id);
        $this->ipAddress = $ipAddress;
        $this->creationTime = $creationTime;
        $this->lastUsed = $lastUsed;
        $this->expiry = $expiry;
        $this->description = $description;
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
            'ipAddress' => $this->getIpAddress(),
            'creationTime' => $this->getCreationTime()->format('c'),
            'lastUsed' => $this->getLastUsed()->format('c'),
            'expiry' => $this->getExpiry()->format('c'),
            'description' => $this->getDescription(),
        ];
        if ($this->user) {
            $data['user'] = $this->getUser();
        }
        return $data;
    }
}
