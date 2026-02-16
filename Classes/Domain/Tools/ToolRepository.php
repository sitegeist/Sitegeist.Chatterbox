<?php

namespace Sitegeist\Chatterbox\Domain\Tools;

class ToolRepository
{
    /**
     * @var array<string,array{className:string, description:string, options:mixed[]}> $toolConfig
     */
    private array $toolConfig = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->toolConfig = $settings['tools'] ?? [];
    }

    public function findAll(): ToolCollection
    {
        $tools = [];
        foreach ($this->toolConfig as $name => $config) {
            $tools[] = $this->instantiateTool($name);
        }
        return new ToolCollection(...array_filter($tools));
    }

    public function findByName(string $name): ToolContract|RemoteMCPServerTool|null
    {
        return $this->instantiateTool($name);
    }

    private function instantiateTool(string $name): ToolContract|RemoteMCPServerTool|null
    {
        if (!array_key_exists($name, $this->toolConfig)) {
            return null;
        }
        $class = $this->toolConfig[$name]['className'];
        if ($class === RemoteMCPServerTool::class) {
            return RemoteMCPServerTool::fromArray($name, $this->toolConfig[$name]['options']);
        }
        $description = $this->toolConfig[$name]['description'] ?? $name;
        $options = $this->toolConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, ToolContract::class, true)) {
            return $class::createFromConfiguration($name, $options);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the ToolContract');
        }
    }
}
