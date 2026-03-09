<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\DataSources;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use OpenAI\Responses\Assistants\AssistantResponse;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

class AssistantIdDataSource extends AbstractDataSource
{
    protected static $identifier = 'Sitegeist.Chatterbox:AssistantId';

    public function __construct(
        private readonly AssistantEntityRepository $assistantEntityRepository,
        private readonly PersistenceManager $persistenceManager,
    ) {
    }

    /**
     * @param NodeInterface|null $node
     * @param array<string, mixed> $arguments
     * @return array|array[]|mixed
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $assistants = $this->assistantEntityRepository->findAll();

        return array_map(
            fn(AssistantEntity $item) => [
                'value' => $this->persistenceManager->getIdentifierByObject($item),
                'label' => $item->getName(),
            ],
            $assistants->toArray()
        );
    }
}
