<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('The ID of an organization')]
final class OrganizationId implements \JsonSerializable
{
    public function __construct(
        public readonly string $value,
    ) {
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
