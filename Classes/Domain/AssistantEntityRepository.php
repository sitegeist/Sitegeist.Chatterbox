<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Persistence\Repository;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
final class AssistantEntityRepository extends Repository
{
    /**
     * Returns the object type this repository is managing.
     *
     * @return string
     * @api
     */
    public function getEntityClassName(): string
    {
        return AssistantEntity::class;
    }
}
