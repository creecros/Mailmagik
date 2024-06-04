<?php

namespace Kanboard\Plugin\Mailmagik\Notification;

use Kanboard\Core\Base;
use Kanboard\Core\Notification\NotificationInterface;

/**
 * Email Notification
 *
 * @package  Kanboard\Notification
 * @author   Frederic Guillot
 */
class MailNotification extends \Kanboard\Notification\MailNotification
{
    /**
     * Get the mail content for a given template name
     *
     * @param  string    $eventName
     * @param  array     $eventData
     * @return string
     */
    public function getMailContent($eventName, array $eventData)
    {
        switch ($eventName) {
            case EVENT_TASKMAILNOTIFY: // 'task.email'
                return $this->template->render('mailmagik:notification/' . str_replace('.', '_', $eventName), $eventData);
                break;

            default:
                return parent::getMailContent($eventName, $eventData);
                break;
        }
    }
}
