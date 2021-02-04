  
<?= $this->render('task/details', array(
    'task' => $task,
    'tags' => $tags,
    'project' => $project,
    'editable' => false,
)) ?>

<div class="page-header">
    <h2><?= t('Task Email: Task#') . $task['id'] . ' &lt;' . $this->task->configModel->get('kbphpimap_user','') . '&gt;' ?></h2>
</div>

<?= $this->render('kbphpimap:task_emails/emails', array('emails' => $emails)) ?>