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
     * @var array<int, string>
     */
    protected $supportedMediaTypes = ['application/json'];

    /**
     * @var array<string, string>
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
        $assistant->continueThread($threadId, $message, true);

        $messageResponses = $assistant->readThread($threadId);
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessage = $messageResponses[$lastMessageKey];

        $this->view->assign('value', [
            'bot' => true,
            'message' => $lastMessage->toApiArray(),
            'threadId' => $threadId,
            'metadata' => $assistant->getCollectedMetadata()
        ]);
    }

    public function historyAction(string $assistantId, string $threadId): void
    {
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $messages = $assistant->readThread($threadId);

        $this->view->assign('value', [
            'messages' => array_map(
                fn (MessageRecord $message): array => $message->toApiArray(),
                $messages
            ),
        ]);
    }

    public function postAction(string $assistantId, string $threadId, string $message): void
    {
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $assistant->continueThread($threadId, $message);

        $messageResponses = $assistant->readThread($threadId);
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessage = $messageResponses[$lastMessageKey];

        $this->view->assign('value', [
            'bot' => true,
            'message' => $lastMessage->toApiArray(),
            'metadata' => $assistant->getCollectedMetadata()
        ]);
    }
}
