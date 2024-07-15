<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Thread;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class ThreadRecords implements \IteratorAggregate, \Countable
{
    /**
     * @var array<int,ThreadRecord>
     */
    private readonly array $items;

    public function __construct(ThreadRecord ...$items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return \Traversable<int,ThreadRecord>
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
