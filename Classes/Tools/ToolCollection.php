<?php

namespace Sitegeist\Chatterbox\Tools;

use Exception;
use Sitegeist\Chatterbox\Tools\ToolContract;
use Traversable;

class ToolCollection implements \IteratorAggregate
{
    /**
     * @var ToolContract[]
     */
    public readonly array $items;
    public function __construct(ToolContract ... $tools) {
        $this->items = $tools;
    }

    public function getIterator(): iterable
    {
        yield from $this->items;
    }
}
