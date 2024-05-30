<?php
declare(strict_types=1);

namespace JvMTECH\ContentSubgroups\NodeCreationHandler;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Ui\NodeCreationHandler\NodeCreationHandlerInterface;
use Neos\Utility\Arrays;

class NodeTypeNodeCreationHandler implements NodeCreationHandlerInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected NodeTypeManager $nodeTypeManager;

    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @param NodeInterface $node The newly created node
     * @param array $data incoming data from the creationDialog
     * @return void
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function handle(NodeInterface $node, array $data)
    {
        if (isset($node->getNodeType()->getFullConfiguration()['properties']['targetNodeTypeName'])) {
            $targetNodeTypeName = null;

            if (isset($data['targetNodeTypeName'])) {
                $targetNodeTypeName = $data['targetNodeTypeName'];
            } else {
                $targetNodeTypeNameOptions = Arrays::getValueByPath($node->getNodeType()->getFullConfiguration(), 'properties.targetNodeTypeName.ui.inspector.editorOptions.values');
                if ($targetNodeTypeNameOptions) {
                    $targetNodeTypeName = array_key_first($targetNodeTypeNameOptions);
                }
            }

            if ($targetNodeTypeName) {
                $nodeType = $this->nodeTypeManager->getNodeType($targetNodeTypeName);
                $node->setNodeType($nodeType);

                // Handle other node creation handlers
                if (isset($node->getNodeType()->getFullConfiguration()['options']['nodeCreationHandlers'])) {
                    $nodeCreationHandlers = $node->getNodeType()->getFullConfiguration()['options']['nodeCreationHandlers'];
                    foreach ($nodeCreationHandlers as $nodeCreationHandlerName => $nodeCreationHandler) {
                        if ($nodeCreationHandlerName === 'contentSubgroups') {
                            continue;
                        }

                        // @todo map data for NodeTemplate CreationHandlers of the final NodeType..
                        $escapedNodeTypeName = str_replace(['.', ':', '-'], ['_', '_', '_'], $nodeType->getName());
                        $nodeCreationHandlerDataRaw = array_filter($data, function ($key) use ($escapedNodeTypeName) {
                            return str_starts_with($key, $escapedNodeTypeName . '_');
                        }, ARRAY_FILTER_USE_KEY);
                        $nodeCreationHandlerData = [];
                        foreach ($nodeCreationHandlerDataRaw as $nodeCreationHandlerDataKey => $nodeCreationHandlerDataValue) {
                            $nodeCreationHandlerData[str_replace($escapedNodeTypeName . '_', '', $nodeCreationHandlerDataKey)] = $nodeCreationHandlerDataValue;
                        }

                        $this->objectManager->get($nodeCreationHandler['nodeCreationHandler'])->handle($node, $nodeCreationHandlerData);
                    }
                }

                foreach ($data as $key => $value) {
                    if (!$node->getNodeType()->getPropertyType($key)) {
                        continue;
                    }

                    $node->setProperty($key, $value);
                }

                foreach ($nodeType->getProperties() as $propertyName => $propertyConfiguration) {
                    if (!$node->getProperty($propertyName)) {
                        $defaultValue = $propertyConfiguration['defaultValue'] ?? null;
                        if ($defaultValue) {
                            $node->setProperty($propertyName, $defaultValue);
                        }
                    }
                }
            }
        }
    }
}
