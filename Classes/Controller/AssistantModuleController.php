<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use OpenAI\Contracts\ClientContract as OpenAiClientContract;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Runs\Steps\ThreadRunStepResponseMessageCreationStepDetails;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseRequiredActionFunctionToolCall;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseToolFunction;
use Psr\Log\LoggerInterface;
use Sitegeist\Chatterbox\Domain\AssistantDepartment;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\Knowledge\Academy;
use Sitegeist\Chatterbox\Domain\Knowledge\KnowledgePool;
use Sitegeist\Chatterbox\Domain\MessageRecord;
use Sitegeist\Chatterbox\Domain\Toolbox;
use Sitegeist\Chatterbox\Tools\ToolContract;

class AssistantModuleController extends AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    public function __construct(
        private readonly OpenAiClientContract $client,
        private readonly Toolbox $toolbox,
        private readonly KnowledgePool $knowledgePool,
        private readonly AssistantDepartment $assistantDepartment,
        private readonly Academy $academy,
    ) {
    }

    public function indexAction(): void
    {
        $assistants = $this->assistantDepartment->findAll();
        $this->view->assign('assistants', $assistants);
    }

    public function editAction(string $assistantId): void
    {
        $assistant = $this->assistantDepartment->findAssistantById($assistantId);
        $this->view->assign('availableTools', $this->toolbox->findAll());
        $this->view->assign('availableSourcesOfKnowledge', $this->knowledgePool->findAllSources());
        $this->view->assign('assistant', $assistant);
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
        $runResponse = $this->client->threads()->createAndRun([
            'assistant_id' => $assistantId,
            "thread" => [
                'messages' => [
                    ['role' => 'user', 'content' => 'current date: ' . (new \DateTimeImmutable())->format('Y-m-d'), 'metadata' => ['role' => 'system']],
                    ['role' => 'user', 'content' => $message]
                ]
            ]
        ]);
        $this->waitForRun($runResponse->threadId, $runResponse->id);
        $this->redirect(actionName: 'showThread', arguments: ['threadId' => $runResponse->threadId, 'assistantId' => $assistantId]);
    }

    public function addThreadMessageAction(string $threadId, string $assistantId, string $message): void
    {
        $this->client->threads()->messages()->create($threadId, ['role' => 'user', 'content' => $message]);
        $runResponse = $this->client->threads()->runs()->create($threadId, ['assistant_id' => $assistantId]);
        $this->waitForRun($threadId, $runResponse->id);
        $this->redirect(actionName: 'showThread', arguments: ['threadId' => $threadId, 'assistantId' => $assistantId]);
    }

    public function showThreadAction(string $threadId, string $assistantId): void
    {
        $threadMessageResponses = $this->client->threads()->messages()->list($threadId)->data;

        $threadMessageResponsesFiltered =  array_filter(
            $threadMessageResponses,
            fn(ThreadMessageResponse $threadMessageResponse) => ($threadMessageResponse->metadata['role'] ?? null) !== 'system'
        );

        $messages = array_reverse(
            array_map(
            fn(ThreadMessageResponse $threadMessageResponse) => MessageRecord::fromThreadMessageResponse($threadMessageResponse),
                $threadMessageResponsesFiltered
            ),
        );

        $this->view->assignMultiple([
            'messages' => $messages,
            'threadId' => $threadId,
            'assistantId' => $assistantId,
        ]);
    }

    private function waitForRun(string $threadId, string $runId): void
    {
        $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        $combinedMetadata = [];
        while ($threadRunResponse->status !== 'completed') {
            if ($threadRunResponse->status === 'requires_action') {
                if ($threadRunResponse->requiredAction?->submitToolOutputs) {
                    $this->logger->info("chatbot tool calls", $threadRunResponse->requiredAction?->submitToolOutputs->toArray());
                    $toolOutputs = [];
                    foreach ($threadRunResponse->requiredAction->submitToolOutputs->toolCalls as $requiredToolCall) {
                        if ($requiredToolCall instanceof ThreadRunResponseRequiredActionFunctionToolCall) {
                            $toolInstance = $this->toolbox->findByName($requiredToolCall->function->name);
                            if ($toolInstance instanceof ToolContract) {
                                $toolResult = $toolInstance->execute(json_decode($requiredToolCall->function->arguments, true));
                                $toolOutputs["tool_outputs"][] = ['tool_call_id' => $requiredToolCall->id, 'output' => json_encode($toolResult->getData())];
                                $combinedMetadata[] = ['tool_call_id' => $requiredToolCall->id, 'data' => $toolResult->getData(), 'metadata' => $toolResult->getMetadata()];
                            }
                        }
                    }
                    $this->logger->info("chatbot tool submit", $toolOutputs);
                    $this->client->threads()->runs()->submitToolOutputs($threadId, $runId, $toolOutputs);
                }
            }
            sleep(1);
            $threadRunResponse = $this->client->threads()->runs()->retrieve($threadId, $runId);
        }
        $this->logger->info("thread run response", $threadRunResponse->toArray());

//        // add tool metadata
//        if ($combinedMetadata) {
//            $stepList = $this->client->threads()->runs()->steps()->list($threadId, $threadRunResponse->id);
//            foreach ($stepList->data as $stepResponse) {
//                $stepDetails = $stepResponse->stepDetails;
//                if ($stepDetails instanceof ThreadRunStepResponseMessageCreationStepDetails) {
//                    $messageId = $stepDetails->messageCreation->messageId;
//                    if ($messageId) {
//                        $this->client->threads()->messages()->modify($threadId, $messageId, ['metadata' => ['tools' => json_encode($combinedMetadata)]]);
//                        break;
//                    }
//                }
//            }
//        }
    }
}
