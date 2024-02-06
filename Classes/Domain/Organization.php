<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Contracts\ClientContract;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;

#[Flow\Proxy(false)]
class Organization
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly OrganizationDiscriminator $discriminator,
        public readonly ClientContract $client,
        public readonly AssistantDepartment $assistantDepartment,
        public readonly Manual $manual,
        public readonly KnowledgePool $knowledgePool,
        public readonly ModelAgency $modelAgency,
        public readonly Toolbox $toolbox,
        public readonly Library $library,
    ) {
    }
}
