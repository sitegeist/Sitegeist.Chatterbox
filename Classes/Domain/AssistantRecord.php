<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Assistants\AssistantResponseToolCodeInterpreter;
use OpenAI\Responses\Assistants\AssistantResponseToolFunction;
use OpenAI\Responses\Assistants\AssistantResponseToolRetrieval;

#[Flow\Proxy(false)]
final class AssistantRecord
{
    /**
     * @param mixed[] $tools
     * @param string[] $metadata
     * @param string[] $selectedTools
     * @param string[] $selectedSourcesOfKnowledge
     * @param string[] $fileIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $instructions,
        public readonly ?array $tools = [],
        public readonly ?array $metadata = [],
        public readonly array $selectedTools = [],
        public readonly array $selectedSourcesOfKnowledge = [],
        public readonly array $fileIds = [],
    ) {
    }

    public static function fromAssistantResponse(AssistantResponse $response): self
    {
        $selectedTools = array_key_exists('selectedTools', $response->metadata) ? json_decode($response->metadata['selectedTools'], true) : [];
        $selectedSourcesOfKnowledge = array_key_exists('selectedSourcesOfKnowledge', $response->metadata) ? json_decode($response->metadata['selectedSourcesOfKnowledge'], true) : [];

        return new self(
            $response->id,
            $response->model,
            $response->name,
            $response->description,
            $response->instructions,
            array_map(fn(AssistantResponseToolCodeInterpreter|AssistantResponseToolRetrieval|AssistantResponseToolFunction $item) => $item->toArray(), $response->tools),
            $response->metadata,
            $selectedTools,
            $selectedSourcesOfKnowledge,
            $response->fileIds,
        );
    }
}
