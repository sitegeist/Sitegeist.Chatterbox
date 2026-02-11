<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\MessageRecord;

#[Flow\Proxy(false)]
readonly class MetaDataItemCollection
{
    /**
     * @var MetaDataItemInterface[]
     */
    public array $items;

    public function __construct(
        MetaDataItemInterface ...$items
    ) {
        $this->items = $items;
    }

    /**
     * @param mixed[] $data
     * @return self
     */
    public static function createFromMixedArray(array $data): self
    {
        return new self( ...array_filter($data, fn($item) => $item instanceof MetaDataItemInterface));
    }

    /**
     * @param mixed[] $data
     * @return self
     */
    public function addFromMixedArray(array $data): self
    {
        return new self(...$this->items,  ...array_filter($data, fn($item) => $item instanceof MetaDataItemInterface));
    }

}
