<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

final class ContentRepositorySourceOfKnowledge implements SourceOfKnowledgeContract
{
    #[Flow\Inject]
    protected ContentContextFactory $contentContextFactory;

    #[Flow\Inject]
    protected ContentDimensionPresetSourceInterface $contentDimensionPresetSource;

    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly ContentRepositorySourceDesignator $designator,
    ) {
    }

    public static function createFromConfiguration(string $name, array $options): static
    {
        return new static(
            $name,
            $options['description'] ?? 'null',
            ContentRepositorySourceDesignator::createFromConfiguration($options),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContent(): JsonlRecordCollection
    {
        $rootNode = $this->designator->findRootNode($this->contentContextFactory, $this->contentDimensionPresetSource);

        return new JsonlRecordCollection(...$this->traverseSubtree($rootNode));
    }

    /**
     * @return JsonlRecord[]
     */
    private function traverseSubtree(NodeInterface $documentNode): array
    {
        $documents = [];
        if (!$documentNode->getNodeType()->isOfType('Neos.Neos:Shortcut')) {
            $documents[] = $this->transformDocument($documentNode);
        }
        foreach ($documentNode->getChildNodes('Neos.Neos:Document') as $childDocument) {
            $documents = array_merge($documents, $this->traverseSubtree($childDocument));
        }

        return $documents;
    }

    private function transformDocument(NodeInterface $documentNode): JsonlRecord
    {
        $content = '';
        foreach ($documentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
            $content .= ' ' . $this->extractContent($childNode);
        }

        return new JsonlRecord(
            $documentNode->getIdentifier(),
            new Uri('node://' . $documentNode->getIdentifier()),
            trim($content)
        );
    }

    private function extractContent(NodeInterface $contentNode): string
    {
        $content = '';

        if ($contentNode->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
            foreach ($contentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
                $content .= $this->extractContent($childNode);
            }
        }
        if ($contentNode->getNodeType()->isOfType('Neos.Neos:Content')) {
            foreach ($contentNode->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
                if (($propertyConfiguration['type'] ?? 'string') === 'string' && ($propertyConfiguration['ui']['inlineEditable'] ?? false) === true) {
                    $content .= ' ' . $contentNode->getProperty($propertyName);
                }
            }
        }

        return trim($content);
    }
}
