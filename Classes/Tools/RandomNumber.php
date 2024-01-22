<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Tools;

use Sitegeist\Chatterbox\Tools\ToolContract;
use Sitegeist\Chatterbox\Contracts\ToolResultContract;
use Sitegeist\Chatterbox\Contracts\ToolSpecificationContract;

class RandomNumber implements ToolContract
{
    /**
     * @param string $name
     * @param mixed[] $options
     */
    final public function __construct(
        public readonly string $name,
        public readonly array $options,
    ) {
    }

    public static function createFromConfiguration(string $name, array $options): static
    {
        return new static($name, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return "Generate random numbers";
    }

    public function getParameterSchema(): array
    {
        return [
            "min" => [
                "type" => "integer",
                "description" => "the minimal number"
            ],
            "max" => [
                "type" => "integer",
                "description" => "the maximal number"
            ]
        ];
    }

    /**
     * @param array{min:int, max:int} $parameters
     * @return array|\JsonSerializable|mixed[]
     */
    public function execute(array $parameters): array|\JsonSerializable
    {
        return ['number' => rand($parameters['min'], $parameters['max'])];
    }
}
