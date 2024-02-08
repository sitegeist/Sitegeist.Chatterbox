<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\MessageEditing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use OpenAI\Contracts\ClientContract;

#[Flow\Proxy(false)]
class EditorialOffice
{
    /**
     * @param array<string,array{className:string, options:array<string,mixed>}> $messageEditorConfig
     */
    public function __construct(
        private readonly array $messageEditorConfig,
        private readonly ClientContract $client,
        private readonly ObjectManagerInterface $objectManager,
    ) {
    }

    public function findAll(): MessageEditorCollection
    {
        return new MessageEditorCollection(...array_filter(array_map(
            fn (string $messageEditorName): ?MessageEditorContract => $this->instantiateMessageEditor($messageEditorName),
            array_keys($this->messageEditorConfig)
        )));
    }

    public function findByName(string $name): ?MessageEditorContract
    {
        return $this->instantiateMessageEditor($name);
    }

    private function instantiateMessageEditor(string $name): ?MessageEditorContract
    {
        if (!array_key_exists($name, $this->messageEditorConfig)) {
            return null;
        }
        $class = $this->messageEditorConfig[$name]['className'];
        $options = $this->messageEditorConfig[$name]['options'] ?? [];
        if (class_exists($class) && is_a($class, MessageEditorContract::class, true)) {
            return $class::createFromConfiguration($name, $options, $this->client, $this->objectManager);
        } else {
            throw new \Exception('Class ' . $class . ' does not exist or does not implement the ToolContract');
        }
    }
}
