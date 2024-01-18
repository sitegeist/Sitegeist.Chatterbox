<?php
declare(strict_types=1);

namespace Sitegeist\Chatterbox\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\Module\AbstractModuleController;
use OpenAI\Client;
use OpenAI\Responses\Assistants\AssistantResponse;
use Sitegeist\Chatterbox\Domain\AssistantRecord;

class AssistantModuleController extends  AbstractModuleController
{
    protected $defaultViewObjectName = FusionView::class;

    #[Flow\Inject]
    protected Client $client;

    public function indexAction():void
    {
        $assistants = array_map(
            fn(AssistantResponse $assistantResponse) => AssistantRecord::fromAssistantResponse($assistantResponse),
            $this->client->assistants()->list()->data
        );

        $this->view->assign('assistants', $assistants);
    }

    public function editAction(string $assistantId):void
    {
        $assistantResponse = $this->client->assistants()->retrieve($assistantId);
        $this->view->assign('assistant', AssistantRecord::fromAssistantResponse($assistantResponse));
    }

    public function updateAction(AssistantRecord $assistant):void
    {
        $this->client->assistants()->modify($assistant->id, ['name' => $assistant->name, 'description' => $assistant->description, 'instructions' => $assistant->instructions]);
        $this->addFlashMessage('Assistant ' . $assistant->name . ' was updated');
        $this->redirect('index');
    }

    public function newAction():void
    {
    }

    public function createAction(string $name):void
    {
        $assistantResponse = $this->client->assistants()->create(['name' => $name, 'model' => 'gpt-4-1106-preview']);
        $this->redirect('edit', null, null, ['assistantId' => $assistantResponse->id]);
    }

}
