<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection as DatabaseConnection;
use OpenAI\Responses\Conversations\ConversationItem;
use OpenAI\Responses\Conversations\Objects\Message;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;

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

    public static function tryFromConversationItem(
        ConversationItem $item,
        SourceOfKnowledgeCollection $sourceOfKnowledgeCollection,
    ): ?self {
        $subject = $item->item;
        if ($subject instanceof Message) {
            return new self(
                $subject->id,
                $subject->role,
                ContentCollection::fromMessageItem($subject),
                $sourceOfKnowledgeCollection->resolveQuotationsForConversationItem($item),
                []
            );
        } return null;
    }

    public static function fromThreadMessageResponse(
        ThreadMessageResponse $response,
        SourceOfKnowledgeCollection $sourceOfKnowledgeCollection,
        OrganizationDiscriminator $organizationDiscriminator,
        DatabaseConnection $connection
    ): self {
        $resolvedAndUnresolvedQuotations = $sourceOfKnowledgeCollection->resolveQuotations(
            $response,
            $organizationDiscriminator,
            $connection
        );
        return new self(
            $response->id,
            $response->role,
            ContentCollection::fromThreadMessageResponse($response, $resolvedAndUnresolvedQuotations->unresolvedQuotations),
            $resolvedAndUnresolvedQuotations->resolvedQuotations,
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
