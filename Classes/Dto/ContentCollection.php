<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Dto;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\ContentCollection as DomainContentCollection;
use Sitegeist\Chatterbox\Domain\ContentText as DomainContentText;

#[Flow\Proxy(false)]
readonly class ContentCollection
{
    /**
     * @var ContentText[]
     */
    public array $items;

    public function __construct(
        ContentText ...$items
    ) {
        $this->items = $items;
    }

    public static function fromDomainContentCollection(DomainContentCollection $content): self
    {
        $items = [];
        foreach ($content as $item) {
            if ($item instanceof DomainContentText) {
                $items[] = ContentText::fromDomainContentText($item);
            }
        }
        return new self(...$items);
    }
}
