<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

final class JsonlRecordCollection implements \Stringable
{
    /**
     * @var array<JsonlRecord>
     */
    private readonly array $records;

    public function __construct(
        JsonlRecord ...$records
    ) {
        $this->records = $records;
    }

    public function __toString(): string
    {
        return implode("\n", array_map(
            fn (JsonlRecord $record): string => \str_replace(PHP_EOL, ' ', \json_encode($record, JSON_THROW_ON_ERROR)),
            $this->records
        ));
    }
}
