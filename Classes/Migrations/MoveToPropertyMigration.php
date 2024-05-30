<?php
declare(strict_types=1);

namespace JvMTECH\ContentSubgroups\Migrations;

use Neos\ContentRepository\Domain\Model\NodeInterface;

class MoveToPropertyMigration implements PropertyMigrationInterface
{
    public static function migrate(
        string $oldNodeTypeName,
        string $newNodTypeName,
        string $oldPropertyName,
        string $newPropertyName,
        mixed $oldValue,
        NodeInterface $node
    ) {
        $node->setProperty($newPropertyName, $oldValue);
        return $node;
    }
}
