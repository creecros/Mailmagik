<?php

namespace Kanboard\Plugin\Mailmagik\EventBuilder;

use Kanboard\Event\TaskEvent;
use Kanboard\Model\TaskModel;

class TaskEventBuilder extends \Kanboard\EventBuilder\TaskEventBuilder
{
    /**
     * Get event title with author
     *
     * @param  string $author
     * @param  string $eventName
     * @param  array  $eventData
     * @return string subject
     */
    public function buildTitleWithAuthor($author, $eventName, array $eventData)
    {
        if ($eventName == EVENT_TASKMAILNOTIFY) {
            return $this->getSubject($eventData);
        }

        return parent::buildTitleWithAuthor($author, $eventName, $eventData);
    }

    /**
     * Get event title without author
     *
     * @param  string $eventName
     * @param  array  $eventData
     * @return string subject
     */
    public function buildTitleWithoutAuthor($eventName, array $eventData)
    {
        if ($eventName == EVENT_TASKMAILNOTIFY) {
            return $this->getSubject($eventData);
        }

        return parent::buildTitleWithoutAuthor($eventName, $eventData);
    }

    private function getSubject(array $eventData): string
    {
        return count($eventData['tasks']) > 1
            ? e("Mailmagik received new task-emails.")
            : e("Mailmagik recieved new task-email on task #{$this->getTaskId($eventData)}.");
    }

    private function getTaskId(array $eventData): string
    {
        return $eventData['tasks'][0]['id'];
    }
}
