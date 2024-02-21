<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;

/**
 * @implements \IteratorAggregate<ContentText>
 */
#[Flow\Proxy(false)]
final class ContentCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<ContentText>
     */
    private readonly array $items;

    public function __construct(
        ContentText ...$items
    ) {
        $this->items = $items;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function fromThreadMessageResponse(ThreadMessageResponse $response): self
    {
        $contents = [];
        foreach ($response->content as $textOrImage) {
            if ($textOrImage instanceof ThreadMessageResponseContentTextObject) {
                $contents[] = ContentText::fromThreadMessageResponseContentTextObject($textOrImage);
            }
        }

        return new self(...$contents);
    }

    /**
     * @return mixed[]
     */
    public function toApiArray(): array
    {
        return array_map(
            fn (ContentText $item): array => $item->toApiArray(),
            $this->items
        );
    }

    /**
     * @return \Traversable<ContentText>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}
