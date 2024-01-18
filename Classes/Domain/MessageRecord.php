<?php
declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;

class MessageRecord
{
    public function __construct(
        public readonly string $id,
        public readonly string $role,
        public readonly array $content,
    ) {
    }

    public static function fromThreadMessageResponse(ThreadMessageResponse $response): static
    {
        return new static(
            $response->id,
            $response->role,
            $response->content
        );
    }
}
