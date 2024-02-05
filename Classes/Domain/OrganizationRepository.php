<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;
use Sitegeist\Flow\OpenAiClientFactory\AccountRepository;
use Sitegeist\Flow\OpenAiClientFactory\OpenAiClientFactory;

#[Flow\Scope('singleton')]
class OrganizationRepository
{
    /**
     * @var array<string,mixed>
     */
    #[Flow\InjectConfiguration(path: 'organizations')]
    protected array $organizationsConfig = [];

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly OpenAiClientFactory $clientFactory,
        private readonly ObjectManagerInterface $objectManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findById(string $id): Organization
    {
        $config = $this->organizationsConfig[$id] ?? null;
        if (!$config) {
            throw new \Exception('Unknown organization "' . $id . '"', 1707150041);
        }

        return $this->createOrganization($id, $config);
    }

    public function findFirst(): ?Organization
    {
        $id = array_key_first($this->organizationsConfig);

        return is_string($id) ? $this->createOrganization($id, $this->organizationsConfig[$id]) : null;
    }

    /**
     * @return array<Organization>
     */
    public function findAll(): array
    {
        $organizations = [];
        foreach ($this->organizationsConfig as $organizationId => $organizationConfig) {
            $organizations[] = $this->createOrganization($organizationId, $organizationConfig);
        }

        return $organizations;
    }

    /**
     * @param array<string,mixed> $config
     */
    private function createOrganization(string $id, array $config): Organization
    {
        $account = $this->accountRepository->findById($config['accountId']);
        $client = $this->clientFactory->createClientForAccountRecord($account);

        $toolbox = new Toolbox($config['tools']);
        $manual = new Manual($this->objectManager, $config['instructions']);

        return new Organization(
            $id,
            $config['label'],
            $client,
            new AssistantDepartment(
                $client,
                $toolbox,
                $manual,
                $this->logger
            ),
            $manual,
            new KnowledgePool($config['knowledge']),
            new ModelAgency($client),
            $toolbox
        );
    }
}
