<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Model;

use OpenAI\Responses\Models\ListResponse;
use OpenAI\Responses\Models\RetrieveResponse;
use Traversable;

/**
 * @implements \IteratorAggregate<int, Model>
 */
final class ModelCollection implements \IteratorAggregate
{
    /**
     * @var Model[]
     */
    public readonly array $members;

    public function __construct(Model ...$models)
    {
        $this->members = $models;
    }

    public static function fromStringArray(array $models): static
    {
        return new static(
            ... array_map(
                fn(string $item) => new Model($item, $item, null),
                $models
            )
        );
    }

    public static function fromApiResponse(ListResponse $data): static
    {
        $models = array_map(
            fn(RetrieveResponse $item) => Model::fromApiResponse($item),
            $data->data
        );
        usort($models, fn(Model $a, Model $b) => $a->id <=> $b->id);
        return new static(...$models);
    }

    /**
     * @return Traversable<int,Model>
     */
    public function getIterator(): Traversable
    {
        yield from $this->members;
    }
}
