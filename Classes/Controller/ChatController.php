<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Security\Cryptography\HashService;
use Sitegeist\Chatterbox\Domain\Assistant;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\AssistantFactory;
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

    protected ?VariableFrontend $metaDataCache = null;

    public function __construct(
        private readonly AssistantEntityRepository $assistantEntityRepository,
        private readonly AssistantFactory $assistantFactory,
        private readonly HashService $hashService,
    ) {
    }

    public function injectMetaDataCache(VariableFrontend $metaDataCache): void
    {
        $this->metaDataCache = $metaDataCache;
    }

    public function startAction(string $assistantId, string $message, ?string $additionalInstructions = null): string
    {
        if ($additionalInstructions) {
            $additionalInstructions = $this->hashService->validateAndStripHmac($additionalInstructions);
        }

        $assistant = $this->getAssistantFromAsstantId($assistantId);
        $threadId = $assistant->startThread();
        $assistant->continueThread($threadId, $message, $additionalInstructions);

        $lastMessage = $assistant->readLastMessageFromThread($threadId);
        $metadata = $assistant->getCollectedMetadata();

        if ($lastMessage && $metadata) {
            $this->metaDataCache?->set($this->cacheId($assistantId, $threadId, $lastMessage->id), $metadata, [$this->cacheTag($assistantId, $threadId)], 3600);
        }

        return json_encode(
            array_merge(
                [
                    'threadId' => $threadId,
                    'metadata' => empty($metadata) ? null : $metadata
                ],
                $lastMessage?->toApiArray() ?: []
            ),
            JSON_THROW_ON_ERROR
        );
    }

    public function historyAction(string $assistantId, string $threadId): string
    {
        $assistant = $this->getAssistantFromAsstantId($assistantId);
        $messages = $assistant->readThread($threadId);

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

    public function postAction(string $assistantId, string $threadId, string $message, ?string $additionalInstructions = null): string
    {
        if ($additionalInstructions) {
            $additionalInstructions = $this->hashService->validateAndStripHmac($additionalInstructions);
        }

        $assistant = $this->getAssistantFromAsstantId($assistantId);
        $assistant->continueThread($threadId, $message, $additionalInstructions);

        $lastMessage = $assistant->readLastMessageFromThread($threadId);
        $metadata = $assistant->getCollectedMetadata();

        if ($lastMessage && $metadata) {
            $this->metaDataCache?->set($this->cacheId($assistantId, $threadId, $lastMessage->id), $metadata, [$this->cacheTag($assistantId, $threadId)], 3600);
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

    /**
     * @param string $assistantId
     * @return Assistant
     * @throws \Exception
     */
    protected function getAssistantFromAsstantId(string $assistantId): Assistant
    {
        $assistantEntity = $this->assistantEntityRepository->findByIdentifier($assistantId);
        if ($assistantEntity instanceof AssistantEntity) {
            $assistant = $this->assistantFactory->createAssistantFromAssistantEntity($assistantEntity);
            if ($assistant instanceof Assistant) {
                return $assistant;
            }
        }
        throw new \Exception('Assistant not found');
    }
}
