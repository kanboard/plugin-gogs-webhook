<?php

require_once 'tests/units/Base.php';

use Kanboard\Event\GenericEvent;
use Kanboard\Plugin\GogsWebhook\WebhookHandler;
use Kanboard\Model\TaskCreationModel;
use Kanboard\Model\ProjectModel;

class WebhookHandlerTest extends Base
{
    public function testUnsupportedEvent()
    {
        $payload = json_decode(file_get_contents(__DIR__.'/fixtures/push.json'), true);
        $handler = new WebhookHandler($this->container);
        $this->assertFalse($handler->parsePayload('create', $payload));
    }

    public function testHandlePush()
    {
        $this->container['dispatcher']->addListener(WebhookHandler::EVENT_COMMIT, array($this, 'onCommit'));

        $tc = new TaskCreationModel($this->container);
        $p = new ProjectModel($this->container);
        $handler = new WebhookHandler($this->container);
        $payload = json_decode(file_get_contents(__DIR__.'/fixtures/push.json'), true);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $handler->setProjectId(1);

        // No task
        $this->assertFalse($handler->parsePayload('push', $payload));

        // Create task with the wrong id
        $this->assertEquals(1, $tc->create(array('title' => 'test1', 'project_id' => 1)));
        $this->assertFalse($handler->parsePayload('push', $payload));

        // Create task with the right id
        $this->assertEquals(2, $tc->create(array('title' => 'test2', 'project_id' => 1)));
        $this->assertTrue($handler->parsePayload('push', $payload));

        $called = $this->container['dispatcher']->getCalledListeners();
        $this->assertArrayHasKey(WebhookHandler::EVENT_COMMIT.'.WebhookHandlerTest::onCommit', $called);
    }

    public function onCommit(GenericEvent $event)
    {
        $data = $event->getAll();
        $this->assertEquals(1, $data['project_id']);
        $this->assertEquals(2, $data['task_id']);
        $this->assertEquals('test2', $data['title']);
        $this->assertEquals("Fix issue #2\n\n\n[Commit made by @Frederic Guillot on Gogs](http://192.168.99.100:3000/me/test/commit/6ed26f1acb801e8904f12b842b918dfd9d10417b)", $data['comment']);
        $this->assertEquals("Fix issue #2\n", $data['commit_message']);
        $this->assertEquals('http://192.168.99.100:3000/me/test/commit/6ed26f1acb801e8904f12b842b918dfd9d10417b', $data['commit_url']);
    }
}
