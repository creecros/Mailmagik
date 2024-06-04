<?php

namespace Kanboard\Plugin\Mailmagik\Console;

use Kanboard\Console\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskMailNotify extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName(CMD_TASKMAILNOTIFY)
            ->setDescription('Send notification for incoming task-emails')
            ->addOption('group', null, InputOption::VALUE_NONE, 'Group all mails for one user (from all projects) in one email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tasks = array();
        $emails = $this->helper->mailHelper->checkMailBox();

        // FIXME invalid Task#
        $tasks = array();
        foreach ($emails as $email) {
            $task = $this->taskFinderModel->getById($email['task_id']);
            $task['project_name'] = $this->projectModel->getById($task['project_id'])['name'];
            $task['email'] = $email['email'];
            $tasks[] = $task;
        }

        if ($input->getOption('group')) {
            $this->sendGroupTaskEMailNotifications($tasks);
        } else {
            $this->sendTaskEMailNotifications($tasks);
        }

        return 0;
    }

    /**
     * Send overdue tasks notification, ungrouped.
     *
     * @param  array $tasks
     * @return array $tasks
     */
    public function sendTaskEMailNotifications(array $tasks): array
    {
        foreach ($this->groupByColumn($tasks, 'project_id') as $project_id => $project_tasks) {
            $users = $this->userNotificationModel->getUsersWithNotificationEnabled($project_id);
            foreach ($users as $user) {
                $this->sendUserTaskEMailNotifications($user, $project_tasks);
            }
        }

        return $tasks;
    }

    /**
     * Send overdue tasks notification for a user.
     *
     * @param  array   $user
     * @param  array   $tasks
     */
    public function sendUserTaskEMailNotifications(array $user, array $tasks)
    {
        $user_tasks = array();
        $project_names = array();

        foreach ($tasks as $task) {
            if ($this->userNotificationFilterModel->shouldReceiveNotification($user, array('task' => $task))) {
                $user_tasks[] = $task;
                $project_names[$task['project_id']] = $task['project_name'];
            }
        }

        if (!empty($user_tasks)) {
            $this->userNotificationModel->sendUserNotification(
                $user,
                EVENT_TASKMAILNOTIFY,
                array('tasks' => $user_tasks, 'project_name' => implode(', ', $project_names))
            );
        }
    }

    /**
     * Group a collection of records by a column.
     *
     * @param  array   $collection
     * @param  string  $column
     * @return array
     */
    private function groupByColumn(array $collection, string $column): array
    {
        $result = array();

        foreach ($collection as $item) {
            $result[$item[$column]] [] = $item;
        }

        return $result;
    }

    /**
     * Send overdue tasks notification, grouped by owner.
     *
     * @param  array $tasks
     * @return array $tasks
     */
    private function sendGroupTaskEMailNotifications(array $tasks): array
    {
        foreach ($this->groupByColumn($tasks, 'owner_id') as $user_tasks) {
            $users = $this->userNotificationModel->getUsersWithNotificationEnabled($user_tasks[0]['project_id']);
            foreach ($users as $user) {
                $this->sendUserTaskEMailNotifications($user, $user_tasks);
            }
        }

        return $tasks;
    }
}
