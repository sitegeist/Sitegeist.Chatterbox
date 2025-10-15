<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\Utility\Arrays;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Conversations\ConversationItem;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionSubmitToolOutputs;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Model\ModelCommunity;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

#[Flow\Proxy(false)]
final class Assistant
{
    /**
     * @var array<int, mixed>
     */
    private array $collectedMetadata = [];

    public function __construct(
        /**@phpstan-ignore-next-line */
        private readonly string $id,
        private readonly string $model,
        /**@phpstan-ignore-next-line */
        private readonly ToolCollection $tools,
        private readonly InstructionCollection $instructions,
        /**@phpstan-ignore-next-line */
        private readonly SourceOfKnowledgeCollection $sourcesOfKnowledge,
        /**@phpstan-ignore-next-line */
        private readonly OrganizationDiscriminator $organizationDiscriminator,
        private readonly OpenAiClientContract $client,
        /**@phpstan-ignore-next-line */
        private readonly DatabaseConnection $connection,
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
        $createResponse = $this->client->responses()->create([
            'conversation' => $threadId,
            'input' => $message,
            'model' => $this->model
        ]);
        $this->completeRun($threadId, $createResponse->id);
    }

    /**
     * @return array<MessageRecord>
     */
    public function readThread(string $threadId): array
    {
        $conversationItems = $this->client->conversations()->items()->list($threadId);
        $this->logger?->info("read thread", ["threadId" => $threadId, $conversationItems->data]);
        return array_filter(
            array_reverse(
                array_map(
                    fn (ConversationItem $conversationItem) => MessageRecord::tryFromConversationItem(
                        $conversationItem
                    ),
                    $conversationItems->data
                )
            )
        );
    }

    private function completeRun(string $threadId, string $responseId): void
    {
        $threadRunResponse = $this->client->responses()->retrieve($responseId);
        while ($threadRunResponse->status !== 'completed' && $threadRunResponse->status !== 'failed') {
//            if ($threadRunResponse->status === 'requires_action') {
//                $submitToolOutputs = $threadRunResponse->requiredAction?->submitToolOutputs;
//                if ($submitToolOutputs instanceof ThreadRunResponseRequiredActionSubmitToolOutputs) {
//                    $this->logger?->info("chatbot tool calls", $submitToolOutputs->toArray());
//                    $toolOutputs = [];
//                    foreach ($submitToolOutputs->toolCalls as $requiredToolCall) {
//                        if ($requiredToolCall instanceof ThreadRunResponseRequiredActionFunctionToolCall) {
//                            $toolInstance = $this->tools->getToolByName($requiredToolCall->function->name);
//                            if ($toolInstance instanceof ToolContract) {
//                                $toolResult = $toolInstance->execute(json_decode($requiredToolCall->function->arguments, true));
//                                $toolOutputs["tool_outputs"][] = ['tool_call_id' => $requiredToolCall->id, 'output' => json_encode($toolResult->getData())];
//                                $this->collectedMetadata = Arrays::arrayMergeRecursiveOverrule($this->collectedMetadata, $toolResult->getMetadata());
//                            }
//                        }
//                    }
//                    $this->logger?->info("chatbot tool submit", $toolOutputs);
//                    if (!empty($toolOutputs)) {
//                        $this->client->threads()->runs()->submitToolOutputs($threadId, $responseId, $toolOutputs);
//                    }
//                }
//            }
            sleep(1);
            $threadRunResponse = $this->client->responses()->retrieve($responseId);
        }
        $this->logger?->info("thread run response", $threadRunResponse->toArray());
    }

    public function getAvailableModels(): ModelCommunity
    {
        return ModelCommunity::fromApiResponse($this->client->models()->list());
    }
}
