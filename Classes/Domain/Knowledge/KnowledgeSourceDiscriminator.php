<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

#[Flow\Proxy(false)]
class KnowledgeSourceDiscriminator
{
    public function __construct(
        public readonly OrganizationDiscriminator $organizationDiscriminator,
        public readonly KnowledgeSourceName $knowledgeSourceName
    ) {
    }

    public static function fromString(string $string): self
    {
        list($organizationDiscriminator, $knowledgeSourceName) = explode('-', $string);

        return new self(
            new OrganizationDiscriminator($organizationDiscriminator),
            new KnowledgeSourceName($knowledgeSourceName)
        );
    }

    public function equals(self $other): bool
    {
        return $this->organizationDiscriminator->equals($other->organizationDiscriminator)
            && $this->knowledgeSourceName->equals($other->knowledgeSourceName);
    }

    public function toString(): string
    {
        return $this->organizationDiscriminator->value . '-' . $this->knowledgeSourceName->value;
    }
}
