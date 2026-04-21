<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\MessageRecord;

#[Flow\Proxy(false)]
readonly class MessageCollection
{
    /**
     * @var Message[]
     */
    public array $items;

    public function __construct(
        Message ...$items
    ) {
        $this->items = $items;
    }
}
