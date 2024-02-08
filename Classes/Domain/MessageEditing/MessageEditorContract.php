<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\MessageEditing;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use OpenAI\Contracts\ClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Sitegeist\Chatterbox\Domain\Assistant;

interface MessageEditorContract
{
    /**
     * @param mixed[] $options
     */
    public static function createFromConfiguration(string $name, array $options, ClientContract $client, ObjectManagerInterface $objectManager): static;

    public function getName(): string;

    public function getDescription(): string;

    /**
     * @param array<string,mixed> $parameters
     */
    public function execute(ThreadMessageResponse $threadMessageResponse, Assistant $assistant, array $parameters): MessageEditingResultContract;
}
