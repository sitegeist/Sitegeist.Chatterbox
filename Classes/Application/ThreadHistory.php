<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\SchemeOnYou\Domain\Metadata\PathResponse;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('A thread\'s history')]
#[PathResponse(200, '')]
final readonly class ThreadHistory implements \JsonSerializable
{
    public function __construct(
        public MessageCollection $messages,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
