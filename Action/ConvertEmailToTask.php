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

                    (is_null($subject)) ?: $values = array_merge($values, $this->scanSubject($subject, $project_id));

                    $this->taskModificationModel->update($values, false);

                    if (!empty($email->getAttachments()) && $this->configModel->get('mailmagik_include_files_tasks', '1') == 1) {
                        $attachments = $email->getAttachments();
                        foreach ($attachments as $attachment) {
                            if (!file_exists(DATA_DIR . '/files/mailmagik/tmp/' . $task_id)) {
                                mkdir(DATA_DIR . '/files/mailmagik/tmp/' . $task_id, 0755, true);
                            }
                            $tmp_name = DATA_DIR . '/files/mailmagik/tmp/' . $task_id . '/' . $attachment->name;
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

                $this->helper->mailHelper->processMessage($mailbox, $mail_id);
            }
        }
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
        
        // Column

        if (($column_name = $this->extractColumn($subject)) != null) {
            if (($column_id = $this->columnModel->getColumnIdByTitle($project_id, $column_name)) > 0) {
                $attributes = array_merge($attributes, array('column_id' => $column_id));
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
        $attr = $this->extractAttribute($subject, $prefix, '\d{4}-\d{2}-\d{2}');
        $date = ($attr != null) ? $attr : '';

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
     * Extract the column from subject
     *
     * @param   string  subject
     * @return  string  extracted column
     * @return  NULL    no category found
     */
    private function extractColumn(&$subject)
    {
        return $this->extractAttribute($subject, 'col');
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
