<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\StringFrontend;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextAnnotationFileCitationObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\QuotationCollection;

/**
 * @implements \IteratorAggregate<SourceOfKnowledgeContract>
 */
final class SourceOfKnowledgeCollection implements \IteratorAggregate, \Countable
{

    protected StringFrontend|null $fileCache = null;

    /**
     * @var SourceOfKnowledgeContract[]
     */
    public readonly array $items;

    public function __construct(SourceOfKnowledgeContract ...$items)
    {
        $this->items = $items;
    }

    public function injectFileCache (StringFrontend $fileCache)
    {
        $this->fileCache = $fileCache;
    }

    public function getKnowledgeSourceByName(KnowledgeSourceName $name): ?SourceOfKnowledgeContract
    {
        foreach ($this->items as $sourceOfKnowledge) {
            if ($sourceOfKnowledge->getName()->equals($name)) {
                return $sourceOfKnowledge;
            }
        }

        return null;
    }

    public function resolveQuotations(ThreadMessageResponse $response, ClientContract $client): QuotationCollection
    {
        $annotations = [];
        foreach ($response->content as $contentObject) {
            if ($contentObject instanceof ThreadMessageResponseContentTextObject) {
                $annotations = array_merge($annotations, $contentObject->text->annotations);
            }
        }

        $quotations = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ThreadMessageResponseContentTextAnnotationFileCitationObject) {
                $fileData = $client->files()->retrieve($annotation->fileCitation->fileId);
                $fileName = KnowledgeFilename::tryFromSystemFileName($fileData->filename);
                if (!$fileName) {
                    continue;
                }
                $sourceOfKnowledge = $this->getKnowledgeSourceByName($fileName->knowledgeSourceName);
                $fileContent = $this->fileCache?->get($annotation->fileCitation->fileId);
                if ($fileContent === false || $fileContent === null) {
                    $fileContent = $client->files()->download($annotation->fileCitation->fileId);
                    $this->fileCache?->set($annotation->fileCitation->fileId, $fileContent);
                }
                $quotation = $sourceOfKnowledge?->findQuotationByQuote($annotation->text, $fileContent);
                if ($quotation) {
                    $quotations[] = $quotation;
                }
            }
        }

        return new QuotationCollection(...$quotations);
    }

    /**
     * @return \Traversable<SourceOfKnowledgeContract>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
