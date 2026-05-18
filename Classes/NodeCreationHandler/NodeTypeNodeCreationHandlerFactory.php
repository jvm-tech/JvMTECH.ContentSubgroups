<?php
declare(strict_types=1);

namespace JvMTECH\ContentSubgroups\NodeCreationHandler;

use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\Neos\Ui\Domain\NodeCreation\NodeCreationCommands;
use Neos\Neos\Ui\Domain\NodeCreation\NodeCreationElements;
use Neos\Neos\Ui\Domain\NodeCreation\NodeCreationHandlerFactoryInterface;
use Neos\Neos\Ui\Domain\NodeCreation\NodeCreationHandlerInterface;
use Neos\Utility\Arrays;

class NodeTypeNodeCreationHandlerFactory implements NodeCreationHandlerFactoryInterface
{
    public function build(ContentRepository $contentRepository): NodeCreationHandlerInterface
    {
        return new class($contentRepository->getNodeTypeManager()) implements NodeCreationHandlerInterface {
            public function __construct(
                protected NodeTypeManager $nodeTypeManager,
            )
            {
            }

            public function handle(NodeCreationCommands $commands, NodeCreationElements $elements): NodeCreationCommands
            {
                if (empty(Arrays::getValueByPath(
                    $this->nodeTypeManager->getNodeType($commands->first->nodeTypeName)->getFullConfiguration(),
                    ['properties', 'targetNodeTypeName'],
                ))) {
                    return $commands;
                }


                $targetNodeTypeName = $elements->get('targetNodeTypeName');
                if (is_string($targetNodeTypeName) && $targetNodeTypeName !== '') {
                    return $commands->withAdditionalCommands(
                        Commands::fromArray([
                            ChangeNodeAggregateType::create(
                                $commands->first->workspaceName,
                                $commands->first->nodeAggregateId,
                                NodeTypeName::fromString($targetNodeTypeName),
                                NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_PROMISED_CASCADE,
                            ),
                        ])
                    );
                }

                return $commands;
            }
        };
    }
}
