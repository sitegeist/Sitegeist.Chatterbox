<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\UriInterface;

#[Flow\Proxy(false)]
final class JsonlRecord implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly UriInterface $url,
        public readonly string $content,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => (string)$this->url,
            'content' => $this->content
        ];
    }
}
