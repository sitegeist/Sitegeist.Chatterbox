<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use League\CommonMark\CommonMarkConverter;

#[Flow\Proxy(false)]
final class ContentText
{
    public function __construct(
        public readonly string $value,
    ) {
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
