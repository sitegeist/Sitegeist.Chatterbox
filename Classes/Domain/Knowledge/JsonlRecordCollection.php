<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

/**
 * @implements \IteratorAggregate<JsonlRecord>
 */
final class JsonlRecordCollection implements \IteratorAggregate, \Countable, \Stringable
{
    /**
     * @var array<JsonlRecord>
     */
    private readonly array $items;

    public function __construct(
        JsonlRecord ...$items
    ) {
        $this->items = $items;
    }

    /**
     * @return \Traversable<JsonlRecord>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this);
    }

    public function __toString(): string
    {
        return '[' . "\n" . implode("\n", array_map(
            fn (JsonlRecord $record): string => \str_replace(
                PHP_EOL,
                "\\\\n",
                \json_encode(
                    $record,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ),
            $this->items
        )) . "\n" . ']';
    }
}
