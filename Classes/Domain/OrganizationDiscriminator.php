<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class OrganizationDiscriminator
{
    public function __construct(
        public readonly string $value,
    ) {
        if (str_contains($value, '-')) {
            throw new \Exception('OrganizationDiscriminator must not contain "-" but "' . $value . '" was given');
        }
    }

    public function equals(self|string $other): bool
    {
        if ($other instanceof self) {
            $otherValue = $other->value;
        } else {
            $otherValue = $other;
        }
        return $this->value === $otherValue;
    }
}
