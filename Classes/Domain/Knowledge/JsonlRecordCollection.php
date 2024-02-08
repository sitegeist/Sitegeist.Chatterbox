<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

final class JsonlRecordCollection implements \Stringable
{
    /**
     * @var array<JsonlRecord>
     */
    private readonly array $items;

    public function __construct(
        JsonlRecord ...$items
    ) {
        $this->items = $items;
    }
    public static function fromString(string $string): self
    {
        if ($string === '') {
            return new self();
        }
        return new self(...array_map(
            fn (string $jsonString): JsonlRecord => JsonlRecord::fromString($jsonString),
            explode("\n", $string)
        ));
    }

    public function findRecordByContentPart(string $contentPart): ?JsonlRecord
    {
        foreach ($this->items as $item) {
            if (\str_contains($item->content, $contentPart)) {
                return $item;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return implode("\n", array_map(
            fn (JsonlRecord $record): string => \str_replace(PHP_EOL, ' ', \json_encode($record, JSON_THROW_ON_ERROR)),
            $this->items
        ));
    }
}
