<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class StartChatRequest
{
    public function __construct(
        public AssistantId $assistantId,
        public string $message,
        public ?string $additionalInstructions = null
    ) {
    }
}
