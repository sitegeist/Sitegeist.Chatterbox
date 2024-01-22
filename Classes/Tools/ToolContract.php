<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Tools;

use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Neos\Flow\Annotations as Flow;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentImageFileObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;

interface ToolContract
{
    /**
     * @param mixed[] $options
     */
    public static function createFromConfiguration(string $name, array $options): static;

    public function getDescription(): string;

    /**
     * @return mixed[]
     */
    public function getParameterSchema(): array;

    /**
     * @param mixed[] $parameters
     * @return mixed[]
     */
    public function execute(array $parameters): array|\JsonSerializable;
}
