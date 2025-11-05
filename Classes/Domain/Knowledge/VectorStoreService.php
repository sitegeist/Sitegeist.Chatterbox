<?php

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Sitegeist\Chatterbox\Domain\AssistantEntity;

class VectorStoreService
{
    /**
     * @var string
     * @Flow\InjectConfiguration(path="context")
     */
    protected string $context;

    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly Environment $environment,
    ) {
    }

    public function createStoreForKnowledgeSource(SourceOfKnowledgeContract $sourceOfKnowledge): VectorStoreId
    {

        $uuid = Algorithms::generateUUID();

        $vectorStoreResponse = $this->client->vectorStores()->create([
            'name' => $sourceOfKnowledge->getName()->value,
            'description' => $sourceOfKnowledge->getDescription(),
            'metadata' => [
                'creator' => "Sitegeist.Chatterbox",
                'context' => $this->context,
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

            $path = $this->environment->getPathToTemporaryDirectory() . '/' . $sourceOfKnowledge->getName()->value . '-' . $item->name . '.' . $item->type;
            \file_put_contents($path, (string)$item->content);

            $createFileResponse = $this->client->files()->upload([
                'file' => fopen($path, 'r'),
                'purpose' => 'user_data'
            ]);
            \unlink($path);

            $this->client->vectorStores()->files()->create(
                $vectorStoreResponse->id,
                [
                    'file_id' => $createFileResponse->id,
                    'attributes'  => [
                        'creator' => "Sitegeist.Chatterbox",
                        'context' => $this->context,
                        'knowledgeSourceName' => $sourceOfKnowledge->getName()->value,
                        'knowledgeSourceIndexUuid' => $uuid
                    ]
                ]
            );
        }

        return new VectorStoreId($vectorStoreResponse->id);
    }
}
