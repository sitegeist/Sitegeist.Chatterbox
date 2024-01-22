<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;

class AssistantDepartment
{
    public function __construct(
        private OpenAiClientContract $client,
    ) {
    }

    public function findAll(): AssistantCollection
    {
        return new AssistantCollection(
            ...array_map(
                fn(AssistantResponse $assistantResponse) => AssistantRecord::fromAssistantResponse($assistantResponse),
                $this->client->assistants()->list()->data
            )
        );
    }

    public function createAssistant(string $name): AssistantRecord
    {
        $assistantResponse = $this->client->assistants()->create(['name' => $name, 'model' => 'gpt-4-1106-preview']);
        return AssistantRecord::fromAssistantResponse($assistantResponse);
    }

    public function findAssistantById(string $assistantId): AssistantRecord
    {
        $assistantResponse = $this->client->assistants()->retrieve($assistantId);
        return AssistantRecord::fromAssistantResponse($assistantResponse);
    }

    public function updateAssistant(AssistantRecord $assistantRecord): void
    {
        $metadata = ['selectedTools' => json_encode($assistantRecord->selectedTools), 'selectedFiles' => json_encode($assistantRecord->selectedFiles)];
        $this->client->assistants()->modify(
            $assistantRecord->id,
            [
                'name' => $assistantRecord->name,
                'description' => $assistantRecord->description,
                'instructions' => $assistantRecord->instructions,
                'metadata' => $metadata
            ]
        );
    }
}
