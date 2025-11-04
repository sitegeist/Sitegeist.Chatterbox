<?php

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Entity
 */
class VectorStoreReference {

    /**
     * @var string
     */
    public $account;

    /**
     * @var string
     */
    public $vectorStoreId;

    /**
     * @var string
     */
    public $context;

    /**
     * @var string
     */
    public $knowledgeSourceIdentifier;

    /**
     * @param string $account
     * @param string $vectorStoreId
     * @param string $context
     * @param string $knowledgeSourceIdentifier
     */
    public function __construct(string $account, string $vectorStoreId, string $context, string $knowledgeSourceIdentifier)
    {
        $this->account = $account;
        $this->vectorStoreId = $vectorStoreId;
        $this->context = $context;
        $this->knowledgeSourceIdentifier = $knowledgeSourceIdentifier;
    }


}
