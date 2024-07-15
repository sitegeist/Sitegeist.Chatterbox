<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Thread;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class ThreadRecord
{
    public function __construct(
        public readonly ThreadId $threadId,
        public readonly \DateTimeImmutable $dateCreated,
    ) {
    }
}
