<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

class Library
{
    public function __construct(
        private readonly AssistantDepartment $assistantDepartment,
        private readonly OpenAiClientContract $client,
        private readonly Environment $environment,
        private readonly OrganizationDiscriminator $organizationDiscriminator,
    ) {
    }

    public function updateSourceOfKnowledge(SourceOfKnowledgeContract $sourceOfKnowledge): void
    {
        $content = $sourceOfKnowledge->getContent();
        $filename = KnowledgeFilename::forKnowledgeSource($sourceOfKnowledge->getName(), $this->organizationDiscriminator);
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
            $filename = KnowledgeFilename::tryFromSystemFileName($fileResponse->filename);
            if ($filename === null || $this->organizationDiscriminator->equals($filename->discriminator) === false) {
                continue;
            }
            if (!in_array($fileResponse->id, $usedFileIds)) {
                $this->client->files()->delete($fileResponse->id);
            }
        }
    }
}
