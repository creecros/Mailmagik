<?= $this->render('task/details', array(
    'task' => $task,
    'tags' => $tags,
    'project' => $project,
    'editable' => false,
)) ?>

<div class="page-header">
    <h2 class=""><?= t('Task Email for Task#') . $task['id'] ?></h2>
</div>

<?= $this->render('mailmagik:task_emails/load', array()) ?>
<form name="mailmagik_form" id="mailmagik_form" class="url-links" method="post" action="<?= $this->url->href('EmailViewController', 'view', array('plugin' => 'mailmagik', 'task_id' => $task['id'], 'project_id' => $task['project_id'])) ?>" autocomplete="off">
</form>

