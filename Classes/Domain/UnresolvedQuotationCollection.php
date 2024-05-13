<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<UnresolvedQuotation>
 *
 * @internal to be used for internal resolution in messages
 */
#[Flow\Proxy(false)]
final class UnresolvedQuotationCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<UnresolvedQuotation>
     */
    private readonly array $items;

    public function __construct(
        UnresolvedQuotation ...$items
    ) {
        $this->items = $items;
    }

    /**
     * @return \Traversable<UnresolvedQuotation>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function removeFromText(string $text): string
    {
        if ($this->isEmpty()) {
            return $text;
        }

        return \str_replace(
            array_map(
                fn (UnresolvedQuotation $quotationStub): string => $quotationStub->id,
                $this->items
            ),
            '',
            $text
        );
    }
}
