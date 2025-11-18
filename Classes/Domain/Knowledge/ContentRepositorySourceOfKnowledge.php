<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Http\Factories\ServerRequestFactory;
use Neos\Http\Factories\UriFactory;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Sitegeist\Chatterbox\Domain\Quotation;

final class ContentRepositorySourceOfKnowledge implements SourceOfKnowledgeContract
{
    #[Flow\Inject]
    protected ContentContextFactory $contentContextFactory;

    #[Flow\Inject]
    protected ContentDimensionPresetSourceInterface $contentDimensionPresetSource;

    protected ?DocumentCollection $runtimeCache = null;

    /**
     * @var array<string, ?NodeInterface>
     */
    protected array $runtimeSourceNodeCache = [];

    public function __construct(
        private readonly KnowledgeSourceName $name,
        private readonly string $description,
        private readonly ContentRepositorySourceDesignator $designator,
    ) {
    }

    public static function createFromConfiguration(string $name, array $options): static
    {
        return new static(
            new KnowledgeSourceName($name),
            $options['description'] ?? 'null',
            ContentRepositorySourceDesignator::createFromConfiguration($options),
        );
    }

    public function getName(): KnowledgeSourceName
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContent(): DocumentCollection
    {
        if ($this->runtimeCache === null) {
            $rootNode = $this->designator->findRootNode($this->contentContextFactory, $this->contentDimensionPresetSource);
            $this->runtimeCache = new DocumentCollection(...$this->traverseSubtree($rootNode));
        }
        return $this->runtimeCache;
    }

    public function tryCreateQuotation(int $index, string $name, string $type): ?Quotation
    {
        $cacheid = $name . '. ' . $type;
        if (array_key_exists($cacheid, $this->runtimeSourceNodeCache) === false) {
            $this->runtimeSourceNodeCache[$cacheid] = $this->designator->findRootNode($this->contentContextFactory, $this->contentDimensionPresetSource)
                   ->getContext()
                   ->getNodeByIdentifier($name);
        }
        $sourceNode = $this->runtimeSourceNodeCache[$cacheid] ?? null;
        if (!$sourceNode instanceof NodeInterface) {
            return null;
        }

        try {
            return new Quotation(
                $index,
                $sourceNode->getProperty('titleOverride') ?: $sourceNode->getProperty('title') ?: $sourceNode->getLabel(),
                $sourceNode->getProperty('abstract') ?: $sourceNode->getProperty('description') ?: $sourceNode->getProperty('metaDescription') ?: '',
                $this->getNodeUri($sourceNode),
            );
        } catch (NoMatchingRouteException) {
            return null;
        }
    }

    /**
     * @return Document[]
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

        return array_filter($documents);
    }

    private function transformDocument(NodeInterface $documentNode): ?Document
    {
        $content = '';

        foreach ($documentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
            $content .= ' ' . $this->extractContent($childNode);
        }

        if (trim($content) === '') {
            return null;
        }

        $header = '# ' . ($documentNode->getProperty('titleOverride') ?: $documentNode->getProperty('title') ?: '') . PHP_EOL;
        $header .= ($documentNode->getProperty('description') ?: $documentNode->getProperty('metaDescription') ?: '') . PHP_EOL;
        $header .= PHP_EOL;

        return Document::createFromHtmlContent(
            $documentNode->getIdentifier(),
            trim(mb_convert_encoding($header . $content, 'UTF-8', 'UTF-8'))
        );
    }

    private function extractContent(NodeInterface $contentNode, int $level = 1): string
    {
        $content = '';

        $title = $contentNode->getProperty('title') ?? null;
        if ($title) {
            for ($i = 0; $i < $level; $i++) {
                $content .= '#';
            }
            $content .= ' ' . strip_tags($title) . PHP_EOL . PHP_EOL;
        }

        if ($contentNode->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
            foreach ($contentNode->getChildNodes('Neos.Neos:Content,Neos.Neos:ContentCollection') as $childNode) {
                $content .= $this->extractContent($childNode, $level + 1);
            }
        }

        if ($contentNode->getNodeType()->isOfType('Neos.Neos:Content')) {
            foreach ($contentNode->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
                if ($propertyName === 'title') {
                    continue;
                }
                if (($propertyConfiguration['type'] ?? 'string') === 'string' && ($propertyConfiguration['ui']['inlineEditable'] ?? false) === true) {
                    $content .= ' ' . $contentNode->getProperty($propertyName);
                }
            }
        }

        return trim($content);
    }

    private function getNodeUri(NodeInterface $node): Uri
    {
        $uri = ServerRequest::getUriFromGlobals();
        $uriBuilder = new UriBuilder();
        $actionRequestFactory = new ActionRequestFactory();
        $serverRequestFactory = new ServerRequestFactory(new UriFactory());
        $httpRequest = $serverRequestFactory->createServerRequest('GET', $uri)
            ->withAttribute(
                ServerRequestAttributes::ROUTING_PARAMETERS,
                RouteParameters::createEmpty()->withParameter('requestUriHost', $uri->getHost())
            );
        $uriBuilder->setRequest($actionRequestFactory->createActionRequest($httpRequest));
        $uriBuilder->setFormat('html');
        $uriBuilder->setCreateAbsoluteUri(true);

        return new Uri($uriBuilder->uriFor(
            'show',
            ['node' => $node],
            'Frontend\Node',
            'Neos.Neos'
        ));
    }
}
