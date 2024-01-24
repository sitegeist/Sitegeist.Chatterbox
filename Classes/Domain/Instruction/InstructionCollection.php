<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Instruction;

/**
 * @implements \IteratorAggregate<InstructionContract>
 */
final class InstructionCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var InstructionContract[]
     */
    public readonly array $items;

    public function __construct(InstructionContract ...$items)
    {
        $this->items = $items;
    }

    /**
     * @return \Traversable<InstructionContract>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getContent(): string
    {
        return implode(
            " \n",
            array_map(
                fn (InstructionContract $instruction): string => $instruction->getContent(),
                $this->items
            )
        );
    }
}
