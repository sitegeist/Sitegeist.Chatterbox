<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use Sitegeist\Chatterbox\Tools\ToolContract;

class AssistantDepartment
{
    public function __construct(
        private OpenAiClientContract $client,
        private Toolbox $toolbox,
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
        $this->client->assistants()->modify(
            $assistantRecord->id,
            [
                'name' => $assistantRecord->name,
                'description' => $assistantRecord->description,
                'instructions' => $assistantRecord->instructions,
                'tools' => $this->createToolConfiguration($assistantRecord),
                'metadata' => $this->createMetadataConfiguration($assistantRecord)
            ]
        );
    }

    /**
     * @return mixed[]
     */
    private function createMetadataConfiguration(AssistantRecord $assistantRecord): array
    {
        return ['selectedTools' => json_encode($assistantRecord->selectedTools), 'selectedFiles' => json_encode($assistantRecord->selectedFiles)];
    }

    /**
     * @return mixed[]
     */
    private function createToolConfiguration(AssistantRecord $assistantRecord): array
    {
        $tools = [];
        foreach ($assistantRecord->selectedTools as $toolId) {
            $tool = $this->toolbox->findByName($toolId);
            if ($tool instanceof ToolContract) {
                $tools[] = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool->getName(),
                        'description' => $tool->getDescription(),
                        'parameters' => $tool->getParameterSchema(),
                    ]
                ];
            }
        }
        return $tools;
    }
}
