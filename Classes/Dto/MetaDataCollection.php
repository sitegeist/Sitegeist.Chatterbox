<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\MetaDataCollection as DomainMetaDataCollection;
use Sitegeist\Chatterbox\Domain\MetaDataInterface;

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

    public static function createFromDomainMetaDataCollection(DomainMetaDataCollection $metaDataCollection): self
    {
        return new self(...$metaDataCollection->items);
    }
}
