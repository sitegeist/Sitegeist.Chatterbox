<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

#[Flow\Proxy(false)]
final readonly class VectorStoreName
{
    public function __construct(
        public KnowledgeSourceDiscriminator $knowledgeSourceDiscriminator,
        public int $timestamp,
    ) {
    }

    public static function forKnowledgeSource(KnowledgeSourceDiscriminator $knowledgeSourceDiscriminator): self
    {
        return new self(
            $knowledgeSourceDiscriminator,
            time()
        );
    }

    public static function tryFromNullableString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        if (substr_count($value, '-') !== 2) {
            return null;
        }
        list($discriminator, $sourceName, $timestamp) = explode('-', $value);

        if (!is_numeric($timestamp)) {
            return null;
        }

        return new self(
            new KnowledgeSourceDiscriminator(
                new OrganizationDiscriminator($discriminator),
                new KnowledgeSourceName($sourceName)
            ),
            (int)$timestamp
        );
    }

    public function takesPrecedenceOver(self $other): bool
    {
        if ($this->knowledgeSourceDiscriminator->equals($other->knowledgeSourceDiscriminator) === false) {
            return false;
        }

        return $this->timestamp > $other->timestamp;
    }

    public function toString(): string
    {
        return $this->knowledgeSourceDiscriminator->toString() . '-' . $this->timestamp;
    }
}
