<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\Library;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;
use Sitegeist\Flow\OpenAiClientFactory\AccountRepository;

#[Flow\Scope('singleton')]
class AssistantModuleController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
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

    public function indexAction(string $organizationId = null): void
    {
        $organization = $organizationId
            ? $this->organizationRepository->findById($organizationId)
            : $this->organizationRepository->findFirst();

        $assistants = $organization?->assistantDepartment->findAllRecords() ?: [];
        $this->view->assignMultiple([
            'organizations' => $this->organizationRepository->findAll(),
            'organizationId' => $organization?->id,
            'assistants' => $assistants
        ]);
        if (!$organization) {
            $this->addFlashMessage(messageBody: 'No organizations configured', severity: Message::SEVERITY_WARNING);
        }
    }

    public function newAction(string $organizationId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $this->view->assignMultiple([
            'organizationId' => $organizationId,
            'models' => $organization->modelAgency->findAllAvailableModels()
        ]);
    }

    public function createAction(string $organizationId, string $name, string $model): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistantResponse = $organization->assistantDepartment->createAssistant($name, $model);
        $this->redirect(
            actionName: 'edit',
            arguments: [
                'assistantId' => $assistantResponse->id,
                'organizationId' => $organizationId
            ]
        );
    }

    public function editAction(string $organizationId, string $assistantId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $this->view->assignMultiple([
            'organizationId' => $organizationId,
            'availableTools' => $organization->toolbox->findAll(),
            'availableSourcesOfKnowledge' => $organization->knowledgePool->findAllSources(),
            'availableInstructions' => $organization->manual->findAll(),
            'assistant' => $organization->assistantDepartment->findAssistantRecordById($assistantId),
            'models' => $organization->modelAgency->findAllAvailableModels(),
        ]);
    }

    public function initializeUpdateAction(): void
    {
        $this->arguments['assistant']->getPropertyMappingConfiguration()->allowAllProperties();
    }

    public function updateAction(string $organizationId, AssistantRecord $assistant): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $organization->assistantDepartment->updateAssistant($assistant);
        $this->addFlashMessage('Assistant ' . $assistant->name . ' was updated');
        $this->redirect(actionName: 'index', arguments: ['organizationId' => $organizationId]);
    }

    public function newThreadAction(string $organizationId, string $assistantId): void
    {
        $this->view->assignMultiple([
            'organizationId' => $organizationId,
            'assistantId' => $assistantId,
        ]);
    }

    public function createThreadAction(string $organizationId, string $assistantId, string $message): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);
        $threadId = $assistant->startThread();
        $this->forward('addThreadMessage', arguments: [
            'organizationId' => $organizationId,
            'threadId' => $threadId,
            'assistantId' => $assistantId,
            'message' => $message,
            'withAdditionalInstructions' => true,
        ]);
    }

    public function addThreadMessageAction(string $organizationId, string $threadId, string $assistantId, string $message, bool $withAdditionalInstructions = false): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);
        try {
            $assistant->continueThread($threadId, $message, $withAdditionalInstructions);
            $this->view->assignMultiple([
                'organizationId' => $organizationId,
                'messages' => $assistant->readThread($threadId),
                'threadId' => $threadId,
                'assistantId' => $assistantId,
                'metadata' => $assistant->getCollectedMetadata()
            ]);
        } catch (\Exception) {
            $this->addFlashMessage('API-Error. I will reload.', 'Something went wrong', Message::SEVERITY_WARNING);
            $this->redirect(
                'showThread',
                null,
                null,
                [
                    'organizationId' => $organizationId,
                    'threadId' => $threadId,
                    'assistantId' => $assistantId,
                ]
            );
        }
    }

    public function showThreadAction(string $organizationId, string $threadId, string $assistantId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $assistant = $organization->assistantDepartment->findAssistantById($assistantId);

        $this->view->assignMultiple([
            'organizationId' => $organizationId,
            'messages' => $assistant->readThread($threadId),
            'threadId' => $threadId,
            'assistantId' => $assistantId,
        ]);
    }
}
