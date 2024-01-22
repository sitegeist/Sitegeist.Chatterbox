<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Mvc\Controller\ActionController;

class ChatController extends ActionController
{
    public function startAction(string $assistantId): void
    {
        // HTTP: POST
        // Antwort: {threadId:string}
    }

    public function historyAction(string $threadId): void
    {
        // HTTP: POST
        // Antwort: {'messages' : [{bot: bool, 'message': string, ...}] }
    }

    public function postAction(string $threadId, string $message): void
    {
        // HTTP: POST
        // Antwort: {bot: bool, 'message': string, ...}
    }
}
