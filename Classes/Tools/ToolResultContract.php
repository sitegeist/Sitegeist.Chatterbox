<?php
declare(strict_types=1);

namespace Sitegeist\Chatterbox\Tools;

interface ToolResultContract {

    public function getData(): array|\JsonSerializable;

    public function getMetadata(): array|\JsonSerializable;
}
