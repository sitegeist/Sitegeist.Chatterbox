<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Flow\OpenAiClientFactory\OpenAiClientFactory;

#[Flow\Scope('singleton')]
class KnowledgeCommandController extends CommandController
{
    private readonly OpenAiClientContract $client;

    public function __construct(
        private readonly KnowledgePool $knowledgePool,
        private readonly Environment $environment,
        OpenAiClientFactory $clientFactory
    ) {
        $this->client = $clientFactory->createClient();
        parent::__construct();
    }

    public function updatePoolCommand(): void
    {
        $this->outputLine('Updating knowledge pool');
        $sources = $this->knowledgePool->findAllSources();
        $this->output->progressStart(count($sources));
        foreach ($sources as $sourceOfKnowledge) {
            $content = $sourceOfKnowledge->getContent();

            $path = $this->environment->getPathToTemporaryDirectory() . '/' . $sourceOfKnowledge->getName() . '-' . time() . '.jsonl';
            \file_put_contents($path, (string)$content);

            $this->client->files()->upload([
                'file' => fopen($path, 'r'),
                'purpose' => 'assistants'
            ]);
            \unlink($path);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->outputLine('');
    }
}
