<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Tools;

interface ToolResultContract
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
