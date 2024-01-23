<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

class AssistantDepartment
{
    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly Toolbox $toolbox,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findAssistantById(string $assistantId): ?Assistant
    {
        $assistantRecord = $this->findAssistantRecordById($assistantId);
        if (!$assistantRecord) {
            return null;
        }

        return new Assistant(
            $assistantId,
            new ToolCollection(...array_filter(
                array_map(
                    fn (string $toolName): ?ToolContract => $this->toolbox->findByName($toolName),
                    $assistantRecord->selectedTools
                )
            )),
            $this->client,
            $this->logger
        );
    }

    public function findAllRecords(): AssistantRecordCollection
    {
        return new AssistantRecordCollection(
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

    public function findAssistantRecordById(string $assistantId): AssistantRecord
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
        return [
            'selectedTools' => json_encode($assistantRecord->selectedTools),
            'selectedSourcesOfKnowledge' => json_encode($assistantRecord->selectedSourcesOfKnowledge)
        ];
    }

    /**
     * @return mixed[]
     */
    private function createToolConfiguration(AssistantRecord $assistantRecord): array
    {
        $tools = [];
        if (!empty($assistantRecord->selectedSourcesOfKnowledge)) {
            $tools[] = [
                'type' => 'retrieval'
            ];
        }
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
