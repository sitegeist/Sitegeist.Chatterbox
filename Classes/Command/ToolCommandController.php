<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Symfony\Component\Yaml\Yaml;

#[Flow\Scope('singleton')]
class ToolCommandController extends CommandController
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
    ) {
        parent::__construct();
    }

    public function listCommand(string $organizationId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $tools = $organization->toolbox->findAll();
        foreach ($tools as $tool) {
            $this->outputLine($tool->getName());
        }
    }

    public function showCommand(string $organizationId, string $toolName,): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $tool = $organization->toolbox->findByName($toolName);
        if (!$tool) {
            $this->outputLine('no such tool');
            $this->quit();
        }
        try {
            $parameterSchema = $tool->getParameterSchema();
            $this->output(Yaml::dump(json_decode(json_encode($parameterSchema) ?: '', true)));
        } catch (\Exception $e) {
            $this->outputLine('Tool failed: ' . $e->getMessage());
            $this->quit();
        }
    }

    public function executeCommand(string $organizationId, string $toolName, string $parameters): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $tool = $organization->toolbox->findByName($toolName);
        if (!$tool) {
            $this->outputLine('no such tool');
            $this->quit();
        }
        try {
            $parametersDecoded = json_decode($parameters, true);
            $result = $tool->execute($parametersDecoded);
        } catch (\Exception $e) {
            $this->outputLine('Tool failed: ' . $e->getMessage());
            $this->quit();
        }
        $this->output(Yaml::dump([
            'data' => json_decode(json_encode($result->getData()) ?: '', true),
            'metadata' => json_decode(json_encode($result->getMetadata()) ?: '', true)
        ]));
    }
}
