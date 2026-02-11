<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Security\Cryptography\HashService;
use Sitegeist\Chatterbox\Domain\Assistant;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\AssistantFactory;
use Sitegeist\Chatterbox\Dto\AssistantId;
use Sitegeist\Chatterbox\Dto\HistoryResponse;
use Sitegeist\Chatterbox\Dto\Message;
use Sitegeist\Chatterbox\Dto\MessageCollection;
use Sitegeist\Chatterbox\Dto\MessageId;
use Sitegeist\Chatterbox\Dto\MetaData;
use Sitegeist\Chatterbox\Dto\PostResponse;
use Sitegeist\Chatterbox\Dto\StartChatResponse;
use Sitegeist\Chatterbox\Dto\ThreadId;
use Sitegeist\SchemeOnYou\Application\OpenApiController;

class OpenApiChatController extends OpenApiController
{
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

    public function startAction(AssistantId $assistantId, string $message, ?string $additionalInstructions = null): StartChatResponse
    {
        if ($additionalInstructions !== null && $additionalInstructions !== '') {
            $additionalInstructions = $this->hashService->validateAndStripHmac($additionalInstructions);
        } else {
            $additionalInstructions = null;
        }
        $assistant = $this->getAssistantFromAssistantId($assistantId);
        $threadId = new ThreadId($assistant->startThread());
        $assistant->continueThread($threadId->value, $message, $additionalInstructions);

        $messageResponses = $assistant->readThread($threadId->value);
        $lastMessageKey = array_key_last($messageResponses);

        $responseMessage = Message::fromMessageRecord($messageResponses[$lastMessageKey]);
        $metadata = $assistant->getCollectedMetadata();
        if ($metadata) {
            $this->metaDataCache?->set($this->cacheId($assistantId, $threadId, $responseMessage->id), $metadata, [$this->cacheTag($assistantId, $threadId)], 3600);
            $responseMessage = $responseMessage->withMetadata(MetaData::fromArray($metadata));
        }

        return new StartChatResponse($threadId, $responseMessage);
    }

    public function historyAction(AssistantId $assistantId, ThreadId $threadId): HistoryResponse
    {
        $assistant = $this->getAssistantFromAssistantId($assistantId);
        $threadItems = $assistant->readThread($threadId->value);
        $cachedMetadata = $this->metaDataCache ? $this->metaDataCache->getByTag($this->cacheTag($assistantId, $threadId)) : [];
        $messages = [];
        foreach ($threadItems as $threadItem) {
            $message = Message::fromMessageRecord($threadItem);
            $metadata = $cachedMetadata[$this->cacheId($assistantId, $threadId, $message->id)] ?? null;
            if ($metadata) {
                $message->withMetadata($metadata);
            }
            $messages[] = $message;
        }
        return new HistoryResponse(new MessageCollection(...$messages));
    }

    public function postAction(AssistantId $assistantId, ThreadId $threadId, string $message, ?string $additionalInstructions = null): PostResponse
    {
        if ($additionalInstructions) {
            $additionalInstructions = $this->hashService->validateAndStripHmac($additionalInstructions);
        }

        $assistant = $this->getAssistantFromAssistantId($assistantId);
        $assistant->continueThread($threadId->value, $message, $additionalInstructions);

        $messageResponses = $assistant->readThread($threadId->value);
        $lastMessageKey = array_key_last($messageResponses);
        $responseMessage = Message::fromMessageRecord($messageResponses[$lastMessageKey]);

        $metadata = $assistant->getCollectedMetadata();
        if ($metadata) {
            $this->metaDataCache?->set($this->cacheId($assistantId, $threadId, $responseMessage->id), $metadata, [$this->cacheTag($assistantId, $threadId)], 3600);
            $responseMessage = $responseMessage->withMetadata($metadata);
        }

        return new PostResponse($responseMessage);
    }


    private function cacheTag(AssistantId $assistantId, ThreadId $threadId): string
    {
        return 't_' . md5($assistantId->value . ':' . $threadId->value);
    }

    private function cacheId(AssistantId $assistantId, ThreadId $threadId, MessageId $messageId): string
    {
        return 'm_' . md5($assistantId->value . ':' . $threadId->value . ':' . $messageId->value);
    }

    protected function getAssistantFromAssistantId(AssistantId $assistantId): Assistant
    {
        $assistantEntity = $this->assistantEntityRepository->findByIdentifier($assistantId->value);
        if ($assistantEntity instanceof AssistantEntity) {
            $assistant = $this->assistantFactory->createAssistantFromAssistantEntity($assistantEntity);
            if ($assistant instanceof Assistant) {
                return $assistant;
            }
        }
        throw new \Exception('Assistant not found');
    }
}
