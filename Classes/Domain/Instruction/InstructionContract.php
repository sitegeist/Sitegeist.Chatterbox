<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Instruction;

use Psr\Container\ContainerInterface;

interface InstructionContract
{
    /**
     * @param array<string,mixed> $options
     */
    public static function createFromConfiguration(string $name, array $options, ContainerInterface $container): static;

    public function getName(): string;

    public function getDescription(): string;

    public function getContent(): string;
}
