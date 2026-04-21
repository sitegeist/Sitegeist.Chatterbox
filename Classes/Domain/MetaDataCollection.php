<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class MetaDataCollection
{
    /**
     * @var MetaDataInterface[]
     */
    public array $items;

    public function __construct(
        MetaDataInterface ...$items
    ) {
        $this->items = $items;
    }

    public function add(MetaDataCollection $other): self
    {
        return new self(...$this->items, ...$other->items);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }
}
