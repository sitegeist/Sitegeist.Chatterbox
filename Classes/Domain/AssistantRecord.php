<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Assistants\AssistantResponseToolCodeInterpreter;
use OpenAI\Responses\Assistants\AssistantResponseToolFileSearch;
use OpenAI\Responses\Assistants\AssistantResponseToolFunction;

#[Flow\Proxy(false)]
final class AssistantRecord
{
    /**
     * @param mixed[] $tools
     * @param string[] $metadata
     * @param string[] $selectedTools
     * @param string[] $selectedSourcesOfKnowledge
     * @param string[] $selectedInstructions
     * @param mixed[] $toolResources
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
        public readonly array $selectedInstructions = [],
        public readonly array $toolResources = [],
    ) {
    }

    public static function fromAssistantResponse(AssistantResponse $response): self
    {
        $selectedTools = array_key_exists('selectedTools', $response->metadata) ? json_decode($response->metadata['selectedTools'], true) : [];
        $selectedSourcesOfKnowledge = array_key_exists('selectedSourcesOfKnowledge', $response->metadata) ? json_decode($response->metadata['selectedSourcesOfKnowledge'], true) : [];
        $selectedInstructions = array_key_exists('selectedInstructions', $response->metadata) ? json_decode($response->metadata['selectedInstructions'], true) : [];

        return new self(
            $response->id,
            $response->model,
            $response->name,
            $response->description,
            $response->instructions,
            array_map(fn(AssistantResponseToolCodeInterpreter|AssistantResponseToolFileSearch|AssistantResponseToolFunction $item) => $item->toArray(), $response->tools),
            $response->metadata,
            $selectedTools,
            $selectedSourcesOfKnowledge,
            $selectedInstructions,
            $response->toolResources?->toArray() ?: [],
        );
    }
}
