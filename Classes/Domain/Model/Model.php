<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Model;

use OpenAI\Responses\Models\RetrieveResponse;

final class Model
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $ownedBy,
    ) {
    }

    public static function fromApiResponse(RetrieveResponse $data): static
    {
        return new static(
            $data->id,
            $data->id,
            $data->ownedBy,
        );
    }
}
