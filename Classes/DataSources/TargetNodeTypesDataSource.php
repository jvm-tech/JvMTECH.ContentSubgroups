<?php
declare(strict_types=1);

namespace JvMTECH\ContentSubgroups\DataSources;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\IconNameMappingService;
use Neos\Utility\Arrays;

class TargetNodeTypesDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    protected static $identifier = 'jvmtech-contentsubgroups-target-nodetypes';

    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected NodeTypeManager $nodeTypeManager;

    /**
     * @var TranslationHelper
     * @Flow\Inject
     */
    protected TranslationHelper $translationHelper;

    /**
     * @Flow\Inject
     * @var IconNameMappingService
     */
    protected $iconNameMappingService;

    /**
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array $arguments Additional arguments (key / value)
     * @return array
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $baseTag = Arrays::getValueByPath($arguments, 'contentSubgroup');

        $contentCollectionNode = $this->getClosestContentCollection($node);

        $nodeTypes = $this->nodeTypeManager->getNodeTypes(false);

        // Search for NodeType Groups...

        $groupNodeTypes = array_filter($nodeTypes, function (NodeType $nodeType) use ($baseTag) {
            $tag = Arrays::getValueByPath($nodeType->getProperties(), 'targetNodeTypeName.ui.inspector.editorOptions.dataSourceAdditionalData.contentSubgroup');
            if (!$tag) {
                return false;
            }

            return true;
        });

        $groupNodeTypes = array_map(function (NodeType $nodeType) {
            return [
                'label' => $this->translationHelper->translate($nodeType->getLabel()) ?: $nodeType->getLabel(),
                'tag' => Arrays::getValueByPath($nodeType->getProperties(), 'targetNodeTypeName.ui.inspector.editorOptions.dataSourceAdditionalData.contentSubgroup'),
            ];
        }, $groupNodeTypes);

        // Use tag as array key...

        $groups = array_combine(array_column($groupNodeTypes, 'tag'), $groupNodeTypes);

        // Search for NodeType Subgroups...

        $subNodeTypes = array_filter($nodeTypes, function (NodeType $nodeType) use ($baseTag, $contentCollectionNode) {
            // if (!$contentCollectionNode->isNodeTypeAllowedAsChildNode($nodeType)) {
            //     return false;
            // }

            $tags = Arrays::getValueByPath($nodeType->getOptions(), 'contentSubgroup.tags');
            if (!$tags) {
                return false;
            }

            return $baseTag ? in_array($baseTag, $tags) : true;
        });

        $subNodeTypes = array_map(function (NodeType $nodeType) {
            return [
                'label' => $nodeType->getLabel(),
                'value' => $nodeType->getName(),
                'tags' => Arrays::getValueByPath($nodeType->getOptions(), 'contentSubgroup.tags') ?: [],
                'icon' => $this->iconNameMappingService->convert($nodeType->getConfiguration('ui.icon')),
            ];
        }, $subNodeTypes);

        // Create select options...

        $groupedOptions = [];
        foreach ($subNodeTypes as $subNodeType) {
            foreach ($subNodeType['tags'] as $tag) {
                $groupedOptions[] = [
                    'label' => $subNodeType['label'],
                    'value' => $subNodeType['value'],
                    'icon' => $subNodeType['icon'],
                    'group' => isset($groups[$tag]['label']) ? $groups[$tag]['label'] : null,
                ];
            }
        }

        return $groupedOptions;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    protected function getClosestContentCollection(NodeInterface $node)
    {
        do {
            if ($node->getNodeType()->isOfType('Neos.Neos:ContentCollection') && !$node->getNodeType()->isOfType('Neos.Neos:Content')) {
                break;
            }
        } while ($node = $node->findParentNode());

        return $node;
    }
}
