<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionContract;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgeFilename;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

class AssistantDepartment
{
    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly Toolbox $toolbox,
        private readonly Manual $manual,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findAssistantById(string $assistantId): Assistant
    {
        $assistantRecord = $this->findAssistantRecordById($assistantId);

        return new Assistant(
            $assistantId,
            new ToolCollection(...array_filter(
                array_map(
                    fn (string $toolName): ?ToolContract => $this->toolbox->findByName($toolName),
                    $assistantRecord->selectedTools
                )
            )),
            new InstructionCollection(...array_filter(
                array_map(
                    fn (string $instructionName): ?InstructionContract => $this->manual->findInstructionByName($instructionName),
                    $assistantRecord->selectedInstructions
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

    public function createAssistant(string $name, string $model): AssistantRecord
    {
        $assistantResponse = $this->client->assistants()->create(['model' => $model, 'name' => $name]);
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
                'model' => $assistantRecord->model,
                'name' => $assistantRecord->name,
                'description' => $assistantRecord->description,
                'instructions' => $assistantRecord->instructions,
                'tools' => $this->createToolConfiguration($assistantRecord),
                'file_ids' => $this->createFileIdConfiguration($assistantRecord),
                'metadata' => $this->createMetadataConfiguration($assistantRecord),
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
            'selectedSourcesOfKnowledge' => json_encode($assistantRecord->selectedSourcesOfKnowledge),
            'selectedInstructions' => json_encode($assistantRecord->selectedInstructions),
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

    /**
     * @return string[]
     */
    private function createFileIdConfiguration(AssistantRecord $assistantRecord): array
    {
        $fileListResponse = $this->client->files()->list();
        $fileIds = [];
        foreach ($assistantRecord->selectedSourcesOfKnowledge as $knowledgeSourceName) {
            $latestFilename = null;
            foreach ($fileListResponse->data as $fileResponse) {
                $knowledgeFilename = KnowledgeFilename::tryFromSystemFileName($fileResponse->filename);
                if ($knowledgeFilename?->takesPrecedenceOver($latestFilename, $knowledgeSourceName)) {
                    $latestFilename = $knowledgeFilename;
                    $fileIds[$knowledgeSourceName] = $fileResponse->id;
                }
            }
        }
        return array_values($fileIds);
    }
}
