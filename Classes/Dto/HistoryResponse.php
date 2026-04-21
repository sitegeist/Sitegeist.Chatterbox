<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class HistoryResponse
{
    public function __construct(
        public MessageCollection $messages,
    ) {
    }
}
