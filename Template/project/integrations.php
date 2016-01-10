<h3><img src="<?= $this->url->dir() ?>/plugins/GogsWebhook/gogs-icon.png"/>&nbsp;<?= t('Gogs webhooks') ?></h3>
<div class="listing">
<input type="text" class="auto-select" readonly="readonly" value="<?= $this->url->href('webhook', 'handler', array('plugin' => 'GogsWebhook', 'token' => $webhook_token, 'project_id' => $project['id']), false, '', true) ?>"/>
<p class="form-help"><a href="http://kanboard.net/plugins/gogs-webhook" target="_blank"><?= t('Help on Gogs webhooks') ?></a></p>
</div>