<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextAnnotationFileCitationObject;

/**
 * The quote representation for database queries
 */
#[Flow\Proxy(false)]
final class QuoteString
{
    private function __construct(
        public readonly string $value,
    ) {
    }

    public static function tryFromFileCitationObject(
        ThreadMessageResponseContentTextAnnotationFileCitationObject $fileCitationObject
    ): ?self {
        if ($fileCitationObject->fileCitation->quote === null || $fileCitationObject->fileCitation->quote === '') {
            return null;
        }
        return new self($fileCitationObject->fileCitation->quote);
    }

    public function unicodeEscape(): self
    {
        return new self(\trim(\json_encode($this->value, JSON_THROW_ON_ERROR), '"'));
    }

    /**
     * Encodes unicode and newline characters so that they are usable in SQL
     */
    public function wtf8Encode(): string
    {
        $value = \str_replace('\\u', '\\\\\\u', $this->value);
        $value = \str_replace('\\\\n', '\\\\\\n', $value);
        $value = \str_replace('\\/', '\\\\\\/', $value);

        return '%' . $value . '%';
    }
}
