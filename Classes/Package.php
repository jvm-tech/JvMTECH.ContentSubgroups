<?php
declare(strict_types=1);

namespace JvMTECH\ContentSubgroups;

use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Controller\Backend\ContentController;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Service\SiteImportService;
use Neos\Neos\Domain\Service\SiteService;
use Neos\Neos\EventLog\Integrations\ContentRepositoryIntegrationService;
use Neos\Neos\Routing\Cache\RouteCacheFlusher;
use Neos\Neos\Service\PublishingService;
use Neos\Neos\Fusion\Cache\ContentCacheFlusher;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\Utility\Arrays;

/**
 * The Neos Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(Node::class, 'nodePropertyChanged', function (NodeInterface $node, string $propertyName, mixed $oldNodeTypeName, mixed $newNodTypeName) use ($bootstrap) {
            if ($propertyName === 'targetNodeTypeName' && $oldNodeTypeName) {
                $propertiesBeforeNodeTypeChange = $node->getProperties();

                $nodeTypeManager = $bootstrap->getObjectManager()->get(NodeTypeManager::class);

                // Change node type...
                $nodeType = $nodeTypeManager->getNodeType($newNodTypeName);
                $node->setNodeType($nodeType);

                // Migrate all properties with the same name and type...
                foreach ($propertiesBeforeNodeTypeChange as $propertyName => $value) {
                    if (!$nodeType->getPropertyType($propertyName)) {
                        continue;
                    }

                    $node->setProperty($propertyName, $value);
                }

                // Migrate configured properties...
                $propertyMigrations = Arrays::getValueByPath($nodeType->getOptions(), ['contentSubgroup', 'propertyMigrationFrom', $oldNodeTypeName]) ?: [];
                foreach ($propertyMigrations as $oldPropertyName => $migrations) {
                    if (!$propertiesBeforeNodeTypeChange[$oldPropertyName]) {
                        continue;
                    }

                    foreach ($migrations as $migration => $newPropertyName) {
                        $migrationClass = null;
                        if (str_starts_with($migration, '\\') && class_exists($migration)) {
                            $migrationClass = $migration;
                        } else if (class_exists('\\JvMTECH\\ContentSubgroups\\Migrations\\' . $migration . 'PropertyMigration')) {
                            $migrationClass = '\\JvMTECH\\ContentSubgroups\\Migrations\\' . $migration . 'PropertyMigration';
                        }

                        if ($migrationClass) {
                            $node = $migrationClass::migrate(
                                $oldNodeTypeName,
                                $newNodTypeName,
                                $oldPropertyName,
                                $newPropertyName,
                                $propertiesBeforeNodeTypeChange[$oldPropertyName],
                                $node
                            );
                        }
                    }
                }
            }
        });
    }
}
