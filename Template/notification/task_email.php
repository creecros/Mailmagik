<style>
table {
    table-layout: fixed;
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin-bottom: 20px;
}
tr {
    background: #fff;
    overflow: hidden;
    padding-bottom: .5em;
    padding-left: 3px;
    padding-right: 3px;
    padding-top: .5em;
    text-align: left;
}
tr.header {
    background: #fbfbfb;
    padding-bottom: .5em;
}
th, td {
    border: 1px solid #eee;
}
td {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>
<p><?= t('You got task-email!') ?></p>
<table cellpadding='5' cellspacing='1'>
    <tr class='header'>
        <th><?= t('Project') ?></th>
        <th><?= t('Task ID') ?></th>
        <th><?= t('Subject') ?></th>
        <th><?= t('Sender') ?></th>
        <th><?= t('Date') ?></th>
    </tr>
    <?php foreach ($tasks as $task): ?>
        <tr>
            <td><?= $task['project_name'] ?></td>
            <td><?= $this->url->link("{$task['id']}",'TaskViewController', 'show',
                array('task_id' => "{$task['id']}"), false, '', $task['title'], false, '', true) ?>
            </td>
            <td><?= $task['email']['subject'] ?></td>
            <td><?= t($task['email']['from']) ?></td>
            <td><?= $task['email']['datetime'] ?></td>
        </tr>
    <?php endforeach ?>
</table>
<?php if ($this->app->config('application_url')): ?>
    <hr/><a href="<?= $this->app->config('application_url') ?>">Kanboard</a>
<?php endif ?>
