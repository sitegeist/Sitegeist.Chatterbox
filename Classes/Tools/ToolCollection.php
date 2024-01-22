<?php

namespace Sitegeist\Chatterbox\Tools;

use Exception;
use Sitegeist\Chatterbox\Tools\ToolContract;
use Traversable;

/**
 * @implements \IteratorAggregate<int, ToolContract>
 */
class ToolCollection implements \IteratorAggregate
{
    /**
     * @var ToolContract[]
     */
    public readonly array $items;
    public function __construct(ToolContract ...$tools)
    {
        $this->items = $tools;
    }

    /**
     * @return Traversable<int, ToolContract>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
