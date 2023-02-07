<?= $this->render('task/details', array(
    'task' => $task,
    'tags' => $tags,
    'project' => $project,
    'editable' => false,
)) ?>

<div class="page-header">
    <h2><?= t('Task Email: Task#') . $task['id'] . ' &lt;' . $this->task->configModel->get('kbphpimap_user','') . '&gt;' ?></h2>
</div>

<?= $this->render('kbphpimap:task_emails/load', array()) ?>
<form name="kbphpimap_form" id="kbphpimap_form" class="url-links" method="post" action="<?= $this->url->href('EmailViewController', 'view', array('plugin' => 'kbphpimap', 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>" autocomplete="off">
</form>

