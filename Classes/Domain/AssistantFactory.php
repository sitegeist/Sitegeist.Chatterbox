<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connection as DatabaseConnection;
use Doctrine\ORM\EntityManager;
use Dsr\KlimaLink\Infrastructure\ClientFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\ConnectionFactory;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InitialPromptInstruction;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionContract;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeContract;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReferenceRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreService;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;
use Sitegeist\Chatterbox\Domain\Tools\ToolRepository;
use Sitegeist\Flow\OpenAiClientFactory\AccountRepository;
use Sitegeist\Flow\OpenAiClientFactory\OpenAiClientFactory;

class AssistantFactory
{
    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    public function __construct(
        private readonly OpenAiClientFactory $clientFactory,
        private readonly ConnectionFactory $connectionFactory,
        private readonly AccountRepository $accountRepository,
        private readonly InstructionRepository $instructionRepository,
        private readonly ToolRepository $toolRepository,
        private readonly SourceOfKnowledgeRepository $sourceOfKnowledgeRepository,
        private readonly VectorStoreReferenceRepository $vectorStoreReferenceRepository,
        private readonly Environment $environment,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function createAssistantFromAssistantEntity(AssistantEntity $assistantEntity): Assistant
    {
        $account = $this->accountRepository->findById($assistantEntity->getAccount());
        $client = $this->clientFactory->createClientForAccountRecord($account);
        $model = $assistantEntity->getModel() ?? '';

        /**
         * @var ToolContract[]
         */
        $tools = [];
        foreach ($assistantEntity->getToolIdentifiers() as $toolIdentifier) {
            $tools[] = $this->toolRepository->findByName($toolIdentifier);
        }

        /**
         * @var InstructionContract[] $instructions
         */
        $instructions = [];
        $initialPromptInstruction = $assistantEntity->getInstructions();
        if ($initialPromptInstruction) {
            $instructions[] = new InitialPromptInstruction('initialInstructions', '', $initialPromptInstruction);
        }
        foreach ($assistantEntity->getInstructionIdentifiers() as $instructionIdentifier) {
            $instructions[] = $this->instructionRepository->findInstructionByName($instructionIdentifier);
        }

        /**
         * @var SourceOfKnowledgeContract[] $knowledgeSources
         */
        $knowledgeSources = [];
        foreach ($assistantEntity->getKnowledgeSourceIdentifiers() as $knowledgeSourceIdentifier) {
            $knowledgeSources[] = $this->sourceOfKnowledgeRepository->findSourceByName($knowledgeSourceIdentifier);
        }

        $vectorStoreService = new VectorStoreService(
            $client,
            $this->environment
        );

        return new Assistant(
            $assistantEntity->getName(),
            $assistantEntity->getAccount(),
            $assistantEntity,
            $model,
            new ToolCollection(...array_filter($tools)),
            new InstructionCollection(...array_filter($instructions)),
            new SourceOfKnowledgeCollection(...array_filter($knowledgeSources)),
            new OrganizationDiscriminator(''),
            $client,
            $this->connectionFactory->create(),
            $vectorStoreService,
            $this->vectorStoreReferenceRepository,
            ($this->settings['enableLogging'] ?? false) ? $this->logger : null
        );
    }
}
