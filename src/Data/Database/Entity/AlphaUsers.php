<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\HintRepository")
 * @ORM\Table(
 *      name="alpha_users",
 *      options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *          @ORM\Index(name="alpha_user_query_hash", columns={"query_hash"})
 *     }
 * )})
 */
class AlphaUsers extends AbstractEntity
{
    /** @ORM\Column(type="binary", nullable=false, unique=true)) */
    public $queryHash;

    public function __construct(
        string $queryHash
    ) {
        parent::__construct();
        $this->queryHash = $queryHash;
    }
}
