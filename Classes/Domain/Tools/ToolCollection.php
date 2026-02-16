<?php

namespace Sitegeist\Chatterbox\Domain\Tools;

use Traversable;

/**
 * @implements \IteratorAggregate<int, ToolContract>
 */
class ToolCollection implements \IteratorAggregate
{
    /**
     * @var array<int,ToolContract|RemoteMCPServerTool>
     */
    public readonly array $items;
    public function __construct(ToolContract|RemoteMCPServerTool ...$tools)
    {
        $this->items = $tools;
    }

    /**
     * @return Traversable<int, ToolContract|RemoteMCPServerTool>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    public function getToolByName(string $toolName): ToolContract|RemoteMCPServerTool|null
    {
        foreach ($this->items as $tool) {
            if ($tool->getName() === $toolName) {
                return $tool;
            }
        }

        return null;
    }
}
