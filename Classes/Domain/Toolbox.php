<?php

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand;
use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Tools\ToolCollection;
use Sitegeist\Chatterbox\Tools\ToolContract;

class Toolbox
{
    /**
     * @var array<string,array{className:string, description:string, options:mixed[]}>
     */
    #[Flow\InjectConfiguration(path:'tools')]
    protected array $toolConfig;

    public function findAll(): ToolCollection
    {
        $tools = [];
        foreach ($this->toolConfig as $name => $config) {
            $tools[] = $this->instantiateTool($name);
        }
        return new ToolCollection(...$tools);
    }

    public function findByName(string $name): ?ToolContract
    {
        return $this->instantiateTool($name);
    }

    private function instantiateTool(string $name): ToolContract
    {
        $class = $this->toolConfig[$name]['className'];
        $description = $this->toolConfig[$name]['description'] ?? $name;
        $options = $this->toolConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, ToolContract::class, true)) {
            return $class::createFromConfiguration($name, $description, $options);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the ToolContract');
        }
    }
}
