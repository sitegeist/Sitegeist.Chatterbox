<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class AssistantRecord
{

    /**
     * @param string[] $selectedTools
     * @param string[] $selectedFiles
     */
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $instructions,
        public readonly array $selectedTools = [],
        public readonly array $selectedFiles = [],
    ) {
    }

    public static function fromAssistantResponse(AssistantResponse $response): self
    {
        $selectedTools = array_key_exists('selectedTools', $response->metadata) ? json_decode($response->metadata['selectedTools'], true) : [];
        $selectedFiles = array_key_exists('selectedFiles', $response->metadata) ? json_decode($response->metadata['selectedFiles'], true) : [];

        return new self(
            $response->id,
            $response->model,
            $response->name,
            $response->description,
            $response->instructions,
            $selectedTools,
            $selectedFiles,
        );
    }
}
