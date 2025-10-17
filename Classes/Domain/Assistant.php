<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\Utility\Arrays;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Conversations\ConversationItem;
use OpenAI\Responses\Responses\Output\OutputFunctionToolCall;
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
        $tools = $this->prepareTools();
        $instructions = $this->prepareInstructions($additionalInstructions);

        $input = [
            [
                'type' => 'message',
                'role' => 'user',
                'content' => $message
            ]
        ];

        $createResponse = $this->client->responses()->create([
                'conversation' => $threadId,
                'input' => $input,
                'model' => $this->model,
                'instructions' => $instructions,
                'tools' => $tools,
        ]);

        $this->completeRun($threadId, $createResponse->id);
    }

    /**
     * @return array<MessageRecord>
     */
    public function readThread(string $threadId): array
    {
        $conversationItems = $this->client->conversations()->items()->list($threadId);
        // $this->logger?->info("read thread", ["threadId" => $threadId, $conversationItems->data]);
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
        $lastResponse = $this->client->responses()->retrieve($responseId);

        // wait for processing
        while ($lastResponse->status !== 'completed' && $lastResponse->status !== 'failed') {
            sleep(1);
            $lastResponse = $this->client->responses()->retrieve($responseId);
        }

        // perform requested tool calls
        $toolResultMessages = [];
        foreach ($lastResponse->output as $toolCall) {
            if ($toolCall instanceof OutputFunctionToolCall) {
                $this->logger?->info("chatbot tool calls", $toolCall->toArray());
                $toolInstance = $this->tools->getToolByName($toolCall->name);
                if ($toolInstance == null) {
                    continue;
                }
                $toolResult = $toolInstance->execute(json_decode($toolCall->arguments, true));
                $toolResultMessages[] = [
                    'type' => 'function_call_output',
                    'call_id' => $toolCall->callId,
                    'output' => json_encode($toolResult->getData())];
                $this->collectedMetadata = Arrays::arrayMergeRecursiveOverrule($this->collectedMetadata, $toolResult->getMetadata());
            }
        }

        // submit tool results
        if (!empty($toolResultMessages)) {
            $this->logger?->info("chatbot tool submit", $toolResultMessages);
            $createResponse = $this->client->responses()->create([
                'conversation' => $threadId,
                'model' => $this->model,
                'instructions' => $this->prepareInstructions(),
                'tools' => $this->prepareTools(),
                'input' => $toolResultMessages
            ]);
            $responseId = $createResponse->id;
            $lastResponse = $this->client->responses()->retrieve($responseId);
        }

        // wait for final processing ... we may evaluate calling this recursive in future
        while ($lastResponse->status !== 'completed' && $lastResponse->status !== 'failed') {
            sleep(1);
            $lastResponse = $this->client->responses()->retrieve($responseId);
        }

        $this->logger?->info("thread run response", $lastResponse->toArray());
    }

    public function getAvailableModels(): ModelCommunity
    {
        return ModelCommunity::fromApiResponse($this->client->models()->list());
    }

    /**
     * Prepare tools for beeing sent to the open ai client
     * @return array<int, array{type:string, name:string, description:string, parameters ?: mixed[]|null}>
     */
    protected function prepareTools(): array
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
}
