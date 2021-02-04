    <fieldset>
        <legend><?= t('Task Email Server Setup') ?></legend>
            <?= $this->form->label(t('IMAP server'), 'kbphpimap_server') ?>
            <?= $this->form->text('kbphpimap_server', $values, $errors, array('placeholder="imap.gmail.com"')) ?>
            
            <?= $this->form->label(t('Port'), 'kbphpimap_port') ?>
            <?= $this->form->text('kbphpimap_port', $values, $errors, array('placeholder="993"')) ?>
            
            <?= $this->form->label(t('Username'), 'kbphpimap_user') ?>
            <?= $this->form->text('kbphpimap_user', $values, $errors) ?>
            
            <?= $this->form->label(t('Password'), 'kbphpimap_password') ?>
            <?= $this->form->password('kbphpimap_password', $values, $errors) ?>

    </fieldset>