<?php

namespace Kanboard\Plugin\GogsWebhook;

use Kanboard\Core\Base;
use Kanboard\Event\GenericEvent;

/**
 * Gogs Webhook
 *
 * @author   Frederic Guillot
 */
class WebhookHandler extends Base
{
    /**
     * Events
     *
     * @var string
     */
    const EVENT_COMMIT = 'gogs.webhook.commit';

    /**
     * Project id
     *
     * @access private
     * @var integer
     */
    private $project_id = 0;

    /**
     * Set the project id
     *
     * @access public
     * @param  integer   $project_id   Project id
     */
    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;
    }

    /**
     * Parse incoming events
     *
     * @access public
     * @param  string  $type      Gogs event type
     * @param  array   $payload   Gogs event
     * @return boolean
     */
    public function parsePayload($type, array $payload)
    {
        if ($type === 'push') {
            return $this->handlePush($payload);
        }

        return false;
    }

    /**
     * Parse push events
     *
     * @access public
     * @param  array   $payload
     * @return boolean
     */
    public function handlePush(array $payload)
    {
        $results = array();

        if (isset($payload['commits'])) {
            foreach ($payload['commits'] as $commit) {
                $results[] = $this->handleCommit($commit);
            }
        }

        return in_array(true, $results, true);
    }

    /**
     * Parse commit
     *
     * @access public
     * @param  array   $commit   Gogs commit
     * @return boolean
     */
    public function handleCommit(array $commit)
    {
        $task_id = $this->taskModel->getTaskIdFromText($commit['message']);

        if (empty($task_id)) {
            return false;
        }

        $task = $this->taskFinderModel->getById($task_id);

        if (empty($task)) {
            return false;
        }

        if ($task['project_id'] != $this->project_id) {
            return false;
        }

        $this->dispatcher->dispatch(
            self::EVENT_COMMIT,
            new GenericEvent(array(
                'task_id' => $task_id,
                'commit_message' => $commit['message'],
                'commit_url' => $commit['url'],
                'comment' => $commit['message']."\n\n[".t('Commit made by @%s on Gogs', $commit['author']['name'] ?: $commit['author']['username']).']('.$commit['url'].')',
            ) + $task)
        );

        return true;
    }
}
