<div class="page-header">
    <h2><?= t('Convert email to comment') ?></h2>
</div>

<div class="confirm">
    <form method="post" action="<?= $this->url->href('EmailViewController', 'convertToComment', array('plugin' => 'mailmagik', 'task_id' => $task_id, 'mail_id' => $mail_id)) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>
    <p class="alert alert-info">
        <?= t('Do you really want to convert this email to a comment?') ?>
    </p>
            <fieldset>
                <legend><?= t('Include email attachments?') ?></legend>
                <?= $this->form->hidden('mailmagik_include_files', array('mailmagik_include_files' => '0')) ?>
                <?= $this->form->checkbox('mailmagik_include_files', t('Yes, include the email attachments when converting.'), 1) ?>
            </fieldset>

    <?= $this->modal->submitButtons(array('submitLabel' =>  t('Yes'))) ?>

</div>