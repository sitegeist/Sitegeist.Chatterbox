<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Conversations\ConversationItem;
use OpenAI\Responses\Responses\Output\OutputFunctionToolCall;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReference;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReferenceRepository;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Model\ModelCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;

#[Flow\Proxy(false)]
final class Assistant
{
    private MetaDataCollection $collectedMetadata;

    public function __construct(
        private readonly AssistantEntity $entity,
        private readonly ToolCollection $tools,
        private readonly InstructionCollection $instructions,
        private readonly SourceOfKnowledgeCollection $sourcesOfKnowledge,
        private readonly OpenAiClientContract $client,
        private readonly VectorStoreReferenceRepository $vectorStoreReferenceRepository,
        private readonly ?LoggerInterface $logger,
    ) {
        $this->collectedMetadata = MetaDataCollection::createEmpty();
    }

    public function getCollectedMetadata(): MetaDataCollection
    {
        return $this->collectedMetadata;
    }

    public function startThread(): string
    {
        $items = [
            [
                'type' => 'message',
                'role' => 'system',
                'content' => $this->instructions->getContent()
            ]
        ];
        $conversationResponse = $this->client->conversations()->create(['items' => $items]);
        return $conversationResponse->id;
    }

    public function continueThread(string $threadId, string $message, ?string $additionalInstructions = null): void
    {
        $functionTools = $this->prepareFunctionTools();
        $fileSearchTools = $this->prepareFileSearchTools();
        $instructions = $this->prepareInstructions($additionalInstructions);

        $input = [
            [
                'type' => 'message',
                'role' => 'user',
                'content' => $message
            ]
        ];

        $responseParameters = [
            'conversation' => $threadId,
            'input' => $input,
            'model' => $this->entity->getModel(),
            'instructions' => $instructions,
            'tools' => array_merge($functionTools, $fileSearchTools),
            'include' => count($fileSearchTools) > 0 ? ['file_search_call.results'] : []
        ];

        $this->logger?->info("thread create response", $responseParameters);

        $createResponse = $this->client->responses()->create($responseParameters);


        $this->completeRun($threadId, $createResponse->id);
    }

    /**
     * @return array<MessageRecord>
     */
    public function readThread(string $threadId, bool $allowSystemMessages = false): array
    {
        $conversationItems = $this->client->conversations()->items()->list($threadId)->data;

        return array_values(
            array_filter(
                array_reverse(
                    array_map(
                        fn (ConversationItem $conversationItem) => MessageRecord::tryFromConversationItem(
                            $conversationItem,
                            $this->sourcesOfKnowledge,
                            $allowSystemMessages
                        ),
                        $conversationItems
                    )
                )
            )
        );
    }

    public function readLastMessageFromThread(string $threadId): ?MessageRecord
    {
        $lastId = $this->client->conversations()->items()->list($threadId)->lastId;
        if ($lastId) {
            $conversationItem = $this->client->conversations()->items()->retrieve($threadId, $lastId);
            return MessageRecord::tryFromConversationItem(
                $conversationItem,
                $this->sourcesOfKnowledge
            );
        }
        return null;
    }

