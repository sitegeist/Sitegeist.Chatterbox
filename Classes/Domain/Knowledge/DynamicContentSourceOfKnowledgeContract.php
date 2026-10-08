<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

interface DynamicContentSourceOfKnowledgeContract
{
    public function getContent(): DocumentCollection;
}
