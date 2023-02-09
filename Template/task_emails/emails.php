<?php if (empty($emails)): ?>
    <p class="alert"><?= t('There are no emails yet.') ?></p>
<?php else: ?>
    <?php foreach ($emails as $email): ?>
        <div class="email-event">
            <?php if (!is_null($email['user'])): ?>
                <?= $this->avatar->render(
    $email['user']['id'],
    $email['user']['username'],
    $email['user']['name'],
    $email['user']['email'],
    $email['user']['avatar_path']
) ?>
            <?php else: ?>
                <div class="avatar avatar-48 avatar-left"><div class="avatar-letter" style="background-color: #5d5d5d" title="test" role="img" aria-label="test">?</div></div>
            <?php endif ?>
            <span class="" style="font-style: italic"><?= $this->dt->datetime(strtotime($email['date'])) ?></span>
            <span class="" style="float: right"><?= $this->url->icon('trash', '', 'EmailViewController', 'delete', array('plugin' => 'mailmagik', 'mail_id' => $email['mail_id'], 'task_id' => $email['task_id'])) ?></span>
            <span class="" style="float: right"><?= $this->url->icon('tasks', '', 'EmailViewController', 'convertToTask', array('plugin' => 'mailmagik', 'mail_id' => $email['mail_id'], 'task_id' => $email['task_id'])) ?></span>
            <span class="" style="float: right"><?= $this->url->icon('comment', '', 'EmailViewController', 'convertToComment', array('plugin' => 'mailmagik', 'mail_id' => $email['mail_id'], 'task_id' => $email['task_id'])) ?></span>
            <br>
            <strong><?= $email['from_name'] . ' &lt;' . $email['from_email'] . '&gt;' ?></strong>
            <br>
            <?= $email['subject'] ?>
            <details class="accordion-section closed">
                <summary class="accordion-title"><?= t('Message') ?></summary>
                <div class="accordion-content message" id="message">
                    <?= $email['message'] ?>
                </div>
            </details>
            <?php if ($email['has_attach'] === 'y'): ?>
            <details class="accordion-section closed">
                <summary class="accordion-title"><?= t('Attachments') ?></summary>
                <div class="accordion-content attachments" id="attachments">
                    <ul class="" style="list-style-type:none;">
                    <?php foreach ($email['attachments'] as $attachment): ?>
                        <li class="">
                            <?= $this->url->icon('download', $attachment, 'EmailViewController', 'download', array('plugin' => 'mailmagik', 'name' => $attachment, 'task_id' => $email['task_id'], 'project_id' => $email['project_id'])) ?>
                        </li>
                    <?php endforeach ?>
                    </ul>
                </div>
            </details>
            <?php endif ?>
        </div>
    <?php endforeach ?>
<?php endif ?>
