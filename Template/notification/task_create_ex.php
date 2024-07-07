<?php if ($task_email || $task_comment): ?>
    <h2><?= t('Mailmagik') ?></h2>
    <ul>
        <?php if ($task_email): ?>
            <li><?= t('To send emails to this new task, use ') ?>
                <?= $mailto[0] ?> <?= t('as address.') ?>
            </li>
        <?php endif ?>
        <?php if ($task_comment): ?>
            <li><?= t('To send comments to this new task, use ') ?>
                <?= $mailto[1] ?> <?= t('as address.') ?>
            </li>
        <?php endif ?>
    </ul>
<?php endif ?>
