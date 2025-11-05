<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Persistence\Repository;
use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReference;

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

    public function findOneByName(string $name): ?AssistantEntity
    {
        $query = $this->createQuery();
        $query->matching($query->equals('name', $name));
        /** @var ?AssistantEntity $result */
        $result = $query->execute()->getFirst();
        return $result;
    }
}
