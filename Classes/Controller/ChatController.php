<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Cache\Frontend\VariableFrontend;
use Sitegeist\Chatterbox\Application\GetThreadHistory;
use Sitegeist\Chatterbox\Application\Message;
use Sitegeist\Chatterbox\Application\MessageCollection;
use Sitegeist\Chatterbox\Application\StartThread;
use Sitegeist\Chatterbox\Application\StartThreadResponse;
use Sitegeist\Chatterbox\Application\ThreadHistory;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Sitegeist\SchemeOnYou\Application\OpenApiController;
use Sitegeist\SchemeOnYou\Domain\Metadata\HttpMethod;
use Sitegeist\SchemeOnYou\Domain\Metadata\Parameter;
use Sitegeist\SchemeOnYou\Domain\Metadata\Path;
use Sitegeist\SchemeOnYou\Domain\Path\ParameterLocation;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class ChatController extends OpenApiController
{
    protected ?VariableFrontend $metaDataCache = null;

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {
    }

    public function injectMetaDataCache(VariableFrontend $metaDataCache): void
    {
        $this->metaDataCache = $metaDataCache;
    }

    #[Path('/chatterbox/chat/start', HttpMethod::METHOD_POST)]
    public function startEndpoint(
        #[Parameter(ParameterLocation::LOCATION_QUERY)]
        StartThread $command
    ): StartThreadResponse {
        $organization = $this->organizationRepository->findById($command->organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($command->assistantId);
        $threadId = $assistant->startThread();
        $assistant->continueThread($threadId, $command->message);

        $messageResponses = $assistant->readThread($threadId);
        $lastMessageKey = array_key_last($messageResponses);
        $lastMessageId = $messageResponses[$lastMessageKey]->id;
        $lastMessage = $messageResponses[$lastMessageKey];
        $metadata = $assistant->getCollectedMetadata();

        if ($metadata) {
            $this->metaDataCache?->set($this->cacheId($command->assistantId, $threadId, $lastMessageId), $metadata, [$this->cacheTag($command->assistantId, $threadId)], 3600);
        }

        return new StartThreadResponse(
            $threadId,
            empty($metadata) ? null : $metadata,
            $lastMessage->id,
            $lastMessage->role !== 'user',
            $lastMessage->content->toApiArray(),
            $lastMessage->quotations->toApiArray()
        );
    }

    #[Path('/chatterbox/chat/history', HttpMethod::METHOD_GET)]
    public function historyEndpoint(
        #[Parameter(ParameterLocation::LOCATION_QUERY)]
        GetThreadHistory $query
    ): ThreadHistory {
        $organization = $this->organizationRepository->findById($query->organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($query->assistantId);
        $messages = $assistant->readThread($query->threadId);

        $cachedMetadata = $this->metaDataCache?->getByTag($this->cacheTag($query->assistantId, $query->threadId)) ?: [];

        return new ThreadHistory(
            new MessageCollection(...array_map(
                fn (MessageRecord $messageRecord): Message => Message::fromMessageRecordAndMetadata(
                    $messageRecord,
                    $cachedMetadata[$this->cacheId($query->assistantId, $query->threadId, $messageRecord->id)] ?? null
                ),
                $messages
            ))
        );
    }

    //#[Path('/chatterbox/chat/post', HttpMethod::METHOD_POST)]
    public function postAction(string $organizationId, string $assistantId, string $threadId, string $message): string
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);
        $assistant->continueThread($threadId, $message);

        $messageResponses = $assistant->readThread($threadId);
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
