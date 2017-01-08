<?php

namespace Kanboard\Plugin\GogsWebhook;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        $this->actionManager->getAction('\Kanboard\Action\CommentCreation')->addEvent(WebhookHandler::EVENT_COMMIT);
        $this->actionManager->getAction('\Kanboard\Action\TaskClose')->addEvent(WebhookHandler::EVENT_COMMIT);
        $this->template->hook->attach('template:project:integrations', 'GogsWebhook:project/integrations');
        $this->route->addRoute('/webhook/gogs/:project_id/:token', 'WebhookController', 'handler', 'GogsWebhook');
        $this->applicationAccessMap->add('WebhookController', 'handler', Role::APP_PUBLIC);
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
        $this->eventManager->register(WebhookHandler::EVENT_COMMIT, t('Gogs commit received'));
    }

    public function getPluginName()
    {
        return 'Gogs Webhook';
    }

    public function getPluginDescription()
    {
        return t('Bind Gogs webhook events to Kanboard automatic actions');
    }

    public function getPluginAuthor()
    {
        return 'Frédéric Guillot';
    }

    public function getPluginVersion()
    {
        return '1.0.4';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/plugin-gogs-webhook';
    }

    public function getCompatibleVersion()
    {
        return '>=1.0.37';
    }
}
