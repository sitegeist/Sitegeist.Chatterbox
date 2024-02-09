<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\MessageEditing;

class MessageEditingResult implements MessageEditingResultContract
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        protected readonly array $metadata,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
