<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionSubmitToolOutputs;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

#[Flow\Proxy(false)]
final class Assistant
{
    public function __construct(
        private readonly string $id,
        private readonly ToolCollection $tools,
        private readonly InstructionCollection $instructions,
        private readonly OpenAiClientContract $client,
        private readonly ?LoggerInterface $logger,
    ) {
    }

    public function startThread(): string
    {
        $runResponse = $this->client->threads()->createAndRun([
            'assistant_id' => $this->id,
            'thread' => [
                'messages' => []
            ]
        ]);
        $this->completeRun($runResponse->threadId, $runResponse->id);

        return $runResponse->threadId;
    }

    /**
     * @return array<int,mixed>
     */
    public function continueThread(string $threadId, string $message, bool $withAdditionalInstructions = false): array
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
        return $this->completeRun($threadId, $runResponse->id);
    }

    /**
     * @return array<int,mixed>
     */
    private function completeRun(string $threadId, string $runId): array
    {
        $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        $combinedMetadata = [];
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
                                $combinedMetadata[] = [
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

        return $combinedMetadata;

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
