<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

#[Flow\Scope('singleton')]
class KnowledgeCommandController extends CommandController
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {
        parent::__construct();
    }

    public function updatePoolCommand(?string $organizationId = null): void
    {
        $this->outputLine('Updating knowledge pool');
        $organizations = $organizationId
            ? [$this->organizationRepository->findById($organizationId)]
            : $this->organizationRepository->findAll();
        $numberOfSources = 0;
        foreach ($organizations as $organization) {
            $numberOfSources += count($organization->knowledgePool->findAllSources());
        }
        $this->output->progressStart($numberOfSources);

        foreach ($organizations as $organization) {
            $sources = $organization->knowledgePool->findAllSources();
            foreach ($sources as $sourceOfKnowledge) {
                $organization->library->updateSourceOfKnowledge($sourceOfKnowledge);
                $this->output->progressAdvance();
            }
        }

        $this->output->progressFinish();
        $this->outputLine('');
        $this->outputLine('Done');
    }

    public function cleanPoolCommand(?string $organizationId = null): void
    {
        $this->outputLine('Cleaning knowledge pool');
        $organizations = $organizationId
            ? [$this->organizationRepository->findById($organizationId)]
            : $this->organizationRepository->findAll();
        $this->output->progressStart(count($organizations));
        foreach ($organizations as $organization) {
            $organization->library->cleanKnowledgePool();
            $this->output->progressAdvance();
        }
        $this->outputLine('');
        $this->outputLine('Done');
    }
}
