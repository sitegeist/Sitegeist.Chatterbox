<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;

/**
 * A stub containing a quotation id describing an unresolved quotation
 *
 * @internal to be used for internal resolution in messages
 */
#[Flow\Proxy(false)]
final class UnresolvedQuotation
{
    public function __construct(
        public readonly string $id,
    ) {
    }
}
