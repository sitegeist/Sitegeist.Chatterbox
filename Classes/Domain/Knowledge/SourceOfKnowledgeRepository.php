<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Environment;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;

class SourceOfKnowledgeRepository
{
    /**
     * @var array<string,array{className:string, options:array<string,mixed>}>
     */
    private array $sourceOfKnowledgeConfig = [];

    public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->sourceOfKnowledgeConfig = $settings['knowledge'] ?? [];
    }

    public function findAll(): SourceOfKnowledgeCollection
    {
        $sources = [];
        foreach ($this->sourceOfKnowledgeConfig as $name => $config) {
            $sources[] = $this->instantiateSourceOfKnowledge($name);
        }
        return new SourceOfKnowledgeCollection(...$sources);
    }

    public function findSourceByName(string $name): ?SourceOfKnowledgeContract
    {
        return $this->instantiateSourceOfKnowledge($name);
    }

    private function instantiateSourceOfKnowledge(string $name): SourceOfKnowledgeContract
    {
        $class = $this->sourceOfKnowledgeConfig[$name]['className'];
        $options = $this->sourceOfKnowledgeConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, SourceOfKnowledgeContract::class, true)) {
            return $class::createFromConfiguration($name, $options);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the SourceOfKnowledgeContract');
        }
    }
}
