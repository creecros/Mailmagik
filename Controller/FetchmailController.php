<?php

namespace Kanboard\Plugin\Mailmagik\Controller;

use Kanboard\Controller\BaseController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FetchmailController extends BaseController
{
    public function run()
    {
        $this->checkWebhookToken();

        $input = new ArrayInput(array(
           'command' => CMD_FETCHMAIL,
        ));
        $output = new NullOutput();

        $this->cli->setAutoExit(false);
        $this->cli->run($input, $output);

        $this->response->html( CMD_FETCHMAIL . ' executed');
    }
}
