<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;
use Sitegeist\Chatterbox\Domain\Quotation;
use Sitegeist\Chatterbox\Domain\ResolvedAndUnresolvedQuotations;
use Sitegeist\Chatterbox\Domain\UnresolvedQuotation;
use Sitegeist\Chatterbox\Domain\UnresolvedQuotationCollection;

/**
 * @implements \IteratorAggregate<SourceOfKnowledgeContract>
 */
final class SourceOfKnowledgeCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var SourceOfKnowledgeContract[]
     */
    public readonly array $items;

    public function __construct(SourceOfKnowledgeContract ...$items)
    {
        $this->items = $items;
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

    public function tryCreateQuotation(int $index, string $name, string $type): ?Quotation
    {
        list($sourceName, $localFilename) = explode('-', $name, 2);
        $source = $this->getKnowledgeSourceByName(new KnowledgeSourceName($sourceName));
        if ($source) {
            return $source->tryCreateQuotation($index, $localFilename, $type);
        }
        return null;
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
