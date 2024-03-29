<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

#[Flow\Scope('singleton')]
class AssistantCommandController extends CommandController
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {
        parent::__construct();
    }

    public function upskillCommand(string $organizationId, string $assistantId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantRecordById($assistantId);
        $organization->assistantDepartment->updateAssistant($assistant);
    }

    public function upskillAllCommand(?string $organizationId = null): void
    {
        $organizations = $organizationId
            ? [$this->organizationRepository->findById($organizationId)]
            : $this->organizationRepository->findAll();

        foreach ($organizations as $organization) {
            foreach ($organization->assistantDepartment->findAllRecords() as $assistant) {
                $organization->assistantDepartment->updateAssistant($assistant);
            }
        }
    }
}
