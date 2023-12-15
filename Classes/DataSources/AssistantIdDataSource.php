<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\DataSources;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use OpenAI\Responses\Assistants\AssistantResponse;
use Psr\Http\Client\ClientInterface;

class AssistantIdDataSource extends AbstractDataSource
{
    protected static $identifier = 'Sitegeist.Chatterbox:AssistantId';

    #[Flow\InjectConfiguration(path: 'apis.openAi.token')]
    protected string $apiToken = '';

    public function __construct(
        private readonly ClientInterface $httpClient
    ) {
    }

    /**
     * @param NodeInterface|null $node
     * @param array<string, mixed> $arguments
     * @return array|array[]|mixed
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $client = \OpenAI::factory()
            ->withApiKey($this->apiToken)
            ->withOrganization(null)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v1')
            ->withHttpClient($this->httpClient)
            ->make();

        $list = $client->assistants()->list(['limit' => 100]);
        return array_map(fn(AssistantResponse $item) => ['value' => $item->id, 'label' => $item->name], $list->data);
    }
}
