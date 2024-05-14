<?php

namespace Kanboard\Plugin\Mailmagik\Action;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Action\Base;
use Kanboard\Controller\BaseController;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;
use Kanboard\Plugin\Mailmagik\Helper\MailHelper;
use League\HTMLToMarkdown\HtmlConverter;
use PhpImap;

/**
 * Action to convert email to a task
 */
class ConvertEmailToTask extends Base
{
    public const PREFIX = 'Project#';

    // Allowed qoute chars for open and close
    public $QO = "\"'“‘«„";
    public $QC = "\"'”’»“";

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
            MailHelper::EVENT_FETCHMAIL,
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
        $converter = new HtmlConverter(array('strip_tags' => true));
        $project = $this->projectModel->getById($data['project_id']);
        $emails = array();

        if (($mailbox = $this->helper->mailHelper->login()) === false) {
            return;
        }

        $mails_ids = $this->helper->mailHelper->getUnseenMails($mailbox, self::PREFIX);

        foreach ($mails_ids as $mail_id) {
            // Get mail by $mail_id
            $email = $mailbox->getMail(
                $mail_id, // ID of the email, you want to get
                false // Do NOT mark emails as seen
            );
            $from_name = (isset($email->fromName)) ? $email->fromName : $email->fromAddress;
            $from_email = $email->fromAddress;
            $project_id = $this->helper->mailHelper->getItemId($email, self::PREFIX);
            $subject = $email->subject;
            $message_id = $email->messageId;
            $date = $email->date;

            if ($email->textHtml) {
                $email->embedImageAttachments();
                $message = $converter->convert($email->textHtml);
            } else {
                $message = $email->textPlain;
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
                    if ($connect_to_user && $connect_to_user['id'] == $user['id']) {
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

                    // Get attributes from subject

                    (is_null($subject)) ?: $values = array_merge($values, $this->scanSubject($subject, $project_id));

                    // Get attributes from message body

                    if ($this->configModel->get('mailmagik_parsing_enable', '1') == 1) {
                        $parsed_data = $this->helper->parsing->parseAllData($email->textPlain, $task_id);

                        if (isset($parsed_data['title'])) {
                            $parsed_data['title'] = $values['title'] . $parsed_data['title'];
                        }

                        $values = array_merge($values, $parsed_data);
                    }

                    $this->taskModificationModel->update($values, false);

                    if (!empty($email->getAttachments()) && $this->configModel->get('mailmagik_include_files_tasks', '1') == 1) {
                        $attachments = $email->getAttachments();
                        foreach ($attachments as $attachment) {
                            $this->helper->mailHelper->saveAndUpload($task_id, $attachment);
                        }
                    }

                    if ($this->configModel->get('mailmagik_task_notify', '0') == 1) {
                        $this->helper->mailHelper->sendNotifyMail($from_email, $from_name, $email->toString, $task_id);
                    }
                }

                $this->helper->mailHelper->disposeMessage($mailbox, $mail_id);
            }
        }
    }

    /**
     * Scan and extract task attributes from subject.
     *
     * @param   string  subject reference
     * @param   string  project_id
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

        // Category, column and tags

        $attributes = array_merge($attributes, $this->extractQuotables($subject, $project_id));

        if (!empty($attributes)) {
            $attributes = array_merge($attributes, array('title' => trim($subject, ' ')));
        }

        return $attributes;
    }

    /**
     * Extract dates from subject
     *
     * @param   string  subject reference
     * @param   string  prefix, 'd' for due date, 's' for start date
     * @return  string  extracted date, e.g. '2023-02-08' | null
     */
    private function extractDate(&$subject, $prefix)
    {
        $attr = $this->extractAttribute($subject, $prefix, '\d{4}-\d{2}-\d{2}');
        $date = ($attr != null) ? $attr : '';

        return date_create($date) ? $date : null;
    }

    /**
     * Extract the priority from subject
     *
     * @param   string  subject reference
     * @return  string  extracted priority | null
     */
    private function extractPriority(&$subject)
    {
        return $this->extractAttribute($subject, 'p');
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

    /**
     * Extract attributes that may be multi word. This relates to Column,
     * Category and Tags. A multi word atribute requires quoting with " or '.
     * Single word without qouting is still OK.
     *
     * @param string $subject reference
     * @param string $project_id
     * @return array $attributes
     */
    private function extractQuotables(string &$subject, string $project_id) : array
    {
        $attributes = array();
        $tags = array();
        $pattern =  "/(c:|col:|t:)([$this->QO](?:\w{1,})(?: |\w{1,}){1,}(?:\w{1,})[$this->QC]|(?:\w{1,})(?:\w{1,}))/u";

        do {
            $matches = array();
            if ($rc = preg_match($pattern, $subject, $matches)) {
                switch ($matches[1]) {
                    case 'col:':
                        if (($column_id = $this->columnModel->getColumnIdByTitle($project_id, $this->cleanup($matches[2]))) > 0) {
                            $attributes = array_merge($attributes, array('column_id' => $column_id));
                        }
                        break;
                    case 'c:':
                        if (($category_id = $this->categoryModel->getIdByName($project_id, $this->cleanup($matches[2]))) > 0) {
                            $attributes = array_merge($attributes, array('category_id' => $category_id));
                        }
                        break;
                    case 't:':
                        $tags[] = $this->cleanup($matches[2]);
                        break;
                    default:
                        break;
                }
                $subject = preg_filter($pattern, '', $subject, 1);
            }
        } while ($rc);

        if (!empty($tags)) {
            $attributes['tags'] = $tags;
        }

        return $attributes;
    }

    private function cleanup(string $match) : string
    {
        return trim($match, ' ' . $this->QO . $this->QC);
    }
}
