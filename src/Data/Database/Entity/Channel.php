<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ChannelRepository")
 * @ORM\Table(
 *     name="channels",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="channels_unique", columns={"from_port_id", "to_port_id"})
 *     }
 * )
 */
class Channel extends AbstractEntity
{
    private const ALLOWED_BEARINGS = ['NE', 'E', 'SE'];

    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $fromPort;
    /**
     * @ORM\ManyToOne(targetEntity="Port")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    public $toPort;

    /** @ORM\Column(type="string") */
    public $bearing;

    /** @ORM\Column(type="integer") */
    public $distance;

    /**
     * @ORM\ManyToOne(targetEntity="PlayerRank")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    public $minimumEntryRank;

    public function __construct(
        UuidInterface $id,
        Port $fromPort,
        Port $toPort,
        string $bearing,
        int $distance,
        ?PlayerRank $minimumEntryRank
    ) {
        parent::__construct($id);
        $this->fromPort = $fromPort;
        $this->toPort = $toPort;
        $this->distance = $distance;
        $this->minimumEntryRank = $minimumEntryRank;

        $bearing = \strtoupper(\trim($bearing));
        if (!\in_array($bearing, self::ALLOWED_BEARINGS)) {
            throw new \InvalidArgumentException('Invalid Bearing');
        }
        $this->bearing = $bearing;
    }
}
