<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

#[Flow\Proxy(false)]
final readonly class VectorStoreIdCollection
{
    public function __construct(
        public string $value,
    ) {
    }
}
