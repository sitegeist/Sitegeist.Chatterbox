<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\Knowledge\Academy;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;

#[Flow\Scope('singleton')]
class KnowledgeCommandController extends CommandController
{
    public function __construct(
        private readonly KnowledgePool $knowledgePool,
        private readonly Academy $academy,
    ) {
        parent::__construct();
    }

    public function updatePoolCommand(): void
    {
        $this->outputLine('Updating knowledge pool');
        $sources = $this->knowledgePool->findAllSources();
        $this->output->progressStart(count($sources));
        foreach ($sources as $sourceOfKnowledge) {
            $this->academy->updateSourceOfKnowledge($sourceOfKnowledge);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->outputLine('');
        $this->outputLine('Done');
    }

    public function cleanPoolCommand(): void
    {
        $this->outputLine('Cleaning knowledge pool');
        $this->academy->cleanKnowledgePool();
        $this->outputLine('');
        $this->outputLine('Done');
    }
}
