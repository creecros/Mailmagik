<?php

namespace Kanboard\Plugin\Mailmagik\Console;

use Kanboard\Console\BaseCommand;
use Kanboard\Event\GenericEvent;
use Kanboard\Plugin\Mailmagik\Helper\MailHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class FetchMail extends BaseCommand
{
    public const EVENT = MailHelper::EVENT_FETCHMAIL;

    private $commands = array(
        CMD_TASKMAILNOTIFY,
    );

    protected function configure()
    {
        $this
            ->setName(CMD_FETCHMAIL)
            ->setDescription('Trigger Mailmagik mail fetching by automatic actions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Main job first: Trigger the actions EMailToTask & EMailToComment
        foreach ($this->getProjectIds() as $project_id) {
            $event = new GenericEvent(array('project_id' => $project_id));
            if (APP_VERSION < '1.2.31') {
                $this->dispatcher->dispatch(self::EVENT, $event);
            } else {
                $this->dispatcher->dispatch($event, self::EVENT);
            }
        }

        // Run additional stuff

        foreach ($this->commands as $command) {
            $job = $this->getApplication()->find($command);
            $job->run(new ArrayInput(array('command' => $command)), new NullOutput());
        }

        return 0;
    }

    private function getProjectIds()
    {
        $listeners = $this->dispatcher->getListeners(self::EVENT);
        $project_ids = array();

        foreach ($listeners as $listener) {
            $project_ids[] = $listener[0]->getProjectId();
        }

        return array_unique($project_ids);
    }
}
