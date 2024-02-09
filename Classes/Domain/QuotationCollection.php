<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<Quotation>
 */
#[Flow\Proxy(false)]
final class QuotationCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<Quotation>
     */
    private readonly array $items;

    public function __construct(
        Quotation ...$items
    ) {
        $this->items = $items;
    }

    public static function createEmpty ()
    {
        return new self();
    }

    /**
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        return array_map(
            fn (Quotation $quotation): array => $quotation->toApiArray(),
            $this->items
        );
    }

    /**
     * @return \Traversable<Quotation>
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
}
