<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Sitegeist\Chatterbox\Domain\Quotation;

interface QuotableSourceOfKnowledgeContract
{
    public function tryCreateQuotation(int $index, string $name, string $type): ?Quotation;
}
