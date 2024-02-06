<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use OpenAI\Contracts\ClientContract;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;

#[Flow\Proxy(false)]
class KnowledgeSourceName
{
    public function __construct(
        public readonly string $value,
    ) {
        if (str_contains($value, '-')) {
            throw new \Exception('KnowledgeSourceName must not contain "-" but "' . $value . '" was given');
        }
        if (empty($value)) {
            throw new \Exception('KnowledgeSourceName must not be empty');
        }
    }

    public function equals(self|string $other): bool
    {
        if ($other instanceof self) {
            $otherValue = $other->value;
        } else {
            $otherValue = $other;
        }
        return $this->value === $otherValue;
    }
}
