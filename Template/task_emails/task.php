<?= $this->render('task/details', array(
    'task' => $task,
    'tags' => $tags,
    'project' => $project,
    'editable' => false,
)) ?>

<div class="page-header">
    <h2 class=""><?= t('Task Email for Task#') . $task['id'] ?></h2>
</div>

<?= $this->render('mailmagik:task_emails/emails', array('emails' => $emails)) ?>
