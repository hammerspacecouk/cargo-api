<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\UsedActionTokenRepository")
 * @ORM\Table(
 *     name="used_action_tokens",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="used_action_tokens_expiry", columns={"expiry"}),
 *     }
 * )
 */
class UsedActionToken extends AbstractEntity
{
    /** @ORM\Column(type="datetime", nullable=true) */
    public $expiry;

    public function __construct(
        DateTimeImmutable $expiry
    ) {
        parent::__construct();
        $this->expiry = $expiry;
    }
}
