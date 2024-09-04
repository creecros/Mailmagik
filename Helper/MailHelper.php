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
        $encoding = array_values($this->helper->mailHelper->getSupportedEncodings())[$this->configModel->get('mailmagik_encoding')];
        $error = false;

        if ($server != '' && $port != '' && $user != '' && $password != '') {
            $mailbox = new PhpImap\Mailbox(
                '{' . $server . ':' . $port . $proto . '}' . $folder,
                $user,
                $password,
                false,
                $encoding
            );
            try
            {
                $mailbox->searchMailbox('ALL');
            } catch(PhpImap\Exceptions\ConnectionException $ex) {
                $error = true;
            } catch (PhpImap\Exceptions\Exception $ex) {
                $error = true;
            }
            if ($error) {
                return false;
            } else {
                return $mailbox;
            }
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
        $method = $this->configModel->get('mailmagik_parse_via', '1');

        if ($method == 2) {
            return $this->searchMailbox($mailbox, 'SUBJECT "['. $prefix . '"');
        } else {
            return $this->searchMailbox($mailbox, 'TO ' . $prefix);
        }
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
            return $this->searchMailbox($mailbox, 'UNSEEN SUBJECT "['. $prefix . '"');
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
                    preg_match('/'.$prefix.'(.*?)]/', $email->subject, $match);
                    ($match[1] > 0 && $match[1] != null)
                    ? $id = $match[1]
                    : $id = null;
                    $email->subject = str_replace('['.$prefix . $match[1] . ']', '', $email->subject);
                }
        }

        return $id;
    }

    /**
    * Delete the message or mark it as seen, according the settings.
    *
    * @param $mailbox ref
    * @param $mail_id
    */
    public function disposeMessage(&$mailbox, $mail_id)
    {
        if ($this->configModel->get('mailmagik_pref', '2') == 2) {
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

    private function getDestAddress()
    {
        $mbox = $this->configModel->get('mailmagik_address', '');
        return $mbox !== '' ? $mbox : $this->configModel->get('mailmagik_user', '');
    }

    public function buildMailtoLink($task_id)
    {
        $email = $this->getDestAddress();
        $task = "Task#$task_id";

        return $this->configModel->get('mailmagik_parse_via', '1') == '1'
            ? array(
                // Address in TO
                "<a href='mailto:$task <$email>'>$task</a>",
                "<a href='mailto:CommentOn$task <$email>'>CommentOn$task</a>",
            )
            : array(
                // Address in SUBJECT
                "<a href='mailto:$email?subject=[$task]'>$task</a>",
                "<a href='mailto:$email?subject=[CommentOn$task]'>CommentOn$task</a>",
            );
    }

    /**
     * Check if the project has an ConvertEmailToComment action.
     *
     * @param int $project_id
     * @return bool
    */
    public function commentingEnabled(int $project_id): bool
    {
        $task_comment = false;
        foreach ($this->actionModel->getAllByProject($project_id) as $action) {
            if (strstr($action['action_name'], 'ConvertEmailToComment')) {
                $task_comment = true;
                break;
            }
        }
        return $task_comment;
    }

    private function mktempdir($task_id)
    {
        $tmpdir = MM_TMP_DIR . $task_id . '/';
        if (!file_exists($tmpdir)) {
            mkdir($tmpdir, MM_PERM, true);
        }
        return $tmpdir;
    }

    public function saveAndUpload($task_id, &$attachment)
    {
        $excludes=$this->configModel->get('mailmagik_exclude_files_pattern', '');
        if ( $excludes != '') {
            if ( preg_match('/' . $excludes . '/',$attachment->name) ) {
                    return;
                }
            }
        $tmp_name =  $this->mktempdir($task_id) . $attachment->name;
        $attachment->setFilePath($tmp_name);
        if (!file_exists($tmp_name)) {
            $attachment->saveToDisk();
        }
        $file = file_get_contents($tmp_name);
        $this->taskFileModel->uploadContent($task_id, $attachment->name, $file, false);
        unlink($tmp_name);
		}

    /**
     * Check task-emails for notification.
     */
    public function checkMailBox()
    {
        $prefix = 'Task#';
        $emails = array();

        if (($mailbox = $this->helper->mailHelper->login()) == false) {
            return $emails;
        }

        $mails_ids = $this->helper->mailHelper->getUnseenMails($mailbox, $prefix);

        foreach ($mails_ids as $mail_id) {
            // Get mail by $mail_id
            $email = $mailbox->getMail(
                $mail_id, // ID of the email, you want to get
                false
            );

            $from_name = (isset($email->fromName)) ? $email->fromName : $email->fromAddress;

            $emails[] = array(
                'task_id' => $this->helper->mailHelper->getItemId($email, $prefix),
                'email' => array(
                    'subject' => trim($email->subject),
                    'from' => "$from_name <$email->fromAddress>",
                    'datetime' => substr($email->date, 0, 10) . ' ' . substr($email->date, 11, 5),
                )
            );

            $mailbox->markMailAsRead($mail_id);
        } // foreach

        $emails = array_reverse($emails);

        return $emails;
    }

    public function getSupportedEncodings()
    {
        $encodings = mb_list_encodings();
        if (!in_array('US-ASCII', $encodings)) {
            $encodings[] = 'US-ASCII';
        }

        return $encodings;
    }

    public function sendConfirmMail($from_email, $from_name, $task_id)
    {
        $project_name =  $this->projectModel->getById($this->taskFinderModel->getById($task_id)['project_id'])['name'];
        $subject = e("[$project_name] Mailmagik created a new task with ID #$task_id");

        $this->emailClient->send(
            $from_email, $from_name, $subject,
            $this->template->render('Mailmagik:notification/confirm', array(
                'email' => $from_email,
                'task_id' => $task_id,
                'task_email' => $this->configModel->get('mailmagik_taskemail_pref', '1') == '1',
                'mailto' => $this->buildMailtoLink($task_id),
            ))
        );
    }


}
