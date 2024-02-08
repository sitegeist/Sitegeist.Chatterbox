<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use GuzzleHttp\Psr7\Uri;
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

    public static function fromString(string $string): self
    {
        $values = \json_decode($string, true, flags: JSON_THROW_ON_ERROR);

        return new self(
            $values['id'],
            new Uri($values['url']),
            $values['content']
        );
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
