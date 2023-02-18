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


            <fieldset>
                <legend><?= t('After processing emails automatically') ?></legend>
                <?= $this->form->radios('mailmagik_pref', array(
                    '1' => t('Delete emails'),
                    '2' => t('Mark emails as seen'),
                ), $values) ?>
            </fieldset>
            <fieldset>
                <legend><?= t('Task Email Feature') ?></legend>
                <?= $this->form->hidden('mailmagik_taskemail_pref', array('mailmagik_taskemail_pref' => '0')) ?>
                <?= $this->form->checkbox('mailmagik_taskemail_pref', t('Enable'), 1, $values['mailmagik_taskemail_pref']) ?>
            </fieldset>
            <br/>
            <p class="form-help"><?= t('To automatically convert emails to tasks or comment, you will need to add the Automatic Actions to each project you wish for it to work in.') ?></p>
            <p class="form-help"><?= t('Example of sending an email directly to a task: ') ?> <a href="mailto:Task#1<myimapemail@email.com>"><?= t('Task#1<myimapemail@email.com>') ?></a></p>
            <p class="form-help"><?= t('Example of sending an email to automatically convert to a task within a project: ') ?> <a href="mailto:Project#1<myimapemail@email.com>"><?= t('Project#1<myimapemail@email.com>') ?></a></p>
            <p class="form-help"><?= t('Example of sending an email to automatically convert to a comment within a task: ') ?> <a href="mailto:CommentOnTask#1<myimapemail@email.com>"><?= t('CommentOnTask#1<myimapemail@email.com>') ?></a></p>

    </fieldset>
