<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use League\HTMLToMarkdown\HtmlConverter;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class Document
{
    private function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $content,
    ) {
    }

    public static function createFromHtmlContent(
        string $filename,
        string $content,
    ): self {
        $converter = new HtmlConverter();

        return new self(
            $filename,
            'md',
            $converter->convert($content)
        );
    }
}
