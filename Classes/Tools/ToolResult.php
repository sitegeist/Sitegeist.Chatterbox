<?php
declare(strict_types=1);

namespace Sitegeist\Chatterbox\Tools;

class ToolResult implements ToolResultContract
{
    public function __construct(
        protected readonly array|\JsonSerializable $data,
        protected readonly array|\JsonSerializable $metadata,
    ) {
    }

    public function getData(): array|\JsonSerializable
    {
        return $this->data;
    }

    public function getMetadata(): array|\JsonSerializable
    {
        return $this->metadata;
    }
}
