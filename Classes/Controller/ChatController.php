<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Mvc\Controller\ActionController;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;

class ChatController extends ActionController
{
    /**
     * @var array
     */
    protected $supportedMediaTypes = ['application/json'];

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = ['json' => 'Neos\Flow\Mvc\View\JsonView'];

    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly Toolbox $toolbox,
    ) {
    }

    public function startAction(string $assistantId, string $message): void
    {
        $runResponse = $this->client->threads()->createAndRun([
            'assistant_id' => $assistantId,
            'thread' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ]
            ]
        ]);

        $this->view->assign('value', [
           'threadId' => $runResponse->threadId
        ]);
    }

    public function historyAction(string $threadId): void
    {
        $data = $this->client->threads()->messages()->list($threadId)->data;

        $messages = array_reverse(array_map(
            fn (ThreadMessageResponse $threadMessageResponse) => MessageRecord::fromThreadMessageResponse($threadMessageResponse),
            $data
        ));

        $this->view->assign('value', [
            'messages' => array_map(
                fn (MessageRecord $message): array => [
                    'bot' => $message->role !== 'user',
                    'message' => $message->content,
                ],
                $messages
            ),
        ]);
    }

    public function postAction(string $assistantId, string $threadId, string $message): void
    {
        $this->client->threads()->messages()->create(
            $threadId,
            [
                'role' => 'user',
                'content' => $message
            ]
        );
        $runResponse = $this->client->threads()->runs()->create($threadId, ['assistant_id' => $assistantId]);
        $this->waitForRun($threadId, $runResponse->id);
        $messageResponse = $this->client->threads()->messages()->list($threadId)->data;
        /** @var ?ThreadMessageResponse $lastMessage */
        $lastMessage = reset($messageResponse);

        $this->view->assign('value', [
            'bot' => true,
            'message' => $lastMessage?->content ?: ''
        ]);
    }

    private function waitForRun(string $threadId, string $runId): void
    {
        $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        while ($threadRunResponse->status !== 'completed') {
            if ($threadRunResponse->status === 'requires_action') {
                if ($threadRunResponse->requiredAction) {
                    $toolOutputs = [];
                    foreach ($threadRunResponse->requiredAction->submitToolOutputs->toolCalls as $requiredToolCall) {
                        if ($requiredToolCall instanceof ThreadRunResponseRequiredActionFunctionToolCall) {
                            $toolInstance = $this->toolbox->findByName($requiredToolCall->function->name);
                            if ($toolInstance instanceof ToolContract) {
                                $toolResult = $toolInstance->execute(json_decode($requiredToolCall->function->arguments, true));
                                $toolOutputs["tool_outputs"][] = ['tool_call_id' => $requiredToolCall->id, 'output' => json_encode($toolResult)];
                            }
                        }
                    }
                    $this->client->threads()->runs()->submitToolOutputs($threadId, $runId, $toolOutputs);
                }
            }
            sleep(1);
            $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        }
    }
}
