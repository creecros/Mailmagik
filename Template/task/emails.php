        <li <?= $this->app->checkMenuSelection('EmailViewController', 'view') ?>>
            <?= $this->url->icon('envelope-o', t('Task Email'), 'EmailViewController', 'view', array('plugin' => 'kbphpimap', 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>
        </li>
