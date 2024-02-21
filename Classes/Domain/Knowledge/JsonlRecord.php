<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use League\HTMLToMarkdown\HtmlConverter;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\UriInterface;

#[Flow\Proxy(false)]
final class JsonlRecord implements \JsonSerializable
{
    private function __construct(
        public readonly string $id,
        public readonly UriInterface $url,
        public readonly string $content,
    ) {
    }

    public static function createFromHtmlContent(
        string $id,
        UriInterface $url,
        string $content,
    ): self {
        $converter = new HtmlConverter();

        return new self(
            $id,
            $url,
            $converter->convert($content)
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
