<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Media\Domain\Repository\DocumentRepository;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\DocumentCollection;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeContract;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReference;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReferenceRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreService;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Sitegeist\Flow\OpenAiClientFactory\AccountRepository;
use Sitegeist\Flow\OpenAiClientFactory\OpenAiClientFactory;

#[Flow\Scope('singleton')]
class KnowledgeCommandController extends CommandController
{
    /**
     * @var string
     * @Flow\InjectConfiguration(path="context")
     */
    protected string $context;

    public function __construct(
        private readonly SourceOfKnowledgeRepository $sourceOfKnowledgeRepository,
        private readonly AssistantEntityRepository $assistantEntityRepository,
        private readonly VectorStoreService $vectorStoreService,
        private readonly OpenAiClientFactory $clientFactory,
        private readonly AccountRepository $accountRepository,
        private readonly VectorStoreReferenceRepository $vectorStoreReferenceRepository,
    ) {
        parent::__construct();
    }

    public function listCommand(): void
    {
        $knowledges = $this->sourceOfKnowledgeRepository->findAll();
        foreach ($knowledges as $knowledge) {
            $this->outputLine($knowledge->getName()->value);
        }
    }

    public function showCommand(string $name): void
    {
        $knowledge = $this->sourceOfKnowledgeRepository->findSourceByName($name);
        if ($knowledge instanceof SourceOfKnowledgeContract) {
            $documentCollection = $knowledge->getContent();
            foreach ($documentCollection as $document) {
                $this->outputLine('<info>%s.%s</info>', [$document->name,$document->type]);
                $this->outputLine();
                $this->output($document->content);
                $this->outputLine();
                $this->outputLine();
            }
        } else {
            $this->outputLine('Knowledge ´source %s was not found', [$name]);
            $this->quit(1);
        }
    }

    public function uploadCommand(?string $name = null, ?string $account = null): void
    {
        /**
         * @var AssistantEntity[] $assistantEntities
         */
        $assistantEntities = $name
            ? [$this->assistantEntityRepository->findOneByName($name)]
            : $this->assistantEntityRepository->findAll();

        /**
         * @var array<string, string[]>
         */
        $accountsForKnowledgeSourceIds = [];

        foreach ($assistantEntities as $assistantEntity) {
            if ($account !== null && $assistantEntity->getAccount() !== $account) {
                continue;
            }
            foreach ($assistantEntity->getKnowledgeSourceIdentifiers() as $knowledgeSourceIdentifier) {
                $accountsForKnowledgeSourceIds[$knowledgeSourceIdentifier][] = $assistantEntity->getAccount();
            }
        }

        $knowledgeSourceIdentifiersToUpdate = array_keys($accountsForKnowledgeSourceIds);

        /**
         * @var array<string, SourceOfKnowledgeContract> $knowledgeSources
         */
        $knowledgeSources = [];

        foreach ($knowledgeSourceIdentifiersToUpdate as $knowledgeSourceIdentifier) {
            $knowledgeSource = $this->sourceOfKnowledgeRepository->findSourceByName($knowledgeSourceIdentifier);
            if ($knowledgeSource instanceof SourceOfKnowledgeContract) {
                $knowledgeSources[$knowledgeSourceIdentifier] = $knowledgeSource;
            }
        }

        foreach ($knowledgeSources as $knowledgeSourceIdentifier => $knowledgeSource) {
            $uniqueAccountIdentifiers = array_unique($accountsForKnowledgeSourceIds[$knowledgeSourceIdentifier] ?? []);
            foreach ($uniqueAccountIdentifiers as $accountIdentifier) {
                $this->outputLine("- Uploading %s to %s", [$knowledgeSourceIdentifier, $accountIdentifier]);
                $account = $this->accountRepository->findById($accountIdentifier);
                $client = $this->clientFactory->createClientForAccountRecord($account);
                $storeId = $this->vectorStoreService->upload($client, $knowledgeSource);

                // persist reference
                $existingVectorStoreReference = $this->vectorStoreReferenceRepository->findOneByAssistantAndKnowledgeSourceIdentifier(
                    $accountIdentifier,
                    $knowledgeSourceIdentifier
                );

                if ($existingVectorStoreReference instanceof VectorStoreReference) {
                    $existingVectorStoreReference->updateVectorStoreId($storeId->value);
                    $this->vectorStoreReferenceRepository->update($existingVectorStoreReference);
                } else {
                    $newVectorStoreReference  = new VectorStoreReference(
                        $this->context,
                        $accountIdentifier,
                        $knowledgeSourceIdentifier,
                        $storeId->value
                    );
                    $this->vectorStoreReferenceRepository->add($newVectorStoreReference);
                }

                $this->outputLine("- Cleanup old instances of %s on %s", [$knowledgeSourceIdentifier, $accountIdentifier]);
                $this->vectorStoreService->cleanup($client, $knowledgeSource, $storeId);
            }
        }
    }
}
