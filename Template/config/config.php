<fieldset class="mm-server-setup">
    <legend><?= t('Mailmagik') ?></legend>
    <?= $this->form->label(t('Email address'), 'mailmagik_address') ?>
    <?= $this->form->email('mailmagik_address', $values, $errors, array('placeholder="me@somewhere.tld"')) ?>
    <p class="form-help"><?= t('Address of Mailmagik mailbox. If not set, the Username below is taken.') ?></p>
    <fieldset>
        <legend><?= t('Server') ?></legend>
        <?= $this->form->label(t('IMAP server address'), 'mailmagik_server') ?>
        <?= $this->form->text('mailmagik_server', $values, $errors, array('placeholder="imap.gmail.com"')) ?>

        <?= $this->form->label(t('Port'), 'mailmagik_port') ?>
        <?= $this->form->text('mailmagik_port', $values, $errors, array('placeholder="993"')) ?>

        <?= $this->form->label(t('Encoding'), 'mailmagik_encoding') ?>
        <?= $this->form->select('mailmagik_encoding', $this->helper->mailHelper->getSupportedEncodings(), $values, $errors) ?>
        <p class="form-help"><?= t('Select US-ASCII for Exchange IMAP or outlook.com.') ?>

        <?= $this->form->label(t('Advanced settings'), 'mailmagik_proto') ?>
        <?= $this->form->text('mailmagik_proto', $values, $errors, array('placeholder="/imap/ssl"')) ?>
        <p class="form-help"><?= t('Advanced flag/settings for imap auth/security, default is /imap/ssl, see: ') ?> <a href="https://www.php.net/manual/en/function.imap-open.php" target="_blank">https://www.php.net/manual/en/function.imap-open.php</a><?= t(' for reference.') ?></p>

        <?= $this->form->label(t('Username'), 'mailmagik_user') ?>
        <?= $this->form->text('mailmagik_user', $values, $errors) ?>

        <?= $this->form->label(t('Password'), 'mailmagik_password') ?>
        <?= $this->form->password('mailmagik_password', $values, $errors) ?>

        <?= $this->form->label(t('Folder'), 'mailmagik_folder') ?>
        <?= $this->form->text('mailmagik_folder', $values, $errors, array(
            'placeholder="INBOX"',
            'list="folderlist"',
        )) ?>
        <datalist id='folderlist'>
            <?php $folders = $this->helper->mailHelper->getFolders()  ?>
            <?php foreach ($folders as $folder): ?>
                <option><?= $this->text->e($folder) ?></option>
            <?php endforeach ?>
        </datalist>
    </fieldset>
    <fieldset>
        <legend><?= t('Task- and Comment-Creation') ?></legend>

            <p><?= t('Common options') ?></p>
            <?= $this->form->radios('mailmagik_parse_via', array(
                '1' => t('Parse from the "TO" field'),
                '2' => t('Parse from the "SUBJECT" field'),
            ), $values) ?>
        <br/>
        <?= $this->render('Mailmagik:config/help') ?>
        <p><?= t('After processing emails automatically') ?></p>
        <?= $this->form->radios('mailmagik_pref', array(
            '1' => t('Delete emails'),
            '2' => t('Mark emails as seen'),
        ), $values) ?>
        <br/>
        <p><strong><?= t('Tasks') ?></strong></p>
        <?php $checkbox = 'Mailmagik:config/checkbox' ?>

        <?= $this->render($checkbox, array(
            'label'   => t('Enable automatic data parsing from messages.'),
            'name'    => 'mailmagik_parsing_enable',
            'default' => '0',
            'values'  => $values,
        )) ?>

        <?= $this->render($checkbox, array(
            'label'   => t('Remove data instructions from messages after processing.'),
            'name'    => 'mailmagik_parsing_remove_data',
            'default' => '0',
            'values'  => $values,
        )) ?>

        <?= $this->render($checkbox, array(
            'label'   => t('Include attachments when converting to task automatically.'),
            'name'    => 'mailmagik_include_files_tasks',
            'default' => '1',
            'values'  => $values,
        )) ?>

        <?= $this->render($checkbox, array(
            'label'   => t('Send confirmation email to the task creator.'),
            'name'    => 'mailmagik_task_notify',
            'default' => '0',
            'values'  => $values,
        )) ?>

        <br/>
        <p><strong><?= t('Comments') ?></strong></p>

        <?= $this->render($checkbox, array(
            'label'   => t('Include attachments when converting to comment automatically.'),
            'name'    => 'mailmagik_include_files_comments',
            'default' => '1',
            'values'  => $values,
        )) ?>
        <?= $this->form->label(t('Pattern of files not to attach'),
                               'mailmagik_exclude_files_pattern') ?>
        <?= $this->form->text('mailmagik_exclude_files_pattern',
                              $values, $errors) ?>
    </fieldset>
    <fieldset>
        <legend><?= t('Task Emails') ?></legend>
        <?= $this->form->hidden('mailmagik_taskemail_pref', array('mailmagik_taskemail_pref' => '0')) ?>
        <?= $this->form->checkbox('mailmagik_taskemail_pref', t('Enable'), 1, $values['mailmagik_taskemail_pref']) ?>
    </fieldset>
</fieldset>
