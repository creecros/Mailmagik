    <fieldset class="mm-server-setup">
        <legend><?= t('Mailmagik Server Setup') ?></legend>
            <?= $this->form->label(t('IMAP server'), 'mailmagik_server') ?>
            <?= $this->form->text('mailmagik_server', $values, $errors, array('placeholder="imap.gmail.com"')) ?>
            
            <?= $this->form->label(t('Port'), 'mailmagik_port') ?>
            <?= $this->form->text('mailmagik_port', $values, $errors, array('placeholder="993"')) ?>
            
            <?= $this->form->label(t('Username'), 'mailmagik_user') ?>
            <?= $this->form->text('mailmagik_user', $values, $errors) ?>
            
            <?= $this->form->label(t('Password'), 'mailmagik_password') ?>
            <?= $this->form->password('mailmagik_password', $values, $errors) ?>

            <?= $this->form->radio('mailmagik_pref', t('Delete emails after processing automatically'), 1, isset($values['mailmagik_pref']) && $values['mailmagik_pref'] == 1) ?>
            <?= $this->form->radio('mailmagik_pref', t('Mark emails as seen after processing automatically'), 2, isset($values['mailmagik_pref']) && $values['mailmagik_pref'] == 2) ?>

            <?= $this->form->radio('mailmagik_taskemail_pref', t('Enable Task Email Feature'), 1, isset($values['mailmagik_taskemail_pref']) && $values['mailmagik_taskemail_pref'] == 1) ?>
            <?= $this->form->radio('mailmagik_taskemail_pref', t('Disable Task Email Feature'), 2, isset($values['mailmagik_taskemail_pref']) && $values['mailmagik_taskemail_pref'] == 2) ?>
            <br/>
            <p class="form-help"><?= t('To automatically convert emails to tasks or comment, you will need to add the Automatic Actions to each project you wish for it to work in.') ?></p>
            <p class="form-help"><?= t('Example of sending an email directly to a task: ') ?> <a href="mailto:Task#1<myimapemail@email.com>"><?= t('Task#1<myimapemail@email.com>') ?></a></p>
            <p class="form-help"><?= t('Example of sending an email to automatically convert to a task within a project: ') ?> <a href="mailto:Project#1<myimapemail@email.com>"><?= t('Project#1<myimapemail@email.com>') ?></a></p>
            <p class="form-help"><?= t('Example of sending an email to automatically convert to a comment within a task: ') ?> <a href="mailto:CommentOnTask#1<myimapemail@email.com>"><?= t('CommentOnTask#1<myimapemail@email.com>') ?></a></p>

    </fieldset>
