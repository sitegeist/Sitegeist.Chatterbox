<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection as DatabaseConnection;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;

#[Flow\Proxy(false)]
final class MessageRecord
{
    /**
     * @param array<int, ThreadMessageResponseContentImageFileObject|ThreadMessageResponseContentTextObject> $content
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly string $role,
        public readonly array $content,
        public readonly QuotationCollection $quotations,
        public readonly array $metadata,
    ) {
    }

    public static function fromThreadMessageResponse(
        ThreadMessageResponse $response,
        SourceOfKnowledgeCollection $sourceOfKnowledgeCollection,
        OrganizationDiscriminator $organizationDiscriminator,
        DatabaseConnection $connection
    ): self {
        return new self(
            $response->id,
            $response->role,
            $response->content,
            $sourceOfKnowledgeCollection->resolveQuotations(
                $response,
                $organizationDiscriminator,
                $connection
            ),
            $response->metadata
        );
    }

    /**
     * @return array{bot:boolean, message: array<int, ThreadMessageResponseContentImageFileObject|ThreadMessageResponseContentTextObject>, quotations: array<mixed>}
     */
    public function toApiArray(): array
    {
        return [
            'bot' => $this->role !== 'user',
            'message' => $this->content,
            'quotations' => $this->quotations->toApiArray()
        ];
    }
}
