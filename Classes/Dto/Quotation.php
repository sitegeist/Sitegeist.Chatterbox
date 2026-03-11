<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\Quotation as DomainQuotation;

#[Flow\Proxy(false)]
readonly class Quotation
{
    public function __construct(
        public int $index,
        public string $name,
        public string $abstract,
        public string $isPartOf,
    ) {
    }

    public static function fromDomainQuotation(DomainQuotation $domainQuotation): self
    {
        return new self(
            $domainQuotation->index,
            $domainQuotation->name,
            $domainQuotation->abstract,
            $domainQuotation->isPartOf->__toString()
        );
    }
}
