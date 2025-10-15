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
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Instruction\InitialPromptInstruction;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionCollection;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionContract;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolCollection;
use Sitegeist\Chatterbox\Domain\Tools\ToolContract;
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

        $initialPromptInstruction = $assistantEntity->getInstructions();
        if ($initialPromptInstruction) {
            $instructions = new InstructionCollection(
                new InitialPromptInstruction('initialInstructions', '', $initialPromptInstruction)
            );
        } else {
            $instructions = new InstructionCollection();
        }

        return new Assistant(
            $assistantEntity->getName(),
            $model,
            new ToolCollection(),
            $instructions,
            new SourceOfKnowledgeCollection(),
            new OrganizationDiscriminator(''),
            $client,
            $this->connectionFactory->create(),
            ($this->settings['enableLogging'] ?? false) ? $this->logger : null
        );
    }
}
