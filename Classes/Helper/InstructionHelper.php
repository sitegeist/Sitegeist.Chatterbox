<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Security\Cryptography\HashService;

class InstructionHelper implements ProtectedContextAwareInterface
{
    public function __construct(
        private readonly HashService $hashService
    ) {
    }

    public function signInstructionsWithHmac(string $instructions): string
    {
        return $this->hashService->appendHmac($instructions);
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
