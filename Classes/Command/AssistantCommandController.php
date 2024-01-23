<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\Knowledge\Academy;

#[Flow\Scope('singleton')]
class AssistantCommandController extends CommandController
{
    public function __construct(
        private readonly Academy $academy,
        private readonly AssistantDepartment $assistantDepartment,
    ) {
        parent::__construct();
    }

    public function upskillCommand(string $assistantId): void
    {
        $this->academy->upskillAssistant($assistantId);
    }

    public function upskillAllCommand(): void
    {
        foreach ($this->assistantDepartment->findAllRecords() as $assistant) {
            $this->academy->upskillAssistant($assistant);
        }
    }
}
