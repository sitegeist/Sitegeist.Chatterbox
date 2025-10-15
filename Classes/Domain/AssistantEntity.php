<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class AssistantEntity
{
    /**
     * @var string|null
     * @ORM\Column(nullable=true)
     */
    protected $model;

    /**
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     * @ORM\Column(nullable=true, type="text")
     */
    protected $description;

    /**
     * @var string|null
     * @ORM\Column(nullable=true, type="text")
     */
    protected $instructions;

    /**
     * @var string[]
     * @ORM\Column(nullable=true, type="simple_array")
     */
    protected $toolIdentifiers = [];

    /**
     * @var string[]
     * @ORM\Column(nullable=true, type="simple_array")
     */
    protected $knowledgeSourceIdentifiers = [];

    /**
     * @var string[]
     * @ORM\Column(nullable=true, type="simple_array")
     */
    protected $instructionIdentifiers = [];

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function setAccount(string $account): void
    {
        $this->account = $account;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): void
    {
        $this->instructions = $instructions;
    }

    /**
     * @return string[]
     */
    public function getToolIdentifiers(): array
    {
        return $this->toolIdentifiers;
    }

    /**
     * @param string[] $toolIdentifiers
     */
    public function setToolIdentifiers(array $toolIdentifiers): void
    {
        $this->toolIdentifiers = $toolIdentifiers;
    }

    /**
     * @return string[]
     */
    public function getKnowledgeSourceIdentifiers(): array
    {
        return $this->knowledgeSourceIdentifiers;
    }

    /**
     * @param string[] $knowledgeSourceIdentifiers
     */
    public function setKnowledgeSourceIdentifiers(array $knowledgeSourceIdentifiers): void
    {
        $this->knowledgeSourceIdentifiers = $knowledgeSourceIdentifiers;
    }

    /**
     * @return string[]
     */
    public function getInstructionIdentifiers(): array
    {
        return $this->instructionIdentifiers;
    }

    /**
     * @param string[] $instructionIdentifiers
     */
    public function setInstructionIdentifiers(array $instructionIdentifiers): void
    {
        $this->instructionIdentifiers = $instructionIdentifiers;
    }
}
