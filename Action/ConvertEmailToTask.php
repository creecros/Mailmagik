<?php

namespace Kanboard\Plugin\Kbphpimap\Action;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Model\UserModel;
use Kanboard\Model\ProjectModel;
use PhpImap;
use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Action to convert email to a task
 */
class ConvertEmailToTask extends Base
{
    public const PREFIX = 'Project#';

    /**
     * Get automatic action description
     *
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return t('Automatically Convert Emails to Tasks');
    }
    /**
     * Get the list of compatible events
     *
     * @access public
     * @return array
     */
    public function getCompatibleEvents()
    {
        return array(
            TaskModel::EVENT_DAILY_CRONJOB,
        );
    }
    /**
     * Get the required parameter for the action (defined by the user)
     *
     * @access public
     * @return array
     */
    public function getActionRequiredParameters()
    {
        return array(
            'column_id' => t('Column'),
            'color_id' => t('Color'),
        );
    }
    /**
     * Get the required parameter for the event
     *
     * @access public
     * @return string[]
     */
    public function getEventRequiredParameters()
    {
        return array('project_id');
    }
    /**
     * Check if the event data meet the action condition
     *
     * @access public
     * @param  array   $data   Event data dictionary
     * @return bool
     */
    public function hasRequiredCondition(array $data)
    {
        return true;
    }

    public function doAction(array $data)
    {
        $converter = new HtmlConverter();
        $project = $this->projectModel->getById($data['project_id']);
        $emails = array();

        $mailbox = $this->login();

        try {
            // Search in mailbox folder for specific emails
            // PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
            $mails_ids = $mailbox->searchMailbox('UNSEEN TO ' . self::PREFIX);
        } catch(PhpImap\Exceptions\ConnectionException $ex) {
            die();
        }

        foreach ($mails_ids as $mail_id) {
            $i = 0;

            // Get mail by $mail_id
            $email = $mailbox->getMail(
                $mail_id, // ID of the email, you want to get
                false // Do NOT mark emails as seen
            );

            $from_name = (isset($email->fromName)) ? $email->fromName : $email->fromAddress;
            $from_email = $email->fromAddress;
            foreach ($email->to as $to) {
                if ($i === 0 && $to != null) {
                    (strpos($to, self::PREFIX) == 0) ? $project_id = str_replace(self::PREFIX, '', $to) : $project_id = null;
                }
                $i++;
            }
            $subject = $email->subject;
            $message_id = $email->messageId;
            $date = $email->date;

            if ($email->textHtml) {
                $email->embedImageAttachments();
                $message = $converter->convert($email->textHtml);
            } else {
                $message = $email->textPlain;
            }
            $message = $email->textPlain;


            if ($email->hasAttachments()) {
                $has_attach = 'y';
            } else {
                $has_attach = 'n';
            }

            if (!is_null($project_id) && intval($project_id) === intval($project['id'])) {
                if (!$this->userModel->getByEmail($from_email)) {
                    $connect_to_user = null;
                } else {
                    $connect_to_user = $this->userModel->getByEmail($from_email);
                }

                $userMembers = $this->projectUserRoleModel->getUsers($data['project_id']);
                $groupMembers = $this->projectGroupRoleModel->getUsers($data['project_id']);
                $project_users = array_merge($userMembers, $groupMembers);
                $user_in_project = false;

                foreach ($project_users as $user) {
                    if ($user['id'] = $connect_to_user['id']) {
                        $user_in_project = true;
                        break;
                    }
                }
                if ($user_in_project) {
                    $task_id = $this->taskCreationModel->create(array(
                        'project_id' => $project_id,
                        'title' => $subject,
                        'description' => isset($message) ? $message : '',
                        'column_id' => $this->getParam('column_id'),
                        'color_id' => $this->getParam('color_id'),
                    ));

                    $values = array(
                        'id' => $task_id,
                        'creator_id' => is_null($connect_to_user) ? '' : $connect_to_user['id'],
                        );

                    // More attributes from subject

                    $values = array_merge($values, $this->scanSubject($subject, $project_id));

                    $this->taskModificationModel->update($values, false);

                    if (!empty($email->getAttachments())) {
                        $attachments = $email->getAttachments();
                        foreach ($attachments as $attachment) {
                            if (!file_exists(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id)) {
                                mkdir(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id, 0755, true);
                            }
                            $tmp_name = DATA_DIR . '/files/kbphpimap/tmp/' . $task_id . '/' . $attachment->name;
                            $attachment->setFilePath($tmp_name);
                            if (!file_exists($tmp_name)) {
                                $attachment->saveToDisk();
                            }
                            $file = file_get_contents($tmp_name);
                            $this->taskFileModel->uploadContent($task_id, $attachment->name, $file, false);
                            unlink($tmp_name);
                        }
                    }
                }

                $option = $this->configModel->get('kbphpimap_pref', '2');

                if ($option == 2) {
                    $mailbox->markMailAsRead($mail_id);
                } else {
                    $mailbox->deleteMail($mail_id);
                }
            }
        }
    }

