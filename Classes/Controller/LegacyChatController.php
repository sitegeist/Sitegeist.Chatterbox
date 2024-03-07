<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Mvc\Controller\ActionController;
use Sitegeist\Chatterbox\Domain\AssistantId;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\OrganizationId;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Sitegeist\Chatterbox\Domain\ThreadId;

class LegacyChatController extends ActionController
{
    /**
     * @var array<int, string>
     */
    protected $supportedMediaTypes = ['application/json'];

    /**
     * @var array<string, string>
     */
    protected $viewFormatToObjectNameMap = ['json' => 'Neos\Flow\Mvc\View\JsonView'];

    protected ?VariableFrontend $metaDataCache = null;

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {
    }

    public function injectMetaDataCache(VariableFrontend $metaDataCache): void
    {
        $this->metaDataCache = $metaDataCache;
    }

    public function startAction(string $organizationId, string $assistantId, string $message, ?string $additionalInstructions = null): string
    {
        $organization = $this->organizationRepository->findById(new OrganizationId($organizationId));
        $assistant = $organization->assistantDepartment->findAssistantById(new AssistantId($assistantId));
        $threadId = $assistant->startThread();
        $assistant->continueThread($threadId, $message);

        $messageResponses = $assistant->readThread($threadId);
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessageId = $messageResponses[$lastMessageKey]->id;
        $lastMessage = $messageResponses[$lastMessageKey];
        $metadata = $assistant->getCollectedMetadata();

        if ($metadata) {
            $this->metaDataCache?->set($this->cacheId($assistantId, $threadId->value, $lastMessageId), $metadata, [$this->cacheTag($assistantId, $threadId->value)], 3600);
        }

        return json_encode(
            array_merge(
                [
                    'threadId' => $threadId,
                    'metadata' => empty($metadata) ? null : $metadata
                ],
                $lastMessage->toApiArray()
            ),
            JSON_THROW_ON_ERROR
        );
    }

    public function historyAction(string $organizationId, string $assistantId, string $threadId): string
    {
        $organization = $this->organizationRepository->findById(new OrganizationId($organizationId));
        $assistant = $organization->assistantDepartment->findAssistantById(new AssistantId($assistantId));
        $messages = $assistant->readThread(new ThreadId($threadId));

        $cachedMetadata = $this->metaDataCache ? $this->metaDataCache->getByTag($this->cacheTag($assistantId, $threadId)) : [];

        return json_encode(
            [
                'messages' => array_map(
                    function (MessageRecord $message) use ($cachedMetadata, $assistantId, $threadId): array {
                        return array_merge(
                            [
                                'metadata' => $cachedMetadata[$this->cacheId($assistantId, $threadId, $message->id)] ?? null
                            ],
                            $message->toApiArray()
                        );
                    },
                    $messages
                ),
            ],
            JSON_THROW_ON_ERROR
        );
    }

    public function postAction(string $organizationId, string $assistantId, string $threadId, string $message, ?string $additionalInstructions = null): string
    {
        $organization = $this->organizationRepository->findById(new OrganizationId($organizationId));
        $assistant = $organization->assistantDepartment->findAssistantById(new AssistantId($assistantId));
        $assistant->continueThread(new ThreadId($threadId), $message);

        $messageResponses = $assistant->readThread(new ThreadId($threadId));
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessageId = $messageResponses[$lastMessageKey]->id;
        $lastMessage = $messageResponses[$lastMessageKey];
        $metadata = $assistant->getCollectedMetadata();

        if ($metadata) {
            $this->metaDataCache?->set($this->cacheId($assistantId, $threadId, $lastMessageId), $metadata, [$this->cacheTag($assistantId, $threadId)], 3600);
        }

        return json_encode(
            array_merge(
                [
                    'metadata' => empty($metadata) ? null : $metadata
                ],
                $lastMessage->toApiArray()
            ),
            JSON_THROW_ON_ERROR
        );
    }

    private function cacheTag(string $assistantId, string $threadId): string
    {
        return 't_' . md5($assistantId . ':' . $threadId);
    }

    private function cacheId(string $assistantId, string $threadId, string $messageId): string
    {
        return 'm_' . md5($assistantId . ':' . $threadId . ':' . $messageId);
    }
}