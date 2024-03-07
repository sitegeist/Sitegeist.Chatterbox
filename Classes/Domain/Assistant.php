<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\Utility\Arrays;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionSubmitToolOutputs;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

#[Flow\Proxy(false)]
final class Assistant
{
    /**
     * @var array<string, mixed>
     */
    private array $collectedMetadata = [];

    public function __construct(
        private readonly AssistantId $id,
        private readonly ToolCollection $tools,
        private readonly InstructionCollection $instructions,
        private readonly SourceOfKnowledgeCollection $sourcesOfKnowledge,
        private readonly OrganizationDiscriminator $organizationDiscriminator,
        private readonly OpenAiClientContract $client,
        private readonly DatabaseConnection $connection,
        private readonly ?LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function getCollectedMetadata(): array
    {
        return $this->collectedMetadata;
    }

    public function startThread(): string
    {
        $threadResponse = $this->client->threads()->create([]);
        return $threadResponse->id;
    }

    public function continueThread(string $threadId, string $message): void
    {
        $this->client->threads()->messages()->create(
            $threadId,
            [
                'role' => 'user',
                'content' => $message
            ]
        );

        $runResponse = $this->client->threads()->runs()->create(
            $threadId,
            array_filter([
                'assistant_id' => $this->id,
                'additional_instructions' => $this->instructions->getContent()
            ])
        );
        $this->completeRun($threadId, $runResponse->id);
    }

    /**
     * @return array<MessageRecord>
     */
    public function readThread(string $threadId): array
    {
        $threadMessageResponses = $this->client->threads()->messages()->list($threadId)->data;

        $threadMessageResponsesFiltered = array_filter(
            $threadMessageResponses,
            fn (ThreadMessageResponse $threadMessageResponse)
                => ($threadMessageResponse->metadata['role'] ?? null) !== 'system'
        );

        return array_reverse(
            array_map(
                fn (ThreadMessageResponse $threadMessageResponse) => MessageRecord::fromThreadMessageResponse(
                    $threadMessageResponse,
                    $this->sourcesOfKnowledge,
                    $this->organizationDiscriminator,
                    $this->connection
                ),
                $threadMessageResponsesFiltered
            )
        );
    }

    private function completeRun(string $threadId, string $runId): void
    {
        $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        while ($threadRunResponse->status !== 'completed' && $threadRunResponse->status !== 'failed') {
            if ($threadRunResponse->status === 'requires_action') {
                $submitToolOutputs = $threadRunResponse->requiredAction?->submitToolOutputs;
                if ($submitToolOutputs instanceof ThreadRunResponseRequiredActionSubmitToolOutputs) {
                    $this->logger?->info("chatbot tool calls", $submitToolOutputs->toArray());
                    $toolOutputs = [];
                    foreach ($submitToolOutputs->toolCalls as $requiredToolCall) {
                        if ($requiredToolCall instanceof ThreadRunResponseRequiredActionFunctionToolCall) {
                            $toolInstance = $this->tools->getToolByName($requiredToolCall->function->name);
                            if ($toolInstance instanceof ToolContract) {
                                $toolResult = $toolInstance->execute(json_decode($requiredToolCall->function->arguments, true));
                                $toolOutputs["tool_outputs"][] = ['tool_call_id' => $requiredToolCall->id, 'output' => json_encode($toolResult->getData())];
                                $this->collectedMetadata = Arrays::arrayMergeRecursiveOverrule($this->collectedMetadata, ['tool_calls' => ['name' => $requiredToolCall->function->name, 'parameters' => $requiredToolCall->function->arguments]]);
                                $this->collectedMetadata = Arrays::arrayMergeRecursiveOverrule($this->collectedMetadata, $toolResult->getMetadata());
                            }
                        }
                    }
                    $this->logger?->info("chatbot tool submit", $toolOutputs);
                    if (!empty($toolOutputs)) {
                        $this->client->threads()->runs()->submitToolOutputs($threadId, $runId, $toolOutputs);
                    }
                }
            }
            sleep(1);
            $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        }
        $this->logger?->info("thread run response", $threadRunResponse->toArray());
    }

//        // add tool metadata
//        if ($combinedMetadata) {
//            $stepList = $this->client->threads()->runs()->steps()->list($threadId, $threadRunResponse->id);
//            foreach ($stepList->data as $stepResponse) {
//                $stepDetails = $stepResponse->stepDetails;
//                if ($stepDetails instanceof ThreadRunStepResponseMessageCreationStepDetails) {
//                    $messageId = $stepDetails->messageCreation->messageId;
//                    if ($messageId) {
//                        $this->client->threads()->messages()->modify($threadId, $messageId, ['metadata' => ['tools' => json_encode($combinedMetadata)]]);
//                        break;
//                    }
//                }
//            }
//        }
}
