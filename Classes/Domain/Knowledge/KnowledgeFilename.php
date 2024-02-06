<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgeSourceName;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

#[Flow\Proxy(false)]
final class KnowledgeFilename
{
    private const FILE_ENDING = '.jsonl';

    public function __construct(
        public readonly OrganizationDiscriminator $discriminator,
        public readonly KnowledgeSourceName $knowledgeSourceName,
        public readonly int $timestamp,
    ) {
    }

    public static function forKnowledgeSource(KnowledgeSourceName $knowledgeSourceName, OrganizationDiscriminator $discriminator): self
    {
        return new self(
            $discriminator,
            $knowledgeSourceName,
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
            new OrganizationDiscriminator($discriminator),
            new KnowledgeSourceName($sourceName),
            (int)$timestamp
        );
    }

    public function toSystemFilename(): string
    {
        return $this->discriminator->value . '-' . $this->knowledgeSourceName->value . '-' . $this->timestamp . self::FILE_ENDING;
    }


    public function isRelevantFor(OrganizationDiscriminator $discriminator, KnowledgeSourceName $knowledgeSourceName): bool
    {
        return ($this->discriminator->equals($discriminator) && $this->knowledgeSourceName->equals($knowledgeSourceName));
    }

    public function takesPrecedenceOver(self $other): bool
    {
        if ($this->isRelevantFor($other->discriminator, $other->knowledgeSourceName) === false) {
            return false;
        }

        return $this->timestamp > $other->timestamp;
    }
}
