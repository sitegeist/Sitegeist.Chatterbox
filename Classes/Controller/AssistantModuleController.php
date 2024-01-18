<?php
declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use OpenAI\Client;
use OpenAI\Responses\Assistants\AssistantResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use Sitegeist\Chatterbox\Domain\AssistantRecord;
use Sitegeist\Chatterbox\Domain\MessageRecord;

class AssistantModuleController extends  AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    #[Flow\Inject]
    protected Client $client;

    public function indexAction(): void
    {
        $assistants = array_map(
            fn(AssistantResponse $assistantResponse) => AssistantRecord::fromAssistantResponse($assistantResponse),
            $this->client->assistants()->list()->data
        );

        $this->view->assign('assistants', $assistants);
    }

    public function editAction(string $assistantId): void
    {
        $assistantResponse = $this->client->assistants()->retrieve($assistantId);
        $this->view->assign('assistant', AssistantRecord::fromAssistantResponse($assistantResponse));
    }

    public function updateAction(AssistantRecord $assistant): void
    {
        $this->client->assistants()->modify($assistant->id, ['name' => $assistant->name, 'description' => $assistant->description, 'instructions' => $assistant->instructions]);
        $this->addFlashMessage('Assistant ' . $assistant->name . ' was updated');
        $this->redirect('index');
    }

    public function newAction(): void
    {
    }

    public function createAction(string $name): void
    {
        $assistantResponse = $this->client->assistants()->create(['name' => $name, 'model' => 'gpt-4-1106-preview']);
        $this->redirect('edit', null, null, ['assistantId' => $assistantResponse->id]);
    }

    public function newThreadAction(string $assistantId): void
    {
        $this->view->assign('assistantId', $assistantId);
    }

    public function createThreadAction(string $assistantId, string $message): void
    {
        $runResponse = $this->client->threads()->createAndRun(['assistant_id' => $assistantId, "thread"=> ['messages' => [['role' => 'user', 'content' => $message]]]]);
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
        $data = $this->client->threads()->messages()->list($threadId)->data;

        $messages = array_reverse(array_map(
            fn(ThreadMessageResponse $threadMessageResponse) => MessageRecord::fromThreadMessageResponse($threadMessageResponse),
            $data
        ));

        $this->view->assignMultiple([
            'messages' => $messages,
            'threadId' => $threadId,
            'assistantId' => $assistantId,
        ]);
    }

    private function waitForRun(string $threadId, string $runId): void
    {
        $thread = $this->client->threads()->runs()->retrieve($threadId, $runId);
        while ($thread->status !== 'completed') {
            sleep (5);
            $thread = $this->client->threads()->runs()->retrieve($threadId, $runId);
        }
    }
}
