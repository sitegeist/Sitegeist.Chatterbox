<?php

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class VectorStoreReference
{
    /**
     * @var string
     * @ORM\Id
     */
    public $account;

    /**
     * @var string
     * @ORM\Id
     */
    public $context;

    /**
     * @var string
     * @ORM\Id
     */
    public $knowledgeSourceIdentifier;

    /**
     * @var string
     */
    public $vectorStoreId;

    /**
     * @param string $context
     * @param string $account
     * @param string $knowledgeSourceIdentifier
     * @param string $vectorStoreId
     */
    public function __construct(string $context, string $account, string $knowledgeSourceIdentifier, string $vectorStoreId)
    {
        $this->account = $account;
        $this->vectorStoreId = $vectorStoreId;
        $this->context = $context;
        $this->knowledgeSourceIdentifier = $knowledgeSourceIdentifier;
    }

    public function updateVectorStoreId(string $vectorStoreId): void
    {
        $this->vectorStoreId = $vectorStoreId;
    }
}
