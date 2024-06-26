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

    public static function fromThreadMessageResponseContentTextObject(
        ThreadMessageResponseContentTextObject $response,
        UnresolvedQuotationCollection $unresolvedQuotations
    ): self {
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $text = $unresolvedQuotations->removeFromText($response->text->value);
        $html = $converter->convert($text);
        return new self($html->getContent());
    }

    public function getType(): string
    {
        return "text";
    }

    /**
     * @return array{type:string, value: string}
     */
    public function toApiArray(): array
    {
        return [
            'type' => 'text',
            'value' => $this->value,
        ];
    }
}
