<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\ContentText as DomainContentText;

#[Flow\Proxy(false)]
readonly class ContentText
{
    public function __construct(
        public string $text,
        public QuotationCollection $quotations,
    ) {
    }

    public static function fromDomainContentText(DomainContentText $content): self
    {
        return new self(
            $content->value,
            QuotationCollection::fromDomainQuotationCollection($content->quotationCollection)
        );
    }
}
