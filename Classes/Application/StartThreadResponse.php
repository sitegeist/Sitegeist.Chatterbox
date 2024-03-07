<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\SchemeOnYou\Domain\Metadata\PathResponse;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('A thread was successfully started')]
#[PathResponse(200, '')]
final readonly class StartThreadResponse implements \JsonSerializable
{
    public function __construct(
        public string $treadId,
        public ?array $metadata,
        public string $id,
        public bool $bot,
        public array $message,
        public array $quotations,
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
