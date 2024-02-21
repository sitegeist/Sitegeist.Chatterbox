<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use League\CommonMark\CommonMarkConverter;

#[Flow\Proxy(false)]
final class MessageRecord
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $role,
        public readonly ContentCollection $content,
        public readonly QuotationCollection $quotations,
        public readonly array $metadata,
    ) {
    }

    public static function fromThreadMessageResponse(
        ThreadMessageResponse $response,
        SourceOfKnowledgeCollection $sourceOfKnowledgeCollection,
        ClientContract $client
    ): self {



        return new self(
            $response->id,
            $response->role,
            ContentCollection::fromThreadMessageResponse($response),
            QuotationCollection::createEmpty(), /*$sourceOfKnowledgeCollection->resolveQuotations($response, $client),*/
            $response->metadata
        );
    }

    /**
     * @return array{bot:boolean, message: array<int, ThreadMessageResponseContentImageFileObject|ThreadMessageResponseContentTextObject>, quotations: array<mixed>}
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'bot' => $this->role !== 'user',
            'message' => $this->content->toApiArray(),
            'quotations' => $this->quotations->toApiArray()
        ];
    }
}
