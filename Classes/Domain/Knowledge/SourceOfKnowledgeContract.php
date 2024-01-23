<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

interface SourceOfKnowledgeContract
{
    /**
     * @param array<string,mixed> $options
     */
    public static function createFromConfiguration(string $name, array $options): static;

    public function getName(): string;

    public function getDescription(): string;

    public function getContent(): JsonlRecordCollection;
}
