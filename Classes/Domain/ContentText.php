<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use League\CommonMark\CommonMarkConverter;

#[Flow\Proxy(false)]
final class ContentText implements ContentInterface
{
    public function __construct(
        public readonly string $value,
    ) {
    }

    public static function fromThreadMessageResponseContentTextObject(ThreadMessageResponseContentTextObject $response): self
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($response->text->value);
        return new self($html->getContent());
    }

    public function getType(): string
    {
        return "text";
    }

    /**
     * @return array{type:string, text: array{value: string}}
     */
    public function toApiArray(): array
    {
        return [
            'type' => 'text',
            'value' => $this->value,
            'text' => ['value' => $this->value, 'deprecated' => 'use ../value instead'],
        ];
    }
}
