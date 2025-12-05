<?php
declare(strict_types=1);

namespace JvMTECH\ContentSubgroups\DataSources;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\EelHelper\TranslationHelper;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\Neos\Service\IconNameMappingService;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;

class TargetNodeTypesDataSource extends AbstractDataSource
{
    /** @var string */
    protected static $identifier = 'jvmtech-contentsubgroups-target-nodetypes';
    #[Flow\Inject]
    protected TranslationHelper $translationHelper;
    #[Flow\Inject]
    protected IconNameMappingService $iconNameMappingService;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function getData(?Node $node = null, array $arguments = []): array
    {
        if (empty($node)) {
            return [];
        }

        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->contentRepositoryId)->getNodeTypeManager();
        $baseTag = Arrays::getValueByPath($arguments, 'contentSubgroup');

        $nodeTypes = $nodeTypeManager->getNodeTypes(false);

        // Search for NodeType Groups...

        /** @var NodeType[] $groupNodeTypes */
        $groupNodeTypes = array_filter($nodeTypes, function (NodeType $nodeType) {
            return !!Arrays::getValueByPath(
                $nodeType->getProperties(),
                'targetNodeTypeName.ui.inspector.editorOptions.dataSourceAdditionalData.contentSubgroup',
            );
        });

        $groupNodeTypes = array_map(function (NodeType $nodeType) {
            $array = [
                'label' => $this->translationHelper->translate($nodeType->getLabel()) ?: $nodeType->getLabel(),
                'tag' => Arrays::getValueByPath(
                    $nodeType->getProperties(),
                    'targetNodeTypeName.ui.inspector.editorOptions.dataSourceAdditionalData.contentSubgroup',
                ),
                'position' => $nodeType->getConfiguration('ui.position'),
            ];
            return $array;
        }, $groupNodeTypes);
        $sorterGroup = new PositionalArraySorter($groupNodeTypes);
        $groupNodeTypes = $sorterGroup->toArray();

        // Use tag as array key...

        $groups = array_combine(array_column($groupNodeTypes, 'tag'), $groupNodeTypes);
        // Search for NodeType Subgroups...

        $subNodeTypes = array_filter($nodeTypes, function (NodeType $nodeType) use ($baseTag) {
            $tags = Arrays::getValueByPath($nodeType->getOptions(), 'contentSubgroup.tags');
            if (!$tags) {
                return false;
            }

            return !$baseTag || in_array($baseTag, $tags);
        });

        $subNodeTypes = array_map(function (NodeType $nodeType) {

            return [
                'label' => $nodeType->getLabel(),
                'value' => $nodeType->name->value,
                'tags' => Arrays::getValueByPath($nodeType->getOptions(), 'contentSubgroup.tags') ?: [],
                'icon' => $this->iconNameMappingService->convert($nodeType->getConfiguration('ui.icon')),
                'position' => $nodeType->getConfiguration('ui.position'),
            ];
        }, $subNodeTypes);
        // Sort subNodeTypes by position
        $sorterSubNodeTypes = new PositionalArraySorter($subNodeTypes);
        $subNodeTypes = $sorterSubNodeTypes->toArray();

        // Create groups with placeholder if no group is set
        // Create groups to have to order of the groups correctly
        foreach ($groups as $tag => $group) {
            $groupedOptions[$tag] = [];
        }
        foreach ($subNodeTypes as $subNodeType) {
            foreach ($subNodeType['tags'] as $tag) {
                $groupedOptions[$tag][] = [
                    'label' => $subNodeType['label'],
                    'value' => $subNodeType['value'],
                    'icon' => $subNodeType['icon'],
                    'group' => isset($groups[$tag]['label']) ? $groups[$tag]['label'] : null,
                ];
            }
        }

        // Create select options by extracting sub elements to the parent level
        $resultArray= [];
        foreach ($groupedOptions as $groupedOption) {
            foreach ($groupedOption as $option) {
                $resultArray[] = $option;
            }
        }
        return $resultArray;
    }
}
