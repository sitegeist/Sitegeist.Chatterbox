<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

class Library
{
    public const TABLE_NAME = 'sitegeist_chatterbox_domain_file_entry';

    /**
     * @param array<string,array{className:string, options:array<string,mixed>}> $sourceOfKnowledgeConfig
     */
    public function __construct(
        private readonly array $sourceOfKnowledgeConfig,
        private readonly OpenAiClientContract $client,
        private readonly DatabaseConnection $databaseConnection,
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

    /**
     * @param array<string> $names
     */
    public function findSourcesByNames(array $names): SourceOfKnowledgeCollection
    {
        return new SourceOfKnowledgeCollection(...array_map(
            fn (string $name): SourceOfKnowledgeContract => $this->instantiateSourceOfKnowledge($name),
            array_intersect($names, array_keys($this->sourceOfKnowledgeConfig))
        ));
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
        $knowledgeSourceDiscriminator = new KnowledgeSourceDiscriminator(
            $this->organizationDiscriminator,
            $sourceOfKnowledge->getName()
        );
        $filename = KnowledgeFilename::forKnowledgeSource($knowledgeSourceDiscriminator);
        $vectorStoreName = VectorStoreName::forKnowledgeSource($knowledgeSourceDiscriminator);
        $this->databaseConnection->transactional(function () use ($content, $knowledgeSourceDiscriminator) {
            $this->databaseConnection->delete(
                self::TABLE_NAME,
                [
                    'knowledge_source_discriminator' => $knowledgeSourceDiscriminator->toString()
                ]
            );
            foreach ($content as $entry) {
                $this->databaseConnection->insert(
                    self::TABLE_NAME,
                    [
                        'id' => $entry->id,
                        'knowledge_source_discriminator' => $knowledgeSourceDiscriminator->toString(),
                        'content' => \trim(\json_encode($entry->content, JSON_THROW_ON_ERROR), '"'),
                    ]
                );
            }
        });

        $path = $this->environment->getPathToTemporaryDirectory() . '/' . $filename->toSystemFilename();
        \file_put_contents($path, (string)$content);

        $createFileResponse = $this->client->files()->upload([
            'file' => fopen($path, 'r'),
            'purpose' => 'assistants'
        ]);
        \unlink($path);

        $this->client->vectorStores()->create([
            'file_ids' => [
                $createFileResponse->id
            ],
            'name' => $vectorStoreName->toString(),
        ]);
    }

    public function cleanKnowledgePool(AssistantDepartment $assistantDepartment): void
    {
        $filesListResponse = $this->client->files()->list();
        $vectorStoreListResponse = $this->client->vectorStores()->list();

        $usedVectorStoreIds = [];
        $usedFileIds = [];
        foreach ($assistantDepartment->findAllRecords() as $assistant) {
            foreach ($assistant->toolResources['file_search']['vector_store_ids'] ?? [] as $vectorStoreId) {
                $usedVectorStoreIds[] = $vectorStoreId;
                foreach ($this->client->vectorStores()->files()->list($vectorStoreId)->data as $vectorStoreFileResponse) {
                    $usedFileIds[] = $vectorStoreFileResponse->id;
                }
            }
        }

        foreach ($filesListResponse->data as $fileResponse) {
            $filename = KnowledgeFilename::tryFromSystemFileName($fileResponse->filename);
            if (
                $filename === null
                || $this->organizationDiscriminator->equals($filename->knowledgeSourceDiscriminator->organizationDiscriminator) === false
            ) {
                continue;
            }
            if (!in_array($fileResponse->id, $usedFileIds)) {
                $this->client->files()->delete($fileResponse->id);
            }
        }

        foreach ($vectorStoreListResponse->data as $vectorStoreResponse) {
            $vectorStoreName = VectorStoreName::tryFromNullableString($vectorStoreResponse->name);
            if (
                $vectorStoreName === null
                || $this->organizationDiscriminator->equals($vectorStoreName->knowledgeSourceDiscriminator->organizationDiscriminator) === false
            ) {
                continue;
            }
            if (!in_array($vectorStoreResponse->id, $usedVectorStoreIds)) {
                $this->client->vectorStores()->delete($vectorStoreResponse->id);
            }
        }
    }

    private function instantiateSourceOfKnowledge(string $name): SourceOfKnowledgeContract
    {
        $class = $this->sourceOfKnowledgeConfig[$name]['className'];
        $options = $this->sourceOfKnowledgeConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, SourceOfKnowledgeContract::class, true)) {
            return $class::createFromConfiguration($name, $options);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the SourceOfKnowledgeContract');
        }
    }
}
