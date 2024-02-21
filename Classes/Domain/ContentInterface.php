<?php

namespace Sitegeist\Chatterbox\Domain;

interface ContentInterface
{
    public function getType(): string;

    /**
     * @return array{type:string, text: array{value: string}}
     */
    public function toApiArray(): array;
}
