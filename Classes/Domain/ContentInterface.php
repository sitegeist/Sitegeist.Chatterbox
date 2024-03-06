<?php

namespace Sitegeist\Chatterbox\Domain;

interface ContentInterface
{
    public function getType(): string;

    /**
     * @return array{type:string}
     */
    public function toApiArray(): array;
}
