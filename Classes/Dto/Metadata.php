<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
readonly class Metadata
{
    public function __construct(
        public string $value,
    ) {
    }

    public static function fromJsonSerializable(\JsonSerializable $data): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @param mixed[] $data
     */
    public static function fromArray(array $data): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
