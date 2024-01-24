<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Instruction;

use Psr\Container\ContainerInterface;

final class CurrentDateInstruction implements InstructionContract
{
    public function __construct(
        private readonly string $name,
        private readonly string $description,
    ) {
    }

    public static function createFromConfiguration(string $name, array $options, ContainerInterface $container): static
    {
        return new static(
            $name,
            $options['description'] ?? ''
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContent(): string
    {
        return 'current date: ' . (new \DateTimeImmutable())->format('Y-m-d');
    }
}
