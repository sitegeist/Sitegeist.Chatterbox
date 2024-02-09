<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\MessageEditing;

use Neos\Flow\Annotations as Flow;

/**
 * @implements \IteratorAggregate<int, MessageEditorContract>
 */
#[Flow\Proxy(false)]
class MessageEditorCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var MessageEditorContract[]
     */
    public readonly array $items;

    public function __construct(MessageEditorContract ...$editors)
    {
        $this->items = $editors;
    }

    public function getEditorByName(string $editorName): ?MessageEditorContract
    {
        foreach ($this->items as $editor) {
            if ($editor->getName() === $editorName) {
                return $editor;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * @return \Traversable<int, MessageEditorContract>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
