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
        private readonly AssistantDepartment $assistantDepartment,
    ) {
        parent::__construct();
    }

    public function upskillCommand(string $assistantId): void
    {
        $assistant = $this->assistantDepartment->findAssistantRecordById($assistantId);
        $this->assistantDepartment->updateAssistant($assistant);
    }

    public function upskillAllCommand(): void
    {
        foreach ($this->assistantDepartment->findAllRecords() as $assistant) {
            $this->assistantDepartment->updateAssistant($assistant);
        }
    }
}
