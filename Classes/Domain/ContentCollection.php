<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Actions\Responses\OutputText;
use OpenAI\Responses\Conversations\ConversationItem;
use OpenAI\Responses\Conversations\Objects\Message;
use OpenAI\Responses\Responses\Input\InputMessageContentInputText as InputText;
use OpenAI\Responses\Responses\Output\OutputMessageContentOutputText;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;

/**
 * @implements \IteratorAggregate<ContentInterface>
 */
#[Flow\Proxy(false)]
final class ContentCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<ContentInterface>
     */
    private readonly array $items;

    public function __construct(
        ContentInterface ...$items
    ) {
        $this->items = $items;
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function fromMessageItem(Message $message): self
    {
        $contents = [];
        foreach ($message->content as $contentItem) {
            if ($contentItem instanceof InputText) {
                $contents[] = new ContentText($contentItem->text);
            } elseif ($contentItem instanceof OutputMessageContentOutputText) {
                $contents[] = new ContentText($contentItem->text);
            }
        }
        return new self(...$contents);
    }

    public static function fromThreadMessageResponse(ThreadMessageResponse $response, UnresolvedQuotationCollection $quotationStubs): self
    {
        $contents = [];
        foreach ($response->content as $textOrImage) {
            if ($textOrImage instanceof ThreadMessageResponseContentTextObject) {
                $contents[] = ContentText::fromThreadMessageResponseContentTextObject($textOrImage, $quotationStubs);
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
            fn (ContentInterface $item): array => $item->toApiArray(),
            $this->items
        );
    }

    /**
     * @return \Traversable<ContentInterface>
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
