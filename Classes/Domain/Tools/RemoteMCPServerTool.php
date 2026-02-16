<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Tools;

use Neos\Flow\Annotations as Flow;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

#[Flow\Proxy(false)]
final readonly class RemoteMCPServerTool
{
    public function __construct(
        public string $label,
        public string $description,
        public UriInterface $url,
        public string $requireApproval,
    ) {
    }

    /**
     * @param array<string,mixed> $values
     */
    public static function fromArray(string $name, array $values): self
    {
        return new self(
            label: $name,
            description: $values['description'] ?? $name,
            url: new Uri($values['url']),
            requireApproval: $values['requireApproval']
        );
    }

    public function getSchema(): array
    {
        return [
            'type' => 'mcp',
            'server_label' => $this->label,
            'server_description' => $this->description,
            'server_url' => (string)$this->url,
            'require_approval' => $this->requireApproval,
        ];
    }
}
