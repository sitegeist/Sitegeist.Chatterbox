<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class AssistantRecord
{
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $instructions,
    ) {
    }

    public static function fromAssistantResponse(AssistantResponse $response): self
    {
        return new self(
            $response->id,
            $response->model,
            $response->name,
            $response->description,
            $response->instructions
        );
    }
}
