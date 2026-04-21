<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class StartChatResponse
{
    public function __construct(
        public ThreadId $id,
        public Message $message
    ) {
    }
}
