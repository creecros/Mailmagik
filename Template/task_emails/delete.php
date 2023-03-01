<div class="page-header">
    <h2><?= t('Delete email') ?></h2>
</div>

<div class="confirm">
    <p class="alert alert-info">
        <?= t('Do you really want to delete this email?') ?>
    </p>

    <?= $this->modal->confirmButtons(
    'EmailViewController',
    'delete',
    array('plugin' => 'mailmagik', 'task_id' => $task_id, 'mail_id' => $mail_id)
) ?>
</div>