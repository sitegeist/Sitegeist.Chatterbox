<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;

#[Flow\Proxy(false)]
final class MessageRecord
{
    /**
     * @param array<int, ThreadMessageResponseContentImageFileObject|ThreadMessageResponseContentTextObject> $content
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $role,
        public readonly array $content,
        public readonly array $metadata,
    ) {
    }

    public static function fromThreadMessageResponse(ThreadMessageResponse $response): self
    {
        return new self(
            $response->id,
            $response->role,
            $response->content,
            $response->metadata
        );
    }
}
