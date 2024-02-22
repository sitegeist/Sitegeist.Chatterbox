<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Sitegeist\Chatterbox\Domain\Quotation;

interface SourceOfKnowledgeContract
{
    /**
     * @param array<string,mixed> $options
     */
    public static function createFromConfiguration(string $name, array $options): static;

    public function getName(): KnowledgeSourceName;

    public function getDescription(): string;

    public function getContent(): JsonlRecordCollection;

    public function tryCreateQuotation(string $identifier, string $quote, string $id): ?Quotation;
}
