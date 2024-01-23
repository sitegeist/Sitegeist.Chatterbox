<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Tools;

use Sitegeist\Chatterbox\Domain\Tools\ToolResultContract;

class ToolResult implements ToolResultContract
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        protected readonly array $data,
        protected readonly array $metadata,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
