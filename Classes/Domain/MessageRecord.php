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
        public readonly array $metadata,
    ) {
    }

    public static function tryFromConversationItem(
        ConversationItem $item,
        SourceOfKnowledgeCollection $sourceOfKnowledgeCollection,
        bool $allowSystemMessages = false
    ): ?self {
        $subject = $item->item;
        if ($subject instanceof Message) {
            if (in_array($subject->role, ['system', 'developer'])) {
                return null;
            }
            return new self(
                $subject->id,
                $subject->role,
                ContentCollection::fromMessageItem($subject, $sourceOfKnowledgeCollection),
                []
            );
        } return null;
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
            /** @deprecated */
            'quotations' => []
        ];
    }
}
