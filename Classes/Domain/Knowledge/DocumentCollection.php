<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

/**
 * @implements \IteratorAggregate<Document>
 */
final class DocumentCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<Document>
     */
    private readonly array $items;

    public function __construct(
        Document ...$items
    ) {
        $this->items = $items;
    }

    /**
     * @return \Traversable<Document>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this);
    }
}
