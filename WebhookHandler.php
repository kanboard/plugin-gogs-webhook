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
                        $results[] = $this->handleOpenIssue($payload['repository'], $payload['issue']);
                    }
                    break;
                case 'label_updated':
                    $results[] = $this->handleLabelUpdated($payload['issue']);
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
     * @param   array   $repository
     * @param   array   $issue
     * @return  bool
     */
    public function handleOpenIssue(array $repository, array $issue)
    {
        $this->dispatcher->dispatch(
            self::EVENT_ISSUES_OPEN,
            new GenericEvent([
                'project_id'    =>  $this->project_id,
                'title'         =>  $issue['title'],
                'description'   =>  $issue['body'],
                'tags'          =>  self::getLabels($issue['labels']),
                'links'         =>  [
                    [
                        'title' =>  t('Issue on Gogs'),
                        'url'   =>  $repository['html_url'] . '/issues/' . $issue['number']
                    ]
                ],
                'reference'     =>  $repository['full_name'] . '#' . $issue['number'],
                'owner_id'      =>  (isset($issue['assignee']['username'])) ? $this->userModel->getIdByUsername($issue['assignee']['username']) : null,
                'date_due'      =>  (isset($issue['milestone']['due_on'])) ? $issue['milestone']['due_on'] : null
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
