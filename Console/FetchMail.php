<?php

namespace Kanboard\Plugin\Mailmagik\Console;

use Kanboard\Console\BaseCommand;
use Kanboard\Event\GenericEvent;
use Kanboard\Plugin\Mailmagik\Helper\MailHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchMail extends BaseCommand
{
    public const EVENT = MailHelper::EVENT_FETCHMAIL;

    protected function configure()
    {
        $this
            ->setName('projects:fetchmail')
            ->setDescription('Trigger scheduler event for all tasks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getProjectIds() as $project_id) {
            $event = new GenericEvent(array('project_id' => $project_id));
            if (APP_VERSION < '1.2.31') {
                $this->dispatcher->dispatch(self::EVENT, $event);
            } else {
                $this->dispatcher->dispatch($event, self::EVENT);
            }
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
