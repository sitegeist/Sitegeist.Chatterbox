<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

/**
 * @implements \IteratorAggregate<int, AssistantRecord>
 */
class AssistantCollection implements \IteratorAggregate
{
    /**
     * @var AssistantRecord[]
     */
    public readonly array $items;

    public function __construct(AssistantRecord ...$tools)
    {
        $this->items = $tools;
    }

    /**
     * @return \Traversable<int, AssistantRecord>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
