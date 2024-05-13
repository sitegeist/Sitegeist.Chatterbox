<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class ResolvedAndUnresolvedQuotations
{
    public function __construct(
        public readonly QuotationCollection $resolvedQuotations,
        public readonly UnresolvedQuotationCollection $unresolvedQuotations
    ) {
    }
}
