<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Conversations\ConversationItem;
use OpenAI\Responses\Conversations\Objects\Message;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;

#[Flow\Proxy(false)]
final class MessageRecord
{
    public function __construct(
        public readonly string $id,
        public readonly string $role,
        public readonly ContentCollection $content,
        public readonly MetaDataCollection $metadata,
    ) {
    }

    public static function tryFromConversationItem(
        ConversationItem $item,
        SourceOfKnowledgeCollection $sourceOfKnowledgeCollection,
        bool $allowSystemMessages = false
    ): ?self {
        $subject = $item->item;
        if ($subject instanceof Message) {
            if ($allowSystemMessages === false && in_array($subject->role, ['system', 'developer'])) {
                return null;
            }
            return new self(
                $subject->id,
                $subject->role,
                ContentCollection::fromMessageItem($subject, $sourceOfKnowledgeCollection),
                new MetaDataCollection()
            );
        } return null;
    }

    public function withMetadata(MetaDataCollection $metadata): self
    {
        return new self(
            $this->id,
            $this->role,
            $this->content,
            $this->metadata->add($metadata),
        );
    }
}
