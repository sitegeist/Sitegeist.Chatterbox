<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

#[Flow\Proxy(false)]
final class ContentRepositorySourceDesignator
{
    /**
     * @param array<string,string> $dimensionValues
     */
    private function __construct(
        private readonly NodeAggregateIdentifier|NodePath $root,
        private readonly array $dimensionValues,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function createFromConfiguration(array $values): self
    {
        $rootDesignator = $values['root'];
        if (\str_starts_with($rootDesignator, '#')) {
            return new self(
                NodeAggregateIdentifier::fromString(\mb_substr($rootDesignator, 1)),
                $values['dimensions']
            );
        } elseif (\str_starts_with($rootDesignator, '/')) {
            return new self(
                NodePath::fromString($rootDesignator),
                $values['dimensions']
            );
        }

        throw new \DomainException('ContentRepository source designators can only be instantiated from valid node aggregate ids or absolute node paths, "' . $rootDesignator . '" given.', 1705938983);
    }

    public function findRootNode(ContextFactory $contentContextFactory, ContentDimensionPresetSourceInterface $contentDimensionPresetSource): Node
    {
        $contextDimensions = [];
        foreach ($contentDimensionPresetSource->getAllPresets() as $dimensionId => $presetConfig) {
            $contextDimensions[$dimensionId] = $presetConfig['presets'][$this->dimensionValues[$dimensionId]]['values'];
        }
        $subgraph = $contentContextFactory->create([
            'dimensions' => $contextDimensions,
            'targetDimensions' => $this->dimensionValues,
        ]);

        $rootNode = $this->root instanceof NodeAggregateIdentifier
            ? $subgraph->getNodeByIdentifier((string)$this->root)
            : $subgraph->getNode((string)$this->root);

        if (!$rootNode instanceof Node) {
            $designatorType = $this->root instanceof NodeAggregateIdentifier
                ? 'id'
                : 'path';

            throw new \DomainException('Could not find root node with ' . $designatorType . ' "' . $this->root . '".', 1705939289);
        }

        return $rootNode;
    }
}
