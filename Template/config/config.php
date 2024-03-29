    <fieldset class="mm-server-setup">
        <legend><?= t('Mailmagik Server Setup') ?></legend>
            <?= $this->form->label(t('IMAP server'), 'mailmagik_server') ?>
            <?= $this->form->text('mailmagik_server', $values, $errors, array('placeholder="imap.gmail.com"')) ?>

            <?= $this->form->label(t('Port'), 'mailmagik_port') ?>
            <?= $this->form->text('mailmagik_port', $values, $errors, array('placeholder="993"')) ?>

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

            <fieldset>
                <legend><?= t('After processing emails automatically') ?></legend>
                <?= $this->form->radios('mailmagik_pref', array(
                    '1' => t('Delete emails'),
                    '2' => t('Mark emails as seen'),
                ), $values) ?>
                <br>
                <?= $this->form->hidden('mailmagik_include_files_tasks', array('mailmagik_include_files_tasks' => '0')) ?>
                <?= $this->form->checkbox('mailmagik_include_files_tasks', t('Include attachments when converting to task automatically?'), 1, $values['mailmagik_include_files_tasks']) ?>
                <?= $this->form->hidden('mailmagik_include_files_comments', array('mailmagik_include_files_comments' => '0')) ?>
                <?= $this->form->checkbox('mailmagik_include_files_comments', t('Include attachments when converting to comment automatically?'), 1, $values['mailmagik_include_files_comments']) ?>
            </fieldset>
            <fieldset>
                <legend><?= t('Task Email Feature') ?></legend>
                <?= $this->form->hidden('mailmagik_taskemail_pref', array('mailmagik_taskemail_pref' => '0')) ?>
                <?= $this->form->checkbox('mailmagik_taskemail_pref', t('Enable'), 1, $values['mailmagik_taskemail_pref']) ?>
            </fieldset>
            <fieldset>
                <legend><?= t('Parsing options') ?></legend>
                <?= $this->form->radios('mailmagik_parse_via', array(
                    '1' => t('Parse from the "TO" field'),
                    '2' => t('Parse from the "SUBJECT" field'),
                ), $values) ?>
            <br/>
            <p class="form-help"><?= t('To automatically convert emails to tasks or comment, you will need to add the Automatic Actions to each project you wish for it to work in.') ?></p>
            <p class="form-help"><?= t('Example of sending an email directly to a task parsed via the "TO" field: ') ?> <a href="mailto:Task#1<myimapemail@email.com>"><?= t('Task#1<myimapemail@email.com>') ?></a><?= t(' if parsed via "SUBJECT", [Task#1] should be in the subject') ?></p>
            <p class="form-help"><?= t('Example of sending an email to automatically convert to a task within a project parsed via "TO" field: ') ?> <a href="mailto:Project#1<myimapemail@email.com>"><?= t('Project#1<myimapemail@email.com>') ?></a><?= t(' if parsed via "SUBJECT", [Project#1] should be in the subject') ?></p>
            <p class="form-help"><?= t('Example of sending an email to automatically convert to a comment within a task parsed via "TO" field: ') ?> <a href="mailto:CommentOnTask#1<myimapemail@email.com>"><?= t('CommentOnTask#1<myimapemail@email.com>') ?></a><?= t(' if parsed via "SUBJECT", [CommentOnTask#1] should be in the subject') ?></p>
            </fieldset>

    </fieldset>
