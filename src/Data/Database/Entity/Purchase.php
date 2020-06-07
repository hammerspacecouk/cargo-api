<?php
declare(strict_types=1);

namespace App\Data\Database\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Data\Database\EntityRepository\PurchaseRepository")
 * @ORM\Table(
 *     name="purchases",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *      @ORM\Index(name="purhcase_checkout_id", columns={"checkout_session_id"})
 *     })
 * )})
 */
class Purchase extends AbstractEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    public User $user;

    /** @ORM\Column(type="string", length=191) */
    public string $checkoutSessionId;

    /** @ORM\Column(type="string", length=191) */
    public string $productId;

    /** @ORM\Column(type="integer") */
    public int $cost;

    /** @ORM\Column(type="integer") */
    public int $vat;

    public function __construct(
        User $user,
        string $checkoutSessionId,
        string $productId,
        int $cost,
        int $vat
    ) {
        parent::__construct();
        $this->user = $user;
        $this->checkoutSessionId = $checkoutSessionId;
        $this->productId = $productId;
        $this->cost = $cost;
        $this->vat = $vat;
    }
}
