<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;

#[Flow\Scope('singleton')]
class KnowledgePool
{
    /**
     * @var array<string,array{className:string, options:array<string,mixed>}>
     */
    #[Flow\InjectConfiguration(path:'knowledge')]
    protected array $knowledgeConfig;

    public function findAllSources(): SourceOfKnowledgeCollection
    {
        $sources = [];
        foreach ($this->knowledgeConfig as $name => $config) {
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
        $class = $this->knowledgeConfig[$name]['className'];
        $options = $this->knowledgeConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, SourceOfKnowledgeContract::class, true)) {
            return $class::createFromConfiguration($name, $options);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the SourceOfKnowledgeContract');
        }
    }
}
