<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionSubmitToolOutputs;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

#[Flow\Proxy(false)]
final class Assistant
{
    /**
     * @var array<int, mixed>
     */
    private $collectedMetadata = [];

    public function __construct(
        private readonly string $id,
        private readonly ToolCollection $tools,
        private readonly InstructionCollection $instructions,
        private readonly OpenAiClientContract $client,
        private readonly ?LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int,mixed>
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

    public function continueThread(string $threadId, string $message, bool $withAdditionalInstructions = false): void
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
                'additional_instructions' => $withAdditionalInstructions
                    ? $this->instructions->getContent()
                    : null
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

        $threadMessageResponsesFiltered =  array_filter(
            $threadMessageResponses,
            fn(ThreadMessageResponse $threadMessageResponse) => ($threadMessageResponse->metadata['role'] ?? null) !== 'system'
        );

        return array_reverse(
            array_map(
                fn(ThreadMessageResponse $threadMessageResponse) => MessageRecord::fromThreadMessageResponse($threadMessageResponse),
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
                                $this->collectedMetadata[] = [
                                    'tool_name' => $requiredToolCall->function->name,
                                    'tool_call_id' => $requiredToolCall->id,
                                    'data' => $toolResult->getData(),
                                    'metadata' => $toolResult->getMetadata()
                                ];
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
}
