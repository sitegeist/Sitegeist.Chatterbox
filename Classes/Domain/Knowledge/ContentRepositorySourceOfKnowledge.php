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

    public function getContent(): JsonlRecordCollection
    {
        $rootNode = $this->designator->findRootNode($this->contentContextFactory, $this->contentDimensionPresetSource);

        return new JsonlRecordCollection(...$this->traverseSubtree($rootNode));
    }

    public function tryCreateQuotation(string $identifier, string $quote, string $id): ?Quotation
    {
        $sourceNode = $this->designator->findRootNode(
            $this->contentContextFactory,
            $this->contentDimensionPresetSource
        )->getContext()
            ->getNodeByIdentifier($id);

        if (!$sourceNode instanceof Node) {
            return null;
        }

        try {
            return new Quotation(
                $identifier,
                $quote,
                $sourceNode->getLabel(),
                $sourceNode->getProperty('abstract') ?: $sourceNode->getProperty('description') ?: '',
                $this->getNodeUri($sourceNode),
            );
        } catch (NoMatchingRouteException) {
            return null;
        }
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

        return JsonlRecord::createFromHtmlContent(
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

    private function getNodeUri(Node $node): Uri
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
