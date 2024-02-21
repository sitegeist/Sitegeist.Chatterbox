<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

#[Flow\Proxy(false)]
final class KnowledgeFilename
{
    private const FILE_ENDING = '.jsonl';

    public function __construct(
        public readonly KnowledgeSourceDiscriminator $knowledgeSourceDiscriminator,
        public readonly int $timestamp,
    ) {
    }

    public static function forKnowledgeSource(KnowledgeSourceDiscriminator $knowledgeSourceDiscriminator): self
    {
        return new self(
            $knowledgeSourceDiscriminator,
            time()
        );
    }

    public static function tryFromSystemFileName(string $systemFilename): ?self
    {
        if (!\str_ends_with($systemFilename, self::FILE_ENDING)) {
            return null;
        }

        $value = \mb_substr($systemFilename, 0, -6);
        if (substr_count($value, '-') !== 2) {
            return null;
        }
        list($discriminator, $sourceName, $timestamp) = explode('-', $value);

        return new self(
            new KnowledgeSourceDiscriminator(
                new OrganizationDiscriminator($discriminator),
                new KnowledgeSourceName($sourceName)
            ),
            (int)$timestamp
        );
    }

    public function toSystemFilename(): string
    {
        return $this->knowledgeSourceDiscriminator->toString() . '-' . $this->timestamp . self::FILE_ENDING;
    }

    public function takesPrecedenceOver(self $other): bool
    {
        if ($this->knowledgeSourceDiscriminator->equals($other->knowledgeSourceDiscriminator) === false) {
            return false;
        }

        return $this->timestamp > $other->timestamp;
    }
}
