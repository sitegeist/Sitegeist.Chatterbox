<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Tools;

use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\Tools\ToolResultContract;

interface ToolContract
{
    /**
     * @param mixed[] $options
     */
    public static function createFromConfiguration(string $name, string $description, array $options): static;

    public function getName(): string;

    public function getDescription(): string;

    /**
     * @return mixed[]
     */
    public function getParameterSchema(): array;

    /**
     * @param mixed[] $parameters
     */
    public function execute(array $parameters): ToolResultContract;
}
