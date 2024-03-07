<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Application;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\AssistantId;
use Sitegeist\Chatterbox\Domain\OrganizationId;
use Sitegeist\Chatterbox\Domain\ThreadId;
use Sitegeist\SchemeOnYou\Domain\Metadata\Schema;
use Sitegeist\SchemeOnYou\Domain\Path\RequestParameterContract;

#[Flow\Proxy(false)]
#[Schema('The query to get thread\'s history')]
final readonly class GetThreadHistory implements RequestParameterContract
{
    public function __construct(
        public OrganizationId $organizationId,
        public AssistantId $assistantId,
        public ThreadId $threadId
    ) {
    }

    /**
     * @param string $parameter
     */
    public static function fromRequestParameter(mixed $parameter): static
    {
        $values = \json_decode($parameter, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            new OrganizationId($values['organizationId']),
            new AssistantId($values['assistantId']),
            new ThreadId($values['threadId']),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}