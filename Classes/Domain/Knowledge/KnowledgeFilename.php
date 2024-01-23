<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

#[Flow\Proxy(false)]
final class KnowledgeFilename
{
    private const FILE_ENDING = '.jsonl';

    public function __construct(
        public readonly string $knowledgeSourceName,
        public readonly int $timestamp,
    ) {
    }

    public static function forKnowledgeSource(string $knowledgeSourceName): self
    {
        return new self(
            $knowledgeSourceName,
            time()
        );
    }

    public static function tryFromSystemFileName(string $systemFilename): ?self
    {
        if (!\str_ends_with($systemFilename, self::FILE_ENDING)) {
            throw new \Exception($systemFilename . ' is no valid system file name', 1706011768);
        }
        $value = \mb_substr($systemFilename, 0, -6);
        $pivot = \mb_strrpos($value, '-');
        if (!$pivot) {
            return null;
        }

        return new self(
            \mb_substr($value, 0, $pivot),
            (int)\mb_substr($value, $pivot + 1)
        );
    }

    public function toSystemFilename(): string
    {
        return $this->knowledgeSourceName . '-' . $this->timestamp . self::FILE_ENDING;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->knowledgeSourceName === $other->knowledgeSourceName
            && $this->timestamp > $other->timestamp;
    }
}
