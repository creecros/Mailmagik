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
            <span class="" style="float: right"><?= $this->modal->confirm('trash', '', 'EmailViewController', 'confirmDelete', array('plugin' => 'mailmagik', 'mail_id' => $email['mail_id'], 'task_id' => $email['task_id'])) ?></span>
            <span class="" style="float: right"><?= $this->modal->confirm('tasks', '', 'EmailViewController', 'confirmConvertToTask', array('plugin' => 'mailmagik', 'mail_id' => $email['mail_id'], 'task_id' => $email['task_id'])) ?></span>
            <span class="" style="float: right"><?= $this->modal->confirm('comment', '', 'EmailViewController', 'confirmConvertToComment', array('plugin' => 'mailmagik', 'mail_id' => $email['mail_id'], 'task_id' => $email['task_id'])) ?></span>
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
            <?php if (!(empty($email['parsed_taskdata']) && empty($email['parsed_metadata']))): ?>
            <details class="accordion-section closed">
                <summary class="accordion-title"><?= t('Updatedable Task Data') ?></summary>
                <div class="accordion-content taskdata" id="taskdata">
                    <ul class="" style="list-style-type:none;">
                        <form method='post'
                            action=<?= $this->url->href('EmailViewController', 'update_taskdata_bulk', array(
                                'plugin' => 'mailmagik',
                                'task_id' => $email['task_id'],
                                'project_id' => $email['project_id'],)) ?> autocomplete='off'>
                            <?= $this->form->csrf() ?>
                            <?php $values = array() ?>
                            <?php foreach ($email['parsed_taskdata'] as $key => $value): ?>
                                <li class="">
                                    <?= $this->url->icon('upload', $key . ' = ' . $value, 'EmailViewController', 'update_taskdata', array('plugin' => 'mailmagik', 'key' => $key, 'value' => $value, 'task_id' => $email['task_id'], 'project_id' => $email['project_id'], 'is_metamagik' => '0')) ?>
                                </li>
                                <?php $values[$key] = $value ?>
                                <?= $this->form->hidden($key, $values) ?>
                            <?php endforeach ?>
                            <?php foreach ($email['parsed_metadata'] as $key => $value): ?>
                                <li class="">
                                    <?= $this->url->icon('upload', $key . ' = ' . $value, 'EmailViewController', 'update_taskdata', array('plugin' => 'mailmagik', 'key' => $key, 'value' => $value, 'task_id' => $email['task_id'], 'project_id' => $email['project_id'], 'is_metamagik' => '1')) ?>
                                </li>
                                <?php $key = 'metamagikkey_' . $key; $values[$key] = $value ?>
                                <?= $this->form->hidden($key, $values) ?>
                            <?php endforeach ?>
                            <div class="form-actions">
                                <button type='submit' class='btn btn-blue'><?= t('Apply all') ?></button>
                            </div>
                        </form>
                    </ul>
                </div>
            </details>
            <?php endif ?>
        </div>
    <?php endforeach ?>
<?php endif ?>
