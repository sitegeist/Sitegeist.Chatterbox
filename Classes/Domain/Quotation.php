<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\UriInterface;

/**
 * @see https://schema.org/Quotation
 */
#[Flow\Proxy(false)]
final class Quotation
{
    public function __construct(
        public readonly string $text,
        public readonly string $name,
        public readonly string $abstract,
        public readonly UriInterface $isPartOf,
    ) {
    }

    /**
     * @return array{text:string, isPartOf:string}
     */
    public function toApiArray(): array
    {
        return [
            'text' => $this->text,
            'name' => $this->name,
            'abstract' => $this->abstract,
            'isPartOf' => (string)$this->isPartOf,
        ];
    }
}
