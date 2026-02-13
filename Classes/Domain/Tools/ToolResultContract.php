<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Tools;

use Sitegeist\Chatterbox\Domain\MetaDataCollection;

interface ToolResultContract
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array;

    public function getMetadata(): MetaDataCollection;
}
