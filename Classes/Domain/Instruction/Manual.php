<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Instruction;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

#[Flow\Scope('singleton')]
class Manual
{
    /**
     * @var array<string,array{className:string, options:array<string,mixed>}>
     */
    #[Flow\InjectConfiguration(path:'instructions')]
    protected array $instructionConfig;

    public function __construct(
        private readonly ObjectManagerInterface $container
    ) {
    }

    public function findAll(): InstructionCollection
    {
        $instructions = [];
        foreach ($this->instructionConfig as $name => $config) {
            $instructions[] = $this->instantiateInstruction($name);
        }
        return new InstructionCollection(...$instructions);
    }

    public function findInstructionByName(string $name): ?InstructionContract
    {
        return $this->instantiateInstruction($name);
    }

    private function instantiateInstruction(string $name): InstructionContract
    {
        $class = $this->instructionConfig[$name]['className'];
        $options = $this->instructionConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, InstructionContract::class, true)) {
            return $class::createFromConfiguration($name, $options, $this->container);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the InstructionContract');
        }
    }
}
