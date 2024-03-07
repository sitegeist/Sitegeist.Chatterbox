<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\DataSources;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\OrganizationId;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

class AssistantIdDataSource extends AbstractDataSource
{
    protected static $identifier = 'Sitegeist.Chatterbox:AssistantId';

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {
    }

    /**
     * @param NodeInterface|null $node
     * @param array<string, mixed> $arguments
     * @return array|array[]|mixed
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $organizationId = $arguments['organizationId'] ?? null;
        if (!$organizationId) {
            return [];
        }

        $organization = $this->organizationRepository->findById(new OrganizationId($organizationId));
        $assistants = $organization->assistantDepartment->findAllRecords();

        return array_map(
            fn (AssistantRecord $item) => ['value' => $item->id, 'label' => $item->name],
            iterator_to_array($assistants)
        );
    }
}
