<?php
declare(strict_types=1);

namespace App\Data\Database\Filters;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class DeletedItemsFilter extends SQLFilter
{
    public const FILTER_NAME = 'deleted_items_filter';

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Filter out entities that have a deletion time set
        if (!$targetEntity->reflClass->hasProperty('deletedAt')) {
            return '';
        }
        return $targetTableAlias . '.deleted_at IS NULL';
    }
}
