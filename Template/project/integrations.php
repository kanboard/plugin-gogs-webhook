<h3><img src="<?= $this->url->dir() ?>plugins/GogsWebhook/gogs-icon.png"/>&nbsp;<?= t('Gogs webhooks') ?></h3>
<div class="panel">
    <input type="text" class="auto-select" readonly="readonly" value="<?= $this->url->href('WebhookController', 'handler', array('plugin' => 'GogsWebhook', 'token' => $webhook_token, 'project_id' => $project['id']), false, '', true) ?>"/>
    <p class="form-help"><a href="https://github.com/kanboard/plugin-gogs-webhook#documentation" target="_blank"><?= t('Help on Gogs webhooks') ?></a></p>
</div>
