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
        $items = [];
        foreach ($domainQuotations as $domainQuotation) {
            $items[] = Quotation::fromDomainQuotation($domainQuotation);
        }
        return new self(...$items);
    }
}
