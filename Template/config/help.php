<p class="form-help"><?= t('To automatically convert emails to tasks or comment, you will need to add the Automatic Actions to each project you wish for it to work in.') ?></p>
<p class="form-help"><?= t('Examples:') ?></p>

<div id='parse-to' >
    <p class="form-help">
        <?= t('- To send an email directly to a task:') ?><a href="mailto:Task#1<myimapemail@email.com>"><?= t('Task#1<myimapemail@email.com>') ?></a><br/>
        <?= t('- To automatically convert to a task within a project: ') ?><a href="mailto:Project#1<myimapemail@email.com>"><?= t('Project#1<myimapemail@email.com>') ?></a><br/>
        <?= t('- To automatically convert to a comment within a task: ') ?><a href="mailto:CommentOnTask#1<myimapemail@email.com>"><?= t('CommentOnTask#1<myimapemail@email.com>') ?></a>
    </p>
</div>

<div id='parse-subject' hidden>
    <p class="form-help">
        <?= t('- To send an email directly to a task: [Task#1] should be in the subject') ?><br/>
        <?= t('- To automatically convert to a task within a project: [Project#1] should be in the subject') ?><br/>
        <?= t('- To automatically convert to a comment within a task : [CommentOnTask#1] should be in the subject') ?>
    </p>
</div>
