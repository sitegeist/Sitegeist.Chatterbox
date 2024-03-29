<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\OrganizationRepository;

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
        try {
            $assistantResponse = $organization->assistantDepartment->createAssistant($name, $model);
            $this->redirect(
                actionName: 'edit',
                arguments: [
                    'assistantId' => $assistantResponse->id,
                    'organizationId' => $organizationId
                ]
            );
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', Message::SEVERITY_ERROR);
            $this->redirect(
                actionName: 'index',
                arguments: [
                    'organizationId' => $organizationId
                ]
            );
        }
    }

    public function editAction(string $organizationId, string $assistantId): void
    {
        $organization = $this->organizationRepository->findById($organizationId);
        $this->view->assignMultiple([
            'organizationId' => $organizationId,
            'availableTools' => $organization->toolbox->findAll(),
            'availableSourcesOfKnowledge' => $organization->library->findAllSourcesOfKnowledge(),
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
            $assistant->continueThread($threadId, $message);
            $metadata = $assistant->getCollectedMetadata();
            $this->view->assignMultiple([
                'organizationId' => $organizationId,
                'messages' => $assistant->readThread($threadId),
                'threadId' => $threadId,
                'assistantId' => $assistantId,
                'metadata' => empty($metadata) ? null : $metadata
            ]);
        } catch (\Exception $e) {
            $this->addFlashMessage('API-Error. I will reload.', 'Something went wrong', Message::SEVERITY_WARNING);
            $this->logger->warning('API-Error. I will reload.', ['exception' => $e->getMessage(), 'organizationId' => $organizationId, 'threadId' =>  $threadId, 'assistantId' =>  $assistantId, 'message' => $message]);
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
