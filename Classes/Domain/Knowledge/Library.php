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
    /**
     * @param array<string,array{className:string, options:array<string,mixed>}> $sourceOfKnowledgeConfig
     */
    public function __construct(
        private readonly array $sourceOfKnowledgeConfig,
        private readonly AssistantDepartment $assistantDepartment,
        private readonly OpenAiClientContract $client,
        private readonly Environment $environment,
        private readonly OrganizationDiscriminator $organizationDiscriminator,
    ) {
    }

    public function findAllSourcesOfKnowledge(): SourceOfKnowledgeCollection
    {
        $sources = [];
        foreach ($this->sourceOfKnowledgeConfig as $name => $config) {
            $sources[] = $this->instantiateSourceOfKnowledge($name);
        }
        return new SourceOfKnowledgeCollection(...$sources);
    }

    public function findSourceByName(string $name): ?SourceOfKnowledgeContract
    {
        return $this->instantiateSourceOfKnowledge($name);
    }

    public function updateAllSourcesOfKnowledge(): void
    {
        foreach ($this->findAllSourcesOfKnowledge() as $sourceOfKnowledge) {
            $this->updateSourceOfKnowledge($sourceOfKnowledge);
        }
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

    private function instantiateSourceOfKnowledge(string $name): SourceOfKnowledgeContract
    {
        $class = $this->sourceOfKnowledgeConfig[$name]['className'];
        $options = $this->knowledgeConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, SourceOfKnowledgeContract::class, true)) {
            return $class::createFromConfiguration($name, $options);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the SourceOfKnowledgeContract');
        }
    }
}