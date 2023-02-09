<li <?= $this->app->checkMenuSelection('EmailViewController', 'view') ?>>
    <?= $this->url->icon('envelope-o', t('Task Email'), 'EmailViewController', 'load', array('plugin' => 'mailmagik', 'task_id' => $task['id'], 'project_id' => $task['project_id']), false, 'mm-menu-item') ?>
</li>
