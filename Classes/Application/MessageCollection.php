<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;

#[Flow\Proxy(false)]
#[Schema('A collection of messages')]
final readonly class MessageCollection implements \JsonSerializable
{
    /**
     * @var array<Message>
     */
    private array $items;

    public function __construct(Message ...$items)
    {
        $this->items = $items;
    }

    /**
     * @return array<Message>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
