<?php

namespace Kanboard\Plugin\Mailmagik\Console;

use Kanboard\Plugin\Mailmagik\Helper\MailHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Kanboard\Console\BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('mailmagik:fetchmail')  // Name for the commandline
            ->setDescription('Trigger Mailmagik mail fetching by automatic actions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = 'projects:fetchmail';

        $job = $this->getApplication()->find($command);
        $job->run(new ArrayInput(array('command' => $command)), new NullOutput());
        return 0;
    }
}
