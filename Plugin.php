<?php

namespace Kanboard\Plugin\Mailmagik;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Notification\MailNotification;
use Kanboard\Plugin\Mailmagik\Action\ConvertEmailToComment;
use Kanboard\Plugin\Mailmagik\Action\ConvertEmailToTask;
use Kanboard\Plugin\Mailmagik\Console\FetchMail;
use Kanboard\Plugin\Mailmagik\Console\TaskMailNotify;
use Kanboard\Plugin\Mailmagik\Controller\FetchmailController;
use Kanboard\Plugin\Mailmagik\Helper\MailHelper;

require_once('constants.php');

class Plugin extends Base
{
    public function initialize()
    {
        if (!file_exists(MM_FILES_DIR)) {
            mkdir(MM_FILES_DIR, MM_PERM, true);
        }

        $this->initConfig(array(
            'mailmagik_taskemail_pref' => '1',
            'mailmagik_pref' => '2',
            'mailmagik_include_files_tasks' => '1',
            'mailmagik_include_files_comments' => '0',
            'mailmagik_exclude_files_pattern' => '',
            'mailmagik_proto' => '/imap/ssl',
            'mailmagik_parse_via' => '1',
            'mailmagik_parsing_enable' => '0',
            'mailmagik_parsing_remove_data' => '0',
            'mailmagik_task_notify' => '0',
            'mailmagik_address' => '',
            'mailmagik_encoding' => array_search(DEFAULT_ENCODING, mb_list_encodings()),
        ));

        // Helper
        $this->helper->register('mailHelper', '\Kanboard\Plugin\Mailmagik\Helper\MailHelper');
        $this->helper->register('parsing', '\Kanboard\Plugin\Mailmagik\Helper\ParsingHelper');

        // Hooks
        $option = $this->configModel->get('mailmagik_taskemail_pref', '1');
        if ($option == 1) {
            $this->template->hook->attach('template:task:sidebar:information', 'mailmagik:task/emails');
        }

        // Config hook
        $this->template->hook->attach('template:config:email', 'mailmagik:config/config');

        // Assets
        $this->hook->on('template:layout:css', array('template' => 'plugins/Mailmagik/Assets/css/mailmagik.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/Mailmagik/Assets/js/mailmagik.js'));

        // Actions
        $this->actionManager->register(new ConvertEmailToTask($this->container));
        $this->actionManager->register(new ConvertEmailToComment($this->container));

        // Commandline: ./cli mailmagik:fetchmail
        $this->cli->add(new FetchMail($this->container));
        $this->cli->add(new TaskMailNotify($this->container));

        // Mailtype handler
        $this->userNotificationTypeModel->setType(
            MailNotification::TYPE,
            t('Email'),
            '\Kanboard\Plugin\Mailmagik\Notification\MailNotification'
        );

        // Webcron
        $this->applicationAccessMap->add('FetchmailController', array('run'), Role::APP_PUBLIC);
        $this->route->addRoute('fetchmail', 'FetchmailController', 'run', 'Mailmagik');
    }

    public function onStartup()
    {
        $this->eventManager->register(MailHelper::EVENT_FETCHMAIL, t('Trigger Mailmagik mail fetching'));
    }

    public function getPluginName()
    {
        return 'Mailmagik';
    }

    public function getPluginAuthor()
    {
        return 'Craig Crosby & Alfred Bühler';
    }

    public function getPluginVersion()
    {
        return '1.6.1';
    }

    public function getPluginDescription()
    {
        return 'Connect Kanboard to an IMAP server to recieve emails and automatically convert emails to tasks or comments';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/creecros/mailmagik';
    }

    public function getClasses()
    {
        return array(
            'Plugin\Mailmagik\Model' => array(
                'NotificationModel',
                'MyMetadataTypeModel',
            )
        );
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
