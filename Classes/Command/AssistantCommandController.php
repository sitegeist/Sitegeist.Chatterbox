<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ResourceManagement\Collection;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseAttachment;
use Sitegeist\Chatterbox\Domain\Assistant;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\AssistantFactory;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeContract;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReference;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReferenceRepository;
use Sitegeist\Chatterbox\Domain\Organization;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

#[Flow\Scope('singleton')]
class AssistantCommandController extends CommandController
{
    /**
     * @var string
     * @Flow\InjectConfiguration(path="context")
     */
    protected string $context;

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly AssistantEntityRepository $assistantEntityRepository,
        private readonly AssistantFactory $assistantFactory,
        private readonly SourceOfKnowledgeRepository $sourceOfKnowledgeRepository,
        private readonly VectorStoreReferenceRepository $vectorStoreReferenceRepository,
    ) {
        parent::__construct();
    }

    public function educateCommand(?string $name = null): void
    {
        /**
         * @var AssistantEntity[] $assistantEntities
         */
        $assistantEntities = $name
            ? [$this->assistantEntityRepository->findOneByName($name)]
            : $this->assistantEntityRepository->findAll();

        foreach($assistantEntities as $assistantEntity) {
            $assistant = $this->assistantFactory->createAssistantFromAssistantEntity($assistantEntity);

            $this->outputLine('educate assistant: %s', [$assistantEntity->getName()]);
            foreach ($assistantEntity->getKnowledgeSourceIdentifiers()  as $knowledgeSourceIdentifier) {
                $source = $this->sourceOfKnowledgeRepository->findSourceByName($knowledgeSourceIdentifier);
                if ($source instanceof SourceOfKnowledgeContract) {
                    $this->outputLine(' - source of knowledge %s', [$knowledgeSourceIdentifier]);
                    $storeId = $assistant->getVectorStoreService()->createStoreForKnowledgeSource($source);
                    $vectorStoreReference  = new VectorStoreReference(
                        $assistant->getAccount(),
                        $storeId->value,
                        $this->context,
                        $knowledgeSourceIdentifier
                    );
                    $this->vectorStoreReferenceRepository->add($vectorStoreReference);
                }
            }
        }
    }

    public function migrateAllLegacyCommand(?string $organizationId = null): void
    {
        $organizations = $organizationId
            ? [$this->organizationRepository->findById($organizationId)]
            : $this->organizationRepository->findAll();

        foreach ($organizations as $organization) {
            $this->outputLine($organization->id);
            foreach ($organization->assistantDepartment->findAllRecords() as $assistant) {
                $this->outputLine($assistant->name ?? '');
                try {
                    $this->migrateLegacyAssitant($organization, $assistant);
                } catch (\Throwable $e) {
                    $this->outputLine($e->getMessage());
                }
            }
        }
    }

    public function migrateLegacyCommand(string $organizationId, string $assistantId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantRecordById($assistantId);
        $this->migrateLegacyAssitant($organization, $assistant);
    }

    private function migrateLegacyAssitant(Organization $organization, AssistantRecord $assistantRecord): AssistantEntity
    {
        $assistantEntity = new AssistantEntity();
        $assistantEntity->setName($assistantRecord->name ?? '');
        $assistantEntity->setAccount($organization->account);
        $assistantEntity->setModel($assistantRecord->model);
        $assistantEntity->setDescription($assistantRecord->description);
        $assistantEntity->setInstructions($assistantRecord->instructions);
        $assistantEntity->setToolIdentifiers($assistantRecord->selectedTools);
        $assistantEntity->setKnowledgeSourceIdentifiers($assistantRecord->selectedSourcesOfKnowledge);
        $assistantEntity->setInstructionIdentifiers($assistantRecord->selectedInstructions);
        $this->assistantEntityRepository->add($assistantEntity);

        $this->outputLine("- migrated %s", [$assistantEntity->getName()]);

        return $assistantEntity;
    }
}
