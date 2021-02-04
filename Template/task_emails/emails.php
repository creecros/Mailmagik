<?php if (empty($emails)): ?>
    <p class="alert"><?= t('There are no emails yet.') ?></p>
<?php else: ?>
    <?php foreach ($emails as $email): ?>
        <div class="activity-event">
            <?= 'From: ' . $email['from_name'] . ' &lt;' . $email['from_email'] . '&gt; ' . 'Subject: ' . $email['subject'] ?>
            <div class="activity-content">
                <?= $email['date'] ?>
            </div>
            <details class="accordion-section closed">
                <summary class="accordion-title"><?= t('Message') ?></summary>
                <div class="accordion-content message" id="message">
                    <?= $email['message'] ?>
                </div>
            </details>
                    
        </div>
    <?php endforeach ?>
<?php endif ?>