<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Responses\Assistants\AssistantResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Assistants\AssistantResponseToolCodeInterpreter;
use OpenAI\Responses\Assistants\AssistantResponseToolFunction;
use OpenAI\Responses\Assistants\AssistantResponseToolRetrieval;

#[Flow\Proxy(false)]
final readonly class AssistantRecord
{
    /**
     * @param mixed[] $tools
     * @param string[] $metadata
     * @param string[] $selectedTools
     * @param string[] $selectedSourcesOfKnowledge
     * @param string[] $selectedInstructions
     * @param string[] $fileIds
     */
    public function __construct(
        public AssistantId $id,
        public string $model,
        public ?string $name,
        public ?string $description,
        public ?string $instructions,
        public ?array $tools = [],
        public ?array $metadata = [],
        public array $selectedTools = [],
        public array $selectedSourcesOfKnowledge = [],
        public array $selectedInstructions = [],
        public array $fileIds = [],
    ) {
    }

    public static function fromAssistantResponse(AssistantResponse $response): self
    {
        $selectedTools = array_key_exists('selectedTools', $response->metadata) ? json_decode($response->metadata['selectedTools'], true) : [];
        $selectedSourcesOfKnowledge = array_key_exists('selectedSourcesOfKnowledge', $response->metadata) ? json_decode($response->metadata['selectedSourcesOfKnowledge'], true) : [];
        $selectedInstructions = array_key_exists('selectedInstructions', $response->metadata) ? json_decode($response->metadata['selectedInstructions'], true) : [];

        return new self(
            new AssistantId($response->id),
            $response->model,
            $response->name,
            $response->description,
            $response->instructions,
            array_map(fn(AssistantResponseToolCodeInterpreter|AssistantResponseToolRetrieval|AssistantResponseToolFunction $item) => $item->toArray(), $response->tools),
            $response->metadata,
            $selectedTools,
            $selectedSourcesOfKnowledge,
            $selectedInstructions,
            $response->fileIds,
        );
    }
}
