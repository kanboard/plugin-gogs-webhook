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
    const EVENT_ISSUES_OPEN = 'gogs.webbook.issues.open';

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
        switch ($type) {
            case 'push':
                return $this->handlePush($payload);
            case 'issues':
                return $this->handleIssues($payload);
            default:
                return false;
        }
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
     * Parse issues events
     *
     * @access  public
     * @param   array   $payload
     * @return  boolean
     */
    public function handleIssues(array $payload)
    {
        $results = [];

        if (isset($payload['action'])) {
            switch ($payload['action']) {
                case 'opened':
                    if (isset($payload['issue'])) {
                        $results[] = $this->handleOpenIssue($payload);
                    }
                    break;
            }
        }

        return in_array(true, $results, true);
    }

    /**
     * Retrieves a list of labels
     *
     * @param   array   $labels
     * @return  array
     */
    private function getLabels(array $labels)
    {
        return array_values(array_filter(array_map(
            function ($label) {
                if (isset($label['name'])) {
                    return $label['name'];
                }
                return null;
            },
            $labels
        )));
    }

    /**
     * Handles an issue being opened on Gogs
     *
     * @param   array   $payload
     * @return  bool
     */
    public function handleOpenIssue(array $payload)
    {
        $this->dispatcher->dispatch(
            self::EVENT_ISSUES_OPEN,
            new GenericEvent([
                'project_id'    =>  $this->project_id,
                'title'         =>  $payload['issue']['title'],
                'description'   =>  $payload['issue']['body'],
                'tags'          =>  self::getLabels($payload['issue']['labels']),
                'links'         =>  [
                    [
                        'title' =>  t('Issue on Gogs'),
                        'url'   =>  $payload['repository']['html_url'] . '/issues/' . $payload['issue']['number']
                    ]
                ],
                'reference'     =>  $payload['repository']['full_name'] . '#' . $payload['issue']['number'],
                'owner_id'      =>  (isset($payload['issue']['assignee']['username'])) ? $this->userModel->getIdByUsername($payload['issue']['assignee']['username']) : null,
                'date_due'      =>  (isset($payload['issue']['milestone']['due_on'])) ? $payload['issue']['milestone']['due_on'] : null
            ])
        );

        return true;
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
