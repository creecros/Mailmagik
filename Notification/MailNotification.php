<?php

namespace Kanboard\Plugin\Mailmagik\Notification;

use Kanboard\Core\Base;
use Kanboard\Core\Notification\NotificationInterface;
use Kanboard\Model\TaskModel;

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

            case TaskModel::EVENT_CREATE: // 'task.create'
                $eventData['task_email'] = $this->configModel->get('mailmagik_taskemail_pref') == 1;
                $eventData['task_comment'] = $this->helper->mailHelper->commentingEnabled($eventData['task']['project_id']);
                $eventData['mailto'] = $this->helper->mailHelper->buildMailtoLink($eventData['task_id']);
                return $this->template->render('mailmagik:notification/' . str_replace('.', '_', $eventName), $eventData);
                break;

            default:
                return parent::getMailContent($eventName, $eventData);
                break;
        }
    }
}
