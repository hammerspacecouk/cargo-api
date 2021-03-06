<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\DictionaryRepository")
 * @ORM\Table(
 *      name="dictionary",
 *      options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *      indexes={@ORM\Index(name="dictionary_context", columns={"context"})}
 * )})
 */
class Dictionary extends AbstractEntity
{
    public const CONTEXT_SHIP_NAME_1 = 'SHIP_NAME_1';
    public const CONTEXT_SHIP_NAME_2 = 'SHIP_NAME_2';

    /** @ORM\Column(type="string", length=191) */
    public string $word;

    /** @ORM\Column(type="string", length=191) */
    public string $context;

    public function __construct(
        string $word,
        string $context
    ) {
        parent::__construct();
        $this->word = $word;
        $this->context = $context;
    }
}
