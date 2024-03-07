<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('A message in a thread')]
final readonly class Message implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public bool $bot,
        public array $message,
        public array $quotations,
        public ?array $metadata,
    ) {
    }

    public static function fromMessageRecordAndMetadata(MessageRecord $messageRecord, array $metadata): self
    {
        return new self(
            $messageRecord->id,
            $messageRecord->role !== 'user',
            $messageRecord->content->toApiArray(),
            $messageRecord->quotations->toApiArray(),
            $metadata
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