    private function completeRun(string $threadId, string $responseId): void
    {
        $lastResponseId = $responseId;
        $lastResponse = $this->client->responses()->retrieve($lastResponseId);

        // wait for processing
        while ($lastResponse->status !== 'completed' && $lastResponse->status !== 'failed') {
            sleep(10);
            $lastResponse = $this->client->responses()->retrieve($lastResponseId);
        }

        /**
         * @var OutputFunctionToolCall[] $pendingToolCalls
         */
        $pendingToolCalls = array_filter($lastResponse->output, fn($thing) => ($thing instanceof OutputFunctionToolCall));

        while (count($pendingToolCalls) > 0) {
            // perform requested tool calls
            $toolResultMessages = [];
            while (count($pendingToolCalls) > 0) {
                $toolCall = array_shift($pendingToolCalls);
                $this->logger?->info("chatbot tool calls", $toolCall->toArray());
                $toolInstance = $this->tools->getToolByName($toolCall->name);
                if ($toolInstance == null) {
                    $this->logger?->info("chatbot tool was missing", [$toolCall->name]);
                    $toolResultMessages[] = [
                        'type' => 'function_call_output',
                        'call_id' => $toolCall->callId,
                        'output' => json_encode(['success' => false, 'message' => 'tool ' . $toolCall->name . ' was missing'])];
                    continue;
                }
                try {
                    $toolResult = $toolInstance->execute(json_decode($toolCall->arguments, true));
                } catch (\Exception $e) {
                    $this->logger?->info("chatbot tool " . $toolCall->name . " failed missing", [$toolCall->name, $toolCall->arguments, $e->getMessage()]);
                    $toolResultMessages[] = [
                        'type' => 'function_call_output',
                        'call_id' => $toolCall->callId,
                        'output' => json_encode(['success' => false, 'message' => 'tool ' . $toolCall->name . ' was missing'])];
                    continue;
                }
                $toolResultMessages[] = [
                    'type' => 'function_call_output',
                    'call_id' => $toolCall->callId,
                    'output' => json_encode($toolResult->getData())];
                $this->collectedMetadata = $this->collectedMetadata->add($toolResult->getMetadata());
            }

            if (empty($toolResultMessages)) {
                break;
            }

            // submit tool results and wait for final processing
            $this->logger?->info("chatbot tool submit", $toolResultMessages);
            $createResponse = $this->client->responses()->create([
                'conversation' => $threadId,
                'model' => $this->entity->getModel(),
                'instructions' => $this->prepareInstructions(),
                'tools' => $this->prepareFunctionTools(),
                'input' => $toolResultMessages
            ]);
            $lastResponseId = $createResponse->id;
            $lastResponse = $this->client->responses()->retrieve($lastResponseId);

            while ($lastResponse->status !== 'completed' && $lastResponse->status !== 'failed') {
                sleep(10);
                $lastResponse = $this->client->responses()->retrieve($lastResponseId);
            }

            // check for consecutive tool calls in the last response
            $pendingToolCalls = array_filter($lastResponse->output, fn($thing) => ($thing instanceof OutputFunctionToolCall));
        }

        $this->logger?->info("thread run response", $lastResponse->toArray());
    }

    public function getAvailableModels(): ModelCollection
    {
        return ModelCollection::fromApiResponse($this->client->models()->list());
    }

    /**
     * Prepare tools for beeing sent to the open ai client
     * @return array<int, array{type:string, name: string, description: string, parameters ?: mixed[]|null}>
     */
    protected function prepareFunctionTools(): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $spec = [
                'type' => 'function',
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
            ];
            $parameters = $tool->getParameterSchema();
            if ($parameters !== null) {
                $spec[ 'parameters' ] = $parameters;
            }
            $tools[] = $spec;
        }

        return $tools;
    }


    /**
     * Prepare knowledge for beeing sent to the open ai client
     *
     * @return array<int, array{type:string, vector_store_ids: string[]}>
     */
    protected function prepareFileSearchTools(): array
    {
        $tools = [];

        $vectorStoreIds = [];
        foreach ($this->sourcesOfKnowledge as $sourceOfKnowledge) {
            $reference = $this->vectorStoreReferenceRepository->findOneByAssistantAndKnowledgeSourceIdentifier(
                $this->entity->getAccount(),
                $sourceOfKnowledge->getName()->value,
            );
            if ($reference instanceof VectorStoreReference) {
                $vectorStoreIds[] = $reference->vectorStoreId;
            }
        }

        if (count($vectorStoreIds) > 0) {
            $tools[] = [
                'type' => 'file_search',
                'vector_store_ids' => $vectorStoreIds
            ];
        }

        return $tools;
    }

    /**
     * @param string|null $additionalInstructions
     * @return string
     */
    protected function prepareInstructions(?string $additionalInstructions = null): string
    {
        $instructions = sprintf('The current date and time is %s', (new \DateTimeImmutable())->format('D Y-m-d H:i'));
        if ($additionalInstructions !== null) {
            $instructions .= PHP_EOL . $additionalInstructions;
        }
        return $instructions;
    }

    public function getAccount(): string
    {
        return $this->entity->getAccount();
    }
}
