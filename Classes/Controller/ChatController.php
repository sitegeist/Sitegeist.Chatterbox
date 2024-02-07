<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Mvc\Controller\ActionController;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

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
        private readonly OrganizationRepository $organizationRepository,
    ) {
    }

    public function startAction(string $organizationId, string $assistantId, string $message): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);
        $threadId = $assistant->startThread();
        $assistant->continueThread($threadId, $message, true);

        $messageResponses = $assistant->readThread($threadId);
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessage = $messageResponses[$lastMessageKey];
        $metadata = $assistant->getCollectedMetadata();

        $this->view->assignMultiple([
            'value' => array_merge(
                [
                    'threadId' => $threadId,
                    'metadata' => empty($metadata) ? new \stdClass() : $metadata
                ],
                $lastMessage->toApiArray()
            )
        ]);
    }

    public function historyAction(string $organizationId, string $assistantId, string $threadId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);
        $messages = $assistant->readThread($threadId);

        $this->view->assign('value', [
            'messages' => array_map(
                fn (MessageRecord $message): array => $message->toApiArray(),
                $messages
            ),
        ]);
    }

    public function postAction(string $organizationId, string $assistantId, string $threadId, string $message): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);
        $assistant->continueThread($threadId, $message);

        $messageResponses = $assistant->readThread($threadId);
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessage = $messageResponses[$lastMessageKey];
        $metadata = $assistant->getCollectedMetadata();

        $this->view->assignMultiple([
            'value' => array_merge(
                [
                    'metadata' => empty($metadata) ? new \stdClass() : $metadata
                ],
                $lastMessage->toApiArray()
            )
        ]);
    }
}
