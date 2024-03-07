<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Contracts\ClientContract;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;

#[Flow\Proxy(false)]
readonly class Organization
{
    public function __construct(
        public OrganizationId $id,
        public string $label,
        public OrganizationDiscriminator $discriminator,
        public ClientContract $client,
        public AssistantDepartment $assistantDepartment,
        public Manual $manual,
        public ModelAgency $modelAgency,
        public Toolbox $toolbox,
        public Library $library,
    ) {
    }
}
