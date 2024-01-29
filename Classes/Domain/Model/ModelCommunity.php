<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Model;

use OpenAI\Responses\Models\ListResponse;
use OpenAI\Responses\Models\RetrieveResponse;
use Traversable;

/**
 * @implements \IteratorAggregate<int, Model>
 */
final class ModelCommunity implements \IteratorAggregate
{
    /**
     * @var Model[]
     */
    public readonly array $members;

    public function __construct(Model ...$models)
    {
        $this->members = $models;
    }

    public static function fromApiResponse(ListResponse $data): static
    {
        return new static(
            ... array_map(
                fn(RetrieveResponse $item) => Model::fromApiResponse($item),
                $data->data
            )
        );
    }

    /**
     * @return Traversable<int,Model>
     */
    public function getIterator(): Traversable
    {
        yield from $this->members;
    }
}
