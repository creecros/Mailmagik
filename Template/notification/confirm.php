<ul>
    <?php if ($task_email): ?>
        <li><?= t('To send emails to this new task, use ') ?>
            <?= $mailto[0] ?> <?= t('as address.') ?>
        </li>
    <?php endif ?>
    <li><?= t('To send comments to this new task, use ') ?>
        <?= $mailto[1] ?> <?= t('as address.') ?>
    </li>
</ul>
<?php if ($this->app->config('application_url')): ?>
    <hr/><a href="<?= $this->app->config('application_url') ?>">Kanboard</a>
<?php endif ?>
