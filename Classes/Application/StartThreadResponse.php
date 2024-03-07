<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\ThreadId;
use Sitegeist\SchemeOnYou\Domain\Metadata\PathResponse;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('A thread was successfully started')]
#[PathResponse(200, '')]
final readonly class StartThreadResponse implements \JsonSerializable
{
    public function __construct(
        public ThreadId $threadId,
        public Message $message,
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
