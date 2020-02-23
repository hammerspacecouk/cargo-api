<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UserRepository")
 * @ORM\Table(
 *     name="users",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *          @ORM\Index(name="user_google", columns={"google_id"}),
 *          @ORM\Index(name="user_microsoft", columns={"microsoft_id"}),
 *          @ORM\Index(name="user_reddit", columns={"reddit_id"}),
 *          @ORM\Index(name="user_ip_hash", columns={"anonymous_ip_hash"}),
 *          @ORM\Index(name="user_completion_time", columns={"game_completion_time"})
 *     }
 * )
 */
class User extends AbstractEntity
{
    /** @ORM\Column(type="binary", nullable=true, unique=true)) */
    public $googleId;

    /** @ORM\Column(type="binary", nullable=true, unique=true)) */
    public $microsoftId;

    /** @ORM\Column(type="binary", nullable=true, unique=true)) */
    public $redditId;

    /** @ORM\Column(type="binary", nullable=true)) */
    public $anonymousIpHash;

    /** @ORM\Column(type="text", length=50, nullable=true) */
    public $nickname;

    /** @ORM\Column(type="integer") */
    public $rotationSteps;

    /** @ORM\Column(type="integer", nullable=false, options={"default":0}) */
    public $permissionLevel = 0;

    /** @ORM\Column(type="bigint") */
    public $score = 0;

    /** @ORM\Column(type="bigint") */
    public $scoreRate = 0;

    /** @ORM\Column(type="datetime_microsecond") */
    public $scoreCalculationTime;

    /** @ORM\Column(type="datetime_microsecond") */
    public $gameStartDateTime;

    /** @ORM\Column(type="integer", nullable=true) */
    public $gameCompletionTime;

    /** @ORM\Column(type="text") */
    public $emblemSvg;

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
        ?string $ipHash,
        string $emblemSvg,
        int $rotationSteps,
        Port $homePort,
        PlayerRank $lastRankSeen,
        DateTimeImmutable $gameStartDateTime
    ) {
        parent::__construct();
        $this->anonymousIpHash = $ipHash;
        $this->rotationSteps = $rotationSteps;
        $this->homePort = $homePort;
        $this->emblemSvg = $emblemSvg;
        $this->scoreCalculationTime = (new \DateTimeImmutable())->setTimestamp(0);
        $this->lastRankSeen = $lastRankSeen;
        $this->gameStartDateTime = $gameStartDateTime;
    }
}
