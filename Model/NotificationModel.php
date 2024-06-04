<?php

namespace Kanboard\Plugin\Mailmagik\Model;

use Kanboard\Core\Base;
use Kanboard\EventBuilder\CommentEventBuilder;
use Kanboard\EventBuilder\EventIteratorBuilder;
use Kanboard\EventBuilder\SubtaskEventBuilder;
use Kanboard\EventBuilder\TaskFileEventBuilder;
use Kanboard\EventBuilder\TaskLinkEventBuilder;
use Kanboard\Plugin\Mailmagik\EventBuilder\TaskEventBuilder;

class NotificationModel extends \Kanboard\Model\NotificationModel
{
    /**
     * Get iterator builder
     *
     * @access protected
     * @return EventIteratorBuilder
     */
    protected function getIteratorBuilder()
    {
        $iterator = new EventIteratorBuilder();
        $iterator
            ->withBuilder(TaskEventBuilder::getInstance($this->container))
            ->withBuilder(CommentEventBuilder::getInstance($this->container))
            ->withBuilder(SubtaskEventBuilder::getInstance($this->container))
            ->withBuilder(TaskFileEventBuilder::getInstance($this->container))
            ->withBuilder(TaskLinkEventBuilder::getInstance($this->container))
        ;

        return $iterator;
    }

    /**
     * Get task id from event
     *
     * @param  string $eventName
     * @param  array  $eventData
     * @return integer
     */
    public function getTaskIdFromEvent($eventName, array $eventData)
    {
        if ($eventName === EVENT_TASKMAILNOTIFY) {
            return $eventData['tasks'][0]['id'];
        }

        return parent::getTaskIdFromEvent($eventName, $eventData);
    }

}