    public function login()
    {
        $server = $this->configModel->get('kbphpimap_server', '');
        $port = $this->configModel->get('kbphpimap_port', '');
        $user = $this->configModel->get('kbphpimap_user', '');
        $password = $this->configModel->get('kbphpimap_password', '');

        $mailbox = new PhpImap\Mailbox(
            '{'.$server.':' . $port . '/imap/ssl}INBOX',
            $user,
            $password,
            false
        );

        return $mailbox;
    }

    /**
     * Scan and extract task attributes from subject.
     *
     * @param   string  subject reference
     * @param   stting  project_id
     * @return  array   extracted attributes
     */
    private function scanSubject(string &$subject, string $project_id): array
    {
        $attributes = array();

        $date_due = $date_started = $priority = null;

        // Start- and due date

        if (($date_due = $this->extractDate($subject, 'd')) != null) {
            $attributes = array_merge($attributes, array('date_due' => $date_due));
        }

        if (($date_started = $this->extractDate($subject, 's')) != null) {
            $attributes = array_merge($attributes, array('date_started' => $date_started));
        }

        // Priority

        if (($priority = $this->extractPriority($subject)) != null) {
            $attributes = array_merge($attributes, array('priority' => intval($priority)));
        }

        // Category

        if (($category_name = $this->extractCategory($subject)) != null) {
            if (($category_id = $this->categoryModel->getIdByName($project_id, $category_name)) > 0) {
                $attributes = array_merge($attributes, array('category_id' => $category_id));
            }
        }

        // Tags

        if (count($tags= $this->extractTags($subject)) > 0) {
            $attributes = array_merge($attributes, array('tags' => $tags));
        }

        // Subject was probably modified, due to attribute stuff removal.

        if (count($attributes) > 0) {
            $attributes = array_merge($attributes, array('title' => trim($subject, ' ')));
        }

        return $attributes;
    }

    /**
     * Extract dates from subject
     *
     * @param   string  subject reference
     * @param   string  prefix, 'd' for due date, 's' for start date
     * @return  string  extracted date, e.g. '2023-02-08'
     * @return  NULL    no or no valid date found
     */
    private function extractDate(&$subject, $prefix)
    {
        $date = $this->extractAttribute($subject, $prefix, '\d{4}-\d{2}-\d{2}');

        return date_create($date) ? $date : null;
    }

    /**
     * Extract the priority from subject
     *
     * @param   string  subject reference
     * @return  string  extracted priority
     * @return  NULL    no priority found
     */
    private function extractPriority(&$subject)
    {
        return $this->extractAttribute($subject, 'p');
    }

    /**
     * Extract the category from subject
     *
     * @param   string  subject
     * @return  string  extracted category
     * @return  NULL    no category found
     */
    private function extractCategory(&$subject)
    {
        return $this->extractAttribute($subject, 'c');
    }

    /**
     * Extract all tags from subject
     *
     * @param   string  subject
     * @return  array   extracted tag names
     * @return  NULL    no tags found
     */
    private function extractTags(&$subject)
    {
        $tags = array();

        while (($tag = $this->extractAttribute($subject, 't')) != null) {
            array_push($tags, $tag);
        }

        return $tags;
    }

    /**
     * Extract task attributes from subject
     * s: start date
     * d: due date
     * p: priority
     * c: category
     * f: flag
     *
     * @param   string  subject reference
     * @param   string  prefix, see above
     * @param   string  pattern to remove from subject
     * @return  string  extracted attribute
     * @return  NULL    no attribute found
     */
    private function extractAttribute(&$subject, $prefix, $pattern = '\w{1,}')
    {
        $attribute = null;

        if (($pos = strpos($subject, "$prefix:")) && $pos >= 0) {
            sscanf(substr($subject, $pos), "$prefix:%s", $attribute);
            $subject = trim(preg_filter("/$prefix:$pattern/", '', $subject, 1), ' ');
        }

        return $attribute;
    }
}
