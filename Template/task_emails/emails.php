<?php if (empty($emails)): ?>
    <p class="alert"><?= t('There are no emails yet.') ?></p>
<?php else: ?>
    <?php foreach ($emails as $email): ?>
        <div class="activity-event" style="
            border: 1px solid #000;
            border-radius: 8px;
            padding: 10px;
        ">
            <?= $email['date'] ?>
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
                    <ul style="list-style-type:none;">
                    <?php foreach ($email['attachments'] as $attachment): ?>
                        <li>
                            <?= $this->url->icon('download', $attachment['name'], 'EmailViewController', 'download', array('plugin' => 'kbphpimap', 'name' => $attachment['name'], 'filename' => $attachment['filename'], 'task_id' => $email['task_id'])) ?>
                        </li>
                    <?php endforeach ?>
                    </ul>
                </div>
            </details>
            <?php endif ?>
                    
        </div>
    <?php endforeach ?>
<?php endif ?>