<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\MetaDataCollection as DomainMetaDataCollection;

#[Flow\Proxy(false)]
readonly class Message
{
    public function __construct(
        public MessageId $id,
        public string $role,
        public ContentCollection $contents,
        public MetaDataCollection $metadata,
    ) {
    }

    public static function fromMessageRecord(MessageRecord $messageRecord): self
    {
        return new self(
            new MessageId($messageRecord->id),
            $messageRecord->role,
            ContentCollection::fromDomainContentCollection($messageRecord->content),
            MetaDataCollection::createFromDomainMetaDataCollection($messageRecord->metadata)
        );
    }

    public function withAddedMetadata(MetaDataCollection $metadata): self
    {
        return new self(
            $this->id,
            $this->role,
            $this->contents,
            $this->metadata->add($metadata),
        );
    }
}
