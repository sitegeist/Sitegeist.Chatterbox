<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

/**
 * @implements \IteratorAggregate<SourceOfKnowledgeContract>
 */
final class SourceOfKnowledgeCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var SourceOfKnowledgeContract[]
     */
    public readonly array $items;

    public function __construct(SourceOfKnowledgeContract ...$tools)
    {
        $this->items = $tools;
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
