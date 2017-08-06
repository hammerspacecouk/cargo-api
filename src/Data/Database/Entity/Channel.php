<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\ChannelRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="channels",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *     indexes={@ORM\Index(name="channel_created", columns={"created_at"})})
 * )})
 */
class Channel extends AbstractEntity
{
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

    public function __construct(
        UuidInterface $id,
        Port $fromPort,
        Port $toPort,
        string $bearing,
        int $distance
    ) {
        parent::__construct($id);
        $this->fromPort = $fromPort;
        $this->toPort = $toPort;
        $this->bearing = $bearing;
        $this->distance = $distance;
    }
}
