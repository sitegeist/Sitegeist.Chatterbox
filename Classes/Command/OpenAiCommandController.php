<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeContract;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\VectorStoreReferenceRepository;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Sitegeist\Flow\OpenAiClientFactory\AccountRepository;
use Sitegeist\Flow\OpenAiClientFactory\OpenAiClientFactory;

#[Flow\Scope('singleton')]
class OpenAiCommandController extends CommandController
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly OpenAiClientFactory $clientFactory,
        private readonly VectorStoreReferenceRepository $vectorStoreReferenceRepository,
    ) {
        parent::__construct();
    }

    public function deleteAllVectorStoresCommand(string $account): void
    {
        $account = $this->accountRepository->findById($account);

        if (!$this->output->askConfirmation("Are you sure (Y/n)")) {
            $this->quit(1);
        }

        $client = $this->clientFactory->createClientForAccountRecord($account);

        $vectorStores = $client->vectorStores()->list();
        $this->output->progressStart(count($vectorStores->data));
        foreach ($vectorStores->data as $vectorStore) {
            $this->output->progressAdvance();
            $client->vectorStores()->delete($vectorStore->id);
        }
        $this->output->progressFinish();

        // remove references aswell
        $this->vectorStoreReferenceRepository->removeAll();

        $this->outputLine();
    }

    public function deleteAllFilesCommand(string $account): void
    {
        $account = $this->accountRepository->findById($account);

        if (!$this->output->askConfirmation("Are you sure (Y/n)")) {
            $this->quit(1);
        }

        $client = $this->clientFactory->createClientForAccountRecord($account);

        $files = $client->files()->list();
        $this->output->progressStart(count($files->data));
        foreach ($files->data as $fileResponse) {
            $this->output->progressAdvance();
            $client->files()->delete($fileResponse->id);
        }
        $this->output->progressFinish();
        $this->outputLine();
    }
}
