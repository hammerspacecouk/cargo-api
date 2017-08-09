<?php
declare(strict_types = 1);
namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\DictionaryRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="dictionary",
 *     options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *     indexes={@ORM\Index(name="dictionary_context", columns={"context"})})
 * )})
 */
class Dictionary extends AbstractEntity
{
    public const CONTEXT_SHIP_NAME_1 = 'SHIP_NAME_1';
    public const CONTEXT_SHIP_NAME_2 = 'SHIP_NAME_2';

    /** @ORM\Column(type="string") */
    public $word;

    /** @ORM\Column(type="string", columnDefinition="ENUM(
        'SHIP_NAME_1',
        'SHIP_NAME_2'
    )") */
    public $context;

    public function __construct(
        UuidInterface $id,
        string $word,
        string $context
    ) {
        parent::__construct($id);
        $this->word = $word;
        $this->context = $context;
    }
}