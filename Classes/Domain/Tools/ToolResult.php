<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Tools;

use Sitegeist\Chatterbox\Domain\MetaDataCollection;

class ToolResult implements ToolResultContract
{
    /**
     * @param array<string, mixed> $data
     * @param MetaDataCollection $metadata
     */
    public function __construct(
        protected readonly array $data,
        protected readonly MetaDataCollection $metadata,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): MetaDataCollection
    {
        return $this->metadata;
    }
}
