<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\MessageEditing;

interface MessageEditingResultContract
{
    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
