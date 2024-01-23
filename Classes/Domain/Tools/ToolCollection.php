<?php

namespace Sitegeist\Chatterbox\Domain\Tools;

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

    public function getToolByName(string $toolName): ?ToolContract
    {
        foreach ($this->items as $tool) {
            if ($tool->getName() === $toolName) {
                return $tool;
            }
        }

        return null;
    }
}
