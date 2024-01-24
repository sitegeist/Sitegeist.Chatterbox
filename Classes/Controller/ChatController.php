<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Mvc\Controller\ActionController;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\MessageRecord;

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
        private readonly AssistantDepartment $assistantDepartment,
    ) {
    }

    public function startAction(string $assistantId, string $message): void
    {
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $threadId = $assistant->startThread();
        $metadata = $assistant->continueThread($threadId, $message, 'current date: ' . (new \DateTimeImmutable())->format('Y-m-d'));

        $messageResponse = $this->client->threads()->messages()->list($threadId)->data;
        /** @var ?ThreadMessageResponse $lastMessage */
        $lastMessage = reset($messageResponse);

        $this->view->assign('value', [
            'bot' => true,
            'message' => $lastMessage?->content ?: '',
            'threadId' => $threadId,
            'metadata' => $metadata
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
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $metadata = $assistant->continueThread($threadId, $message);

        $messageResponse = $this->client->threads()->messages()->list($threadId)->data;
        /** @var ?ThreadMessageResponse $lastMessage */
        $lastMessage = reset($messageResponse);

        $this->view->assign('value', [
            'bot' => true,
            'message' => $lastMessage?->content ?: '',
            'metadata' => $metadata
        ]);
    }
}
