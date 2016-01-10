<?php

namespace Kanboard\Plugin\GogsWebhook\Controller;

use Kanboard\Controller\Base;
use Kanboard\Plugin\GogsWebhook\WebhookHandler;

/**
 * Webhook Controller
 *
 * @package  controller
 * @author   Frederic Guillot
 */
class Webhook extends Base
{
    /**
     * Handle Gogs webhooks
     *
     * @access public
     */
    public function handler()
    {
        $this->checkWebhookToken();

        $handler = new WebhookHandler($this->container);
        $handler->setProjectId($this->request->getIntegerParam('project_id'));

        $result = $handler->parsePayload(
            $this->request->getHeader('X-Gogs-Event'),
            $this->request->getJson()
        );

        echo $result ? 'PARSED' : 'IGNORED';
    }
}
