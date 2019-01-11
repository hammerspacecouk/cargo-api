<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UserRepository")
 * @ORM\Table(
 *     name="users",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *          @ORM\Index(name="user_query_hash", columns={"query_hash"}),
 *          @ORM\Index(name="user_ip_hash", columns={"anonymous_ip_hash"})
 *     }
 * )
 */
class User extends AbstractEntity
{
    /** @ORM\Column(type="binary", nullable=true, unique=true)) */
    public $queryHash;

    /** @ORM\Column(type="binary", nullable=true)) */
    public $anonymousIpHash;

    /** @ORM\Column(type="string", length=6)) */
    public $colour = '000000';

    /** @ORM\Column(type="integer") */
    public $rotationSteps;

    /** @ORM\Column(type="bigint") */
    public $score = 0;

    /** @ORM\Column(type="bigint") */
    public $scoreRate = 0;

    /** @ORM\Column(type="datetime_microsecond") */
    public $scoreCalculationTime;

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     */
    public $homePort;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     * @ORM\JoinColumn()
     */
    public $lastRankSeen;

    public function __construct(
        ?string $queryHash,
        ?string $ipHash,
        string $colour,
        int $rotationSteps,
        Port $homePort,
        PlayerRank $lastRankSeen
    ) {
        parent::__construct();
        $this->queryHash = $queryHash;
        $this->anonymousIpHash = $ipHash;
        $this->colour = $colour;
        $this->rotationSteps = $rotationSteps;
        $this->homePort = $homePort;
        $this->scoreCalculationTime = (new \DateTimeImmutable())->setTimestamp(0);
        $this->lastRankSeen = $lastRankSeen;
    }
}
