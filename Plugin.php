<?php

namespace Kanboard\Plugin\Mailmagik;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\Mailmagik\Action\ConvertEmailToTask;
use Kanboard\Plugin\Mailmagik\Action\ConvertEmailToComment;

class Plugin extends Base
{
    public function initialize()
    {
        if (!file_exists(DATA_DIR . '/files/mailmagik/files')) {
            mkdir(DATA_DIR . '/files/mailmagik/files', 0755, true);
        }

        $this->initConfig(array(
            'mailmagik_taskemail_pref' => '1',
            'mailmagik_pref' => '2',
        ));

        // Hooks
        $option = $this->configModel->get('mailmagik_taskemail_pref', '1');
        if ($option == 1) {
            $this->template->hook->attach('template:task:sidebar:information', 'mailmagik:task/emails');
        }


        //CONFIG HOOK
        $this->template->hook->attach('template:config:email', 'mailmagik:config/config');

        //css
        $this->hook->on('template:layout:css', array('template' => 'plugins/Mailmagik/Assets/css/mailmagik.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/Mailmagik/Assets/js/mailmagik.js'));

        //ACTIONS
        $this->actionManager->register(new ConvertEmailToTask($this->container));
        $this->actionManager->register(new ConvertEmailToComment($this->container));
    }

    public function getPluginName()
    {
        return 'Mailmagik';
    }

    public function getPluginAuthor()
    {
        return 'Craig Crosby';
    }

    public function getPluginVersion()
    {
        return '1.1.1';
    }

    public function getPluginDescription()
    {
        return 'Connect Kanboard to an IMAP server to recieve emails and automatically convert emails to tasks or comments';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/creecros/mailmagik';
    }

    private function initConfig(array $configs)
    {
        $values = array();

        foreach ($configs as $name => $value) {
            if ($this->configModel->get($name) == '') {
                $values += array($name => $value);
            }
        }

        if (!empty($values)) {
            $this->configModel->save($values);
        }
    }
}
