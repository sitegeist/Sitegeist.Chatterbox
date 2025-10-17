<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\Assistant;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\Organization;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

#[Flow\Scope('singleton')]
class AssistantCommandController extends CommandController
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly AssistantEntityRepository $assistantEntityRepository,
    ) {
        parent::__construct();
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
                    $newRecord = $this->migrateLegacyAssitant($organization, $assistant);
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
        return $assistantEntity;
    }
}
