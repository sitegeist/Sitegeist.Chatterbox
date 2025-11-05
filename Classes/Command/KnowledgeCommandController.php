<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeContract;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeRepository;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

#[Flow\Scope('singleton')]
class KnowledgeCommandController extends CommandController
{
    public function __construct(
        private readonly SourceOfKnowledgeRepository $sourceOfKnowledgeRepository,
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
                $this->outputLine($document->name . '.' . $document->type);
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

    public function updatePoolCommand(?string $organizationId = null): void
    {
//        $this->outputLine('Updating knowledge pool');
//        $organizations = $organizationId
//            ? [$this->organizationRepository->findById($organizationId)]
//            : $this->organizationRepository->findAll();
//        $numberOfSources = 0;
//        foreach ($organizations as $organization) {
//            $numberOfSources += count($organization->library->findAllSourcesOfKnowledge());
//        }
//        $this->output->progressStart($numberOfSources);
//
//        foreach ($organizations as $organization) {
//            foreach ($organization->library->findAllSourcesOfKnowledge() as $sourceOfKnowledge) {
//                $organization->library->updateSourceOfKnowledge($sourceOfKnowledge);
//                $this->output->progressAdvance();
//            }
//        }
//
//        $this->output->progressFinish();
//        $this->outputLine('');
//        $this->outputLine('Done');
    }

    public function cleanPoolCommand(?string $organizationId = null): void
    {
//        $this->outputLine('Cleaning knowledge pool');
//        $organizations = $organizationId
//            ? [$this->organizationRepository->findById($organizationId)]
//            : $this->organizationRepository->findAll();
//        $this->output->progressStart(count($organizations));
//        foreach ($organizations as $organization) {
//            $organization->library->cleanKnowledgePool($organization->assistantDepartment);
//            $this->output->progressAdvance();
//        }
//        $this->outputLine('');
//        $this->outputLine('Done');
    }
}
