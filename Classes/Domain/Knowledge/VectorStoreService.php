<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;

class VectorStoreService
{
    /**
     * @Flow\InjectConfiguration(path="context")
     */
    protected string $context;

    /**
     * @Flow\Inject
     */
    protected Environment $environment;

    public function upload(OpenAiClientContract $client, SourceOfKnowledgeContract $sourceOfKnowledge): VectorStoreId
    {
        $uuid = Algorithms::generateUUID();

        $vectorStoreResponse = $client->vectorStores()->create([
            'name' => $sourceOfKnowledge->getName()->value . '-' . $this->context,
            'description' => $sourceOfKnowledge->getDescription(),
            'metadata' => [
                'creator' => "Sitegeist.Chatterbox",
                'knowledgeSourceName' => $sourceOfKnowledge->getName()->value,
                'knowledgeSourceIndexUuid' => $uuid
            ]
        ]);

        /**
         * @var Document $item
         */
        foreach ($sourceOfKnowledge->getContent() as $item) {
            if ($item->content === '') {
                continue;
            }

            try {
                $path = $this->environment->getPathToTemporaryDirectory() . '/' . $sourceOfKnowledge->getName()->value . '-' . $item->name . '.' . $item->type;
                \file_put_contents($path, (string)$item->content);

                $createFileResponse = $client->files()->upload([
                    'file' => fopen($path, 'r'),
                    'purpose' => 'user_data'
                ]);
                \unlink($path);

                $client->vectorStores()->files()->create(
                    $vectorStoreResponse->id,
                    [
                        'file_id' => $createFileResponse->id,
                        'attributes' => [
                            'creator' => "Sitegeist.Chatterbox",
                            'context' => $this->context,
                            'knowledgeSourceName' => $sourceOfKnowledge->getName()->value,
                            'knowledgeSourceIndexUuid' => $uuid
                        ]
                    ]
                );
            } catch (\Exception) {
            }
        }

        return new VectorStoreId($vectorStoreResponse->id);
    }

    public function cleanup(OpenAiClientContract $client, SourceOfKnowledgeContract $sourceOfKnowledge, VectorStoreId $vectorStoreIdToKeep): void
    {
        $vectorStores = $client->vectorStores()->list();
        foreach ($vectorStores->data as $vectorStore) {
            if (
                $vectorStore->name === ($sourceOfKnowledge->getName()->value . '-' . $this->context)
                && $vectorStore->id !== $vectorStoreIdToKeep->value
            ) {
                $files = $client->vectorStores()->files()->list($vectorStore->id);
                foreach ($files->data as $file) {
                    try {
                        $client->files()->delete($file->id);
                    } catch (\Exception) {
                    }
                }
                try {
                    $client->vectorStores()->delete($vectorStore->id);
                } catch (\Exception) {
                }
            }
        }
    }
}
