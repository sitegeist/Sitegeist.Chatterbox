<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\AssistantRecord;

#[Flow\Scope('singleton')]
class Academy
{
    public function __construct(
        private readonly AssistantDepartment $assistantDepartment,
        private readonly OpenAiClientContract $client,
        private readonly Environment $environment,
    ) {
    }

    public function upskillAssistant(string|AssistantRecord $assistant): void
    {
        $assistant = is_string($assistant) ? $this->assistantDepartment->findAssistantRecordById($assistant) : $assistant;
        $fileListResponse = $this->client->files()->list();
        $fileIds = [];
        foreach ($assistant->selectedSourcesOfKnowledge as $knowledgeSourceName) {
            $latestFilename = null;
            foreach ($fileListResponse->data as $fileResponse) {
                $knowledgeFilename = KnowledgeFilename::tryFromSystemFileName($fileResponse->filename);
                if ($knowledgeFilename && (!$latestFilename || $knowledgeFilename->isGreaterThan($latestFilename))) {
                    $latestFilename = $knowledgeFilename;
                    $fileIds[$knowledgeSourceName] = $fileResponse->id;
                }
            }
        }
        $this->client->assistants()->modify(
            $assistant->id,
            [
                'file_ids' => array_values($fileIds)
            ]
        );
    }

    public function updateSourceOfKnowledge(SourceOfKnowledgeContract $sourceOfKnowledge): void
    {
        $content = $sourceOfKnowledge->getContent();
        $filename = KnowledgeFilename::forKnowledgeSource($sourceOfKnowledge->getName());
        $path = $this->environment->getPathToTemporaryDirectory() . '/' . $filename->toSystemFilename();
        \file_put_contents($path, (string)$content);

        $this->client->files()->upload([
            'file' => fopen($path, 'r'),
            'purpose' => 'assistants'
        ]);
        \unlink($path);
    }

    public function cleanKnowledgePool(): void
    {
        $filesListResponse = $this->client->files()->list();

        $usedFileIds = [];
        foreach ($this->assistantDepartment->findAllRecords() as $assistant) {
            $usedFileIds = array_merge($usedFileIds, $assistant->fileIds);
        }

        foreach ($filesListResponse->data as $fileResponse) {
            if ($fileResponse->purpose === 'assistants' && !in_array($fileResponse->id, $usedFileIds)) {
                $this->client->files()->delete($fileResponse->id);
            }
        }
    }
}
