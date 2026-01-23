<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\MessageRecord;

#[Flow\Proxy(false)]
readonly class Message
{
    public function __construct(
        public MessageId $id,
        public string $role,
        public ContentCollection $contents,
        public ?Metadata $metadata,
    ) {
    }

    public static function fromMessageRecord(MessageRecord $messageRecord): self
    {
        return new self(
            new MessageId($messageRecord->id),
            $messageRecord->role,
            ContentCollection::fromDomainContentCollection($messageRecord->content),
            Metadata::fromArray($messageRecord->metadata),
        );
    }

    public function withMetadata(Metadata $metadata): self
    {
        return new self(
            $this->id,
            $this->role,
            $this->contents,
            $metadata,
        );
    }
}
