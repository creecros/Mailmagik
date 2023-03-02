<?php

namespace Kanboard\Plugin\Mailmagik\Helper;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Core\Base;
use PhpImap;

class MailHelper extends Base
{
    public const EVENT_FETCHMAIL = 'mailmagik.fetchmail';

    /**
     * Login to the IMAP server
     *
     * @return $mailbox or false
     */
    public function login()
    {
        $server = $this->configModel->get('mailmagik_server', '');
        $port = $this->configModel->get('mailmagik_port', '');
        $proto = $this->configModel->get('mailmagik_proto', '');
        $user = $this->configModel->get('mailmagik_user', '');
        $password = $this->configModel->get('mailmagik_password', '');
        $folder = $this->configModel->get('mailmagik_folder', 'INBOX');

        if ($server != '' && $port != '' && $user != '' && $password != '') {
            return new PhpImap\Mailbox(
                '{' . $server . ':' . $port . $proto . '}' . $folder,
                $user,
                $password,
                false
            );
        } else {
            return false;
        }
    }

    /**
     * Get all task mails.
     *
     * @param $mailbox ref
     * @param string $prefix
     * @return array $mails_ids
     */
    public function getTaskMails(&$mailbox, $prefix)
    {
        return $this->searchMailbox($mailbox, 'TO ' . $prefix);
    }

    /**
     * Get all unseen messages.
     *
     * @param $mailbox ref
     * @param string $prefix
     * @return array $mails_ids
     */
    public function getUnseenMails(&$mailbox, $prefix)
    {
        $method = $this->configModel->get('mailmagik_parse_via', '1');
        
        if ($method == 2) {
            return $this->searchMailbox($mailbox, 'UNSEEN SUBJECT "'. $prefix . '"');
        } else {
            return $this->searchMailbox($mailbox, 'UNSEEN TO ' . $prefix);
        }
    }

    /**
     * Extract the id from the to-field.
     *
     * @param $email ref
     * @param string $prefix
     * @return string $id | null
     */
    public function getItemId(&$email, string $prefix)
    {
        $i = 0;
        $id = null;
        $method = $this->configModel->get('mailmagik_parse_via', '1');
        
        if ($method == 1) {
            foreach ($email->to as $to) {
                if ($i === 0 && $to != null) {
                    (strpos($to, $prefix) == 0)
                    ? $id = trim(str_replace($prefix, '', $to), ' ')
                    : $id = null;
                }
                $i++;
            }
        } else {
                if ($email->subject != null) {
                    (strpos($email->subject, $prefix) == 0)
                    ? $id = trim(str_replace($prefix, '', $email->subject), ' ')
                    : $id = null;
                }
        }

        return $id;
    }

    /**
    * Delete mthe message or makk it as seen, according the settings.
    *
    * @param $mailbox ref
    * @param $mail_id
    */
    public function processMessage(&$mailbox, $mail_id)
    {
        $option = $this->configModel->get('mailmagik_pref', '2');

        if ($option == 2) {
            $mailbox->markMailAsRead($mail_id);
        } else {
            $mailbox->deleteMail($mail_id);
        }
    }

    /**
     * Search the mailbox for unseen messages.
     *
     * @param $mailbox ref
     * @param string $filter
     * @return array $mails_ids
     */
    private function searchMailbox(&$mailbox, string $filter): array
    {
        try {
            $mails_ids = $mailbox->searchMailbox($filter);
        } catch(PhpImap\Exceptions\ConnectionException $ex) {
            return [];
        }

        return $mails_ids;
    }

    /**
     * Get the folder tree of the mailbox.
     *
     * @return array Folder names
     */
    public function getFolders(): array
    {
        $mailbox = $this->login();
        $values = array();
        if ($mailbox != false) {
            $folders = $mailbox->getMailboxes('*');
            foreach ($folders as $folder) {
                array_push($values, $folder['shortpath']);
            }
        }

        return $values;
    }
}
