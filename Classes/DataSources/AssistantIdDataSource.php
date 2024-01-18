<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\DataSources;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;

class AssistantIdDataSource extends AbstractDataSource
{
    protected static $identifier = 'Sitegeist.Chatterbox:AssistantId';

    public function __construct(
        private readonly ClientContract $client
    ) {
    }

    /**
     * @param NodeInterface|null $node
     * @param array<string, mixed> $arguments
     * @return array|array[]|mixed
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $list = $this->client->assistants()->list(['limit' => 100]);
        return array_map(fn(AssistantResponse $item) => ['value' => $item->id, 'label' => $item->name], $list->data);
    }
}
