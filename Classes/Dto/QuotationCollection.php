<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\QuotationCollection as DomainQuotationCollection;

#[Flow\Proxy(false)]
readonly class QuotationCollection
{
    /**
     * @var Quotation[]
     */
    public array $items;

    public function __construct(
        Quotation ...$items,
    ) {
        $this->items = $items;
    }

    public static function fromDomainQuotationCollection(DomainQuotationCollection $domainQuotations): self
    {
        // since there is no way to determine the reference position we ensure each domain is only shown once
        // this may change once we have a proper definition for the index property and can link citations to text.
        $itemsByUri = [];
        foreach ($domainQuotations as $domainQuotation) {
            $itemsByUri[(string)$domainQuotation->isPartOf] = Quotation::fromDomainQuotation($domainQuotation);
        }
        return new self(...array_values($itemsByUri));
    }
}
