<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;
use Sitegeist\SchemeOnYou\Domain\Path\RequestParameterContract;

#[Flow\Proxy(false)]
#[Schema('The command to start a thread')]
final readonly class StartThread implements RequestParameterContract
{
    public function __construct(
        public string $organizationId,
        public string $assistantId,
        public string $message
    ) {
    }

    /**
     * @param string $parameter
     */
    public static function fromRequestParameter(mixed $parameter): static
    {
        return new self(...\json_decode($parameter, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
