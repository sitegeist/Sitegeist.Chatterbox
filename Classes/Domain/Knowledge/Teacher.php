<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Neos\Flow\Annotations as Flow;
use Sitegeist\Chatterbox\Domain\AssistantRecord;

#[Flow\Scope('singleton')]
class Teacher
{
    public function teachAssistant(AssistantRecord $record): void
    {


    }

}
