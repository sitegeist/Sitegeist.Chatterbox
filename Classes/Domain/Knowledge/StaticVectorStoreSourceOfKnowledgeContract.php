<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

interface StaticVectorStoreSourceOfKnowledgeContract
{
    public function getVectorStoreId(): VectorStoreId;
}
