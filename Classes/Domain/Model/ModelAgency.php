<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Model;

use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\Model\Model;
use Sitegeist\Chatterbox\Domain\Model\ModelCommunity;

class ModelAgency
{
    public function __construct(
        private readonly OpenAiClientContract $client
    ) {
    }

    public function findAllAvailableModels(): ModelCommunity
    {
        $list = $this->client->models()->list();
        return ModelCommunity::fromApiResponse($list);
    }

    public function findOneById(string $id): Model
    {
        $model = $this->client->models()->retrieve($id);
        return Model::fromApiResponse($model);
    }
}
