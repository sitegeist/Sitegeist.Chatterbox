<?php
declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Persistence\Repository;
use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\AssistantEntity;

#[Flow\Scope('singleton')]
class VectorStoreReferenceRepository extends Repository {

    /**
     * @var string
     * @Flow\InjectConfiguration(path="context")
     */
    protected string $context;

    public function findOneByAssistantAndKnowledgeSourceIdentifier(AssistantEntity $assistantEntity, string $knowledgeSourceIdentifier): ?VectorStoreReference
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('account', $assistantEntity->getAccount(), true),
                $query->equals('context', $this->context, true),
                $query->equals('knowledgeSourceIdentifier', $knowledgeSourceIdentifier, true)
            )
        );
        return $query->execute()->getFirst();
    }
}
