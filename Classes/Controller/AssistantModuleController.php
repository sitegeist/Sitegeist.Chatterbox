<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Sitegeist\Chatterbox\Domain\AssistantEntity;
use Sitegeist\Chatterbox\Domain\AssistantEntityRepository;
use Sitegeist\Chatterbox\Domain\AssistantFactory;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\Instruction\InstructionRepository;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeRepository;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Sitegeist\Chatterbox\Domain\Tools\ToolRepository;
use Sitegeist\Flow\OpenAiClientFactory\AccountRepository;

#[Flow\Scope('singleton')]
class AssistantModuleController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    public function __construct(
        private readonly AssistantEntityRepository $assistantEntityRepository,
        private readonly ToolRepository $toolRepository,
        private readonly InstructionRepository $instructionRepository,
        private readonly SourceOfKnowledgeRepository $sourceOfKnowledgeRepository,
        private readonly AssistantFactory $assistantFactory,
        private readonly AccountRepository $accountRepository,
    ) {
    }

    /**
     * @param FusionView $view
     */
    public function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->setFusionPathPattern('resource://@package/Private/BackendFusion');
    }

    public function indexAction(): void
    {
        $assistants = $this->assistantEntityRepository->findAll();
        $this->view->assignMultiple([
            'assistants' => $assistants,
        ]);
    }

    public function newAction(): void
    {
        $this->view->assignMultiple([
            'availableAccounts' => $this->accountRepository->findAll(),
        ]);
    }

    public function createAction(string $account, string $name): void
    {
        $assistant = new AssistantEntity();
        $assistant->setAccount($account);
        $assistant->setName($name);

        $this->assistantEntityRepository->add($assistant);

        $this->addFlashMessage('Assistant %s was created!', '', Message::SEVERITY_OK, [$name]);
        $this->redirect(
            actionName: 'edit',
            arguments: ['assistant' => $assistant],
        );
    }

    public function editAction(AssistantEntity $assistant): void
    {
        $assistantObject = $this->assistantFactory->createAssistantFromAssistantEntity($assistant);
        $this->view->assignMultiple([
            'availableAccounts' => $this->accountRepository->findAll(),
            'availableModels' => $assistantObject->getAvailableModels(),
            'availableTools' => $this->toolRepository->findAll(),
            'availableSourcesOfKnowledge' => $this->sourceOfKnowledgeRepository->findAll(),
            'availableInstructions' => $this->instructionRepository->findAll(),
            'assistant' => $assistant,
        ]);
    }

    public function deleteAction(AssistantEntity $assistant): void
    {
        $this->assistantEntityRepository->remove($assistant);
        $this->addFlashMessage('Assistant %s was deleted!', '', Message::SEVERITY_OK, [$assistant->getName()]);
        $this->redirect(actionName: 'index');
    }

    public function updateAction(AssistantEntity $assistant): void
    {
        $this->assistantEntityRepository->update($assistant);
        $this->addFlashMessage('Assistant ' . $assistant->getName() . ' was updated');
        $this->redirect(actionName: 'index');
    }

    public function newThreadAction(AssistantEntity $assistant): void
    {
        $this->view->assignMultiple([
            'assistant' => $assistant,
        ]);
    }

    public function createThreadAction(AssistantEntity $assistant, string $message): void
    {
        $assistantObject = $this->assistantFactory->createAssistantFromAssistantEntity($assistant);

        $threadId = $assistantObject->startThread();
        $this->forward(
            actionName: 'addThreadMessage',
            arguments: [
                'threadId' => $threadId,
                'assistant' => $assistant,
                'message' => $message,
                'withAdditionalInstructions' => true,
            ]
        );
    }

    public function addThreadMessageAction(AssistantEntity $assistant, string $threadId, string $message, bool $withAdditionalInstructions = false): void
    {
        $assistantObject = $this->assistantFactory->createAssistantFromAssistantEntity($assistant);

        try {
            $assistantObject->continueThread($threadId, $message);
            $metadata = $assistantObject->getCollectedMetadata();
            $this->view->assignMultiple([
                'messages' => $assistantObject->readThread($threadId),
                'threadId' => $threadId,
                'assistant' => $assistant,
                'metadata' => empty($metadata) ? null : $metadata
            ]);
        } catch (\Exception $e) {
            $this->addFlashMessage('API-Error. I will reload.', 'Something went wrong', Message::SEVERITY_WARNING);
            $this->logger->warning('API-Error. I will reload.', ['exception' => $e->getMessage(), 'threadId' =>  $threadId, 'assistant' =>  $assistant, 'message' => $message]);
            $this->redirect(
                'showThread',
                null,
                null,
                [
                    'threadId' => $threadId,
                    'assistant' => $assistant,
                ]
            );
        }
    }

    public function showThreadAction(AssistantEntity $assistant, string $threadId): void
    {
        $assistantObject = $this->assistantFactory->createAssistantFromAssistantEntity($assistant);

        $this->view->assignMultiple([
            'messages' => $assistantObject->readThread($threadId),
            'threadId' => $threadId,
            'assistant' => $assistant,
        ]);
    }
}
