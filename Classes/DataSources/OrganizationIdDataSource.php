<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\DataSources;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Sitegeist\Chatterbox\Domain\Organization;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

class OrganizationIdDataSource extends AbstractDataSource
{
    protected static $identifier = 'Sitegeist.Chatterbox:OrganizationId';

    public function __construct(
        private readonly OrganizationRepository $organizationRepository
    ) {
    }

    /**
     * @param NodeInterface|null $node
     * @param array<string, mixed> $arguments
     * @return array|array[]|mixed
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        return array_map(
            fn(Organization $item) => ['value' => $item->id, 'label' => $item->label],
            $this->organizationRepository->findAll()
        );
    }
}
