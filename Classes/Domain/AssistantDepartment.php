<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\Flow\Annotations as Flow;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionContract;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgeSourceDiscriminator;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgeSourceName;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreName;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

class AssistantDepartment
{
    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly DatabaseConnection $connection,
        private readonly Toolbox $toolbox,
        private readonly Manual $manual,
        private readonly Library $library,
        private readonly LoggerInterface $logger,
        private readonly OrganizationDiscriminator $organizationDiscriminator,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function findAssistantById(string $assistantId): Assistant
    {
        $assistantRecord = $this->findAssistantRecordById($assistantId);
        $discriminatorName = $assistantRecord->metadata['discriminator'] ?? '';
        if ($this->organizationDiscriminator->equals($discriminatorName) === false) {
            throw new \Exception('Wrong assistant discriminator "' . $discriminatorName . '", I do not dare to use this');
        }

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
                    fn (string $instructionName): ?InstructionContract
                        => $this->manual->findInstructionByName($instructionName),
                    $assistantRecord->selectedInstructions
                )
            )),
            $this->library->findSourcesByNames($assistantRecord->selectedSourcesOfKnowledge),
            $this->organizationDiscriminator,
            $this->client,
            $this->connection,
            ($this->settings['enableLogging'] ?? false) ? $this->logger : null
        );
    }

    public function findAllRecords(): AssistantRecordCollection
    {
        return new AssistantRecordCollection(
            ...array_map(
                fn(AssistantResponse $assistantResponse) => AssistantRecord::fromAssistantResponse($assistantResponse),
                array_filter(
                    $this->client->assistants()->list()->data,
                    fn(AssistantResponse $assistantResponse)
                        => $this->organizationDiscriminator->equals($assistantResponse->metadata['discriminator'] ?? '')
                )
            )
        );
    }

    public function createAssistant(string $name, string $model): AssistantRecord
    {
        $assistantResponse = $this->client->assistants()->create(['model' => $model, 'name' => $name, 'metadata' => ['discriminator' => $this->organizationDiscriminator->value]]);
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
                'name' => $assistantRecord->name ?: '',
                'description' => $assistantRecord->description ?: '',
                'instructions' => $assistantRecord->instructions ?: '',
                'tools' => $this->createToolConfiguration($assistantRecord),
                'tool_resources' => $this->createToolResourcesConfiguration($assistantRecord),
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
            'descriminator' => $this->organizationDiscriminator->value,
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
                'type' => 'file_search'
            ];
        }
        foreach ($assistantRecord->selectedTools as $toolId) {
            $tool = $this->toolbox->findByName($toolId);
            if ($tool instanceof ToolContract) {
                $spec = [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool->getName(),
                        'description' => $tool->getDescription(),
                    ]
                ];
                $parameters = $tool->getParameterSchema();
                if ($parameters !== null) {
                    $spec['function']['parameters'] = $parameters;
                }
                $tools[] = $spec;
            }
        }
        return $tools;
    }

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    private function createToolResourcesConfiguration(AssistantRecord $assistantRecord): array
    {
        $vectorStoreListResponse = $this->client->vectorStores()->list();
        $vectorStoreIds = [];
        foreach ($assistantRecord->selectedSourcesOfKnowledge as $knowledgeSourceName) {
            $knowledgeSourceDiscriminator = new KnowledgeSourceDiscriminator(
                $this->organizationDiscriminator,
                new KnowledgeSourceName($knowledgeSourceName)
            );
            $latestVectorStoreName = null;
            foreach ($vectorStoreListResponse->data as $vectorStoreResponse) {
                $vectorStoreName = VectorStoreName::tryFromNullableString($vectorStoreResponse->name);
                if (!$vectorStoreName?->knowledgeSourceDiscriminator->equals($knowledgeSourceDiscriminator)) {
                    continue;
                }

                if (!$latestVectorStoreName || $vectorStoreName->takesPrecedenceOver($latestVectorStoreName)) {
                    $latestVectorStoreName = $vectorStoreName;
                    $vectorStoreIds[$knowledgeSourceName] = $vectorStoreResponse->id;
                }
            }
        }

        return [
            'file_search' => [
                'vector_store_ids' => array_values($vectorStoreIds)
            ],
        ];
    }
}
