<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\Instruction\Manual;
use Sitegeist\Chatterbox\Domain\Knowledge\Academy;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\Model\ModelAgency;
use Sitegeist\Chatterbox\Domain\Tools\Toolbox;

#[Flow\Scope('singleton')]
class AssistantModuleController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly Toolbox $toolbox,
        private readonly KnowledgePool $knowledgePool,
        private readonly Manual $manual,
        private readonly AssistantDepartment $assistantDepartment,
        private readonly Academy $academy,
        private readonly ModelAgency $modelAgency,
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
        $assistants = $this->assistantDepartment->findAllRecords();
        $this->view->assign('assistants', $assistants);
    }

    public function editAction(string $assistantId): void
    {
        $this->view->assignMultiple([
            'availableTools' => $this->toolbox->findAll(),
            'availableSourcesOfKnowledge' => $this->knowledgePool->findAllSources(),
            'availableInstructions' => $this->manual->findAll(),
            'assistant' => $this->assistantDepartment->findAssistantRecordById($assistantId),
            'models' => $this->modelAgency->findAllAvailableModels(),
        ]);
    }

    public function initializeUpdateAction(): void
    {
        $this->arguments['assistant']->getPropertyMappingConfiguration()->allowAllProperties();
    }

    public function updateAction(AssistantRecord $assistant): void
    {
        $this->assistantDepartment->updateAssistant($assistant);
        $this->academy->upskillAssistant($assistant);
        $this->addFlashMessage('Assistant ' . $assistant->name . ' was updated');
        $this->redirect('index');
    }

    public function newAction(): void
    {
    }

    public function createAction(string $name): void
    {
        $assistantResponse = $this->assistantDepartment->createAssistant($name);
        $this->redirect('edit', null, null, ['assistantId' => $assistantResponse->id]);
    }

    public function newThreadAction(string $assistantId): void
    {
        $this->view->assign('assistantId', $assistantId);
    }

    public function createThreadAction(string $assistantId, string $message): void
    {
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $threadId = $assistant->startThread();
        $this->forward('addThreadMessage', arguments: [
            'threadId' => $threadId,
            'assistantId' => $assistantId,
            'message' => $message,
            'withAdditionalInstructions' => true,
        ]);
    }

    public function addThreadMessageAction(string $threadId, string $assistantId, string $message, bool $withAdditionalInstructions = false): void
    {
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $assistant->continueThread($threadId, $message, $withAdditionalInstructions);

        $this->view->assignMultiple([
            'messages' => $this->fetchMessages($threadId),
            'threadId' => $threadId,
            'assistantId' => $assistantId,
            'metadata' => $assistant->getCollectedMetadata()
        ]);
    }

    public function showThreadAction(string $threadId, string $assistantId): void
    {
        $this->view->assignMultiple([
            'messages' => $this->fetchMessages($threadId),
            'threadId' => $threadId,
            'assistantId' => $assistantId,
        ]);
    }

    /**
     * @return array<MessageRecord>
     */
    private function fetchMessages(string $threadId): array
    {
        $threadMessageResponses = $this->client->threads()->messages()->list($threadId)->data;

        $threadMessageResponsesFiltered =  array_filter(
            $threadMessageResponses,
            fn(ThreadMessageResponse $threadMessageResponse) => ($threadMessageResponse->metadata['role'] ?? null) !== 'system'
        );

        return array_reverse(
            array_map(
                fn(ThreadMessageResponse $threadMessageResponse) => MessageRecord::fromThreadMessageResponse($threadMessageResponse),
                $threadMessageResponsesFiltered
            ),
        );
    }
}
