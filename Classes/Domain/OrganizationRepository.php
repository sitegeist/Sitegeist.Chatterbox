<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Utility\Environment;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
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
        private readonly Environment $environment
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
        $discriminator = new OrganizationDiscriminator($config['discriminator'] ?? '');

        $toolbox = new Toolbox($config['tools']);
        $manual = new Manual($this->objectManager, $config['instructions']);
        $assistantDepartment = new AssistantDepartment(
            $client,
            $toolbox,
            $manual,
            $this->logger,
            $discriminator,
        );

        return new Organization(
            $id,
            $config['label'],
            $discriminator,
            $client,
            $assistantDepartment,
            $manual,
            new ModelAgency($client),
            $toolbox,
            new Library($config['knowledge'], $assistantDepartment, $client, $this->environment, $discriminator)
        );
    }
}
