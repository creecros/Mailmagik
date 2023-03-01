<?php

namespace Kanboard\Plugin\Mailmagik\Action;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Action\Base;
use Kanboard\Controller\BaseController;
use Kanboard\Model\CommentModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;
use Kanboard\Plugin\Mailmagik\Helper\MailHelper;
use League\HTMLToMarkdown\HtmlConverter;
use PhpImap;

/**
 * Action to convert email to a comment
 */
class ConvertEmailToComment extends Base
{
    public const PREFIX = 'CommentOnTask#';

    /**
     * Get automatic action description
     *
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return t('Automatically Convert Emails to Comments');
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
        return array();
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
            $task_id = $this->helper->mailHelper->getItemId($email, self::PREFIX);
            $subject = $email->subject;
            $message_id = $email->messageId;
            $date = $email->date;

            if ($email->textHtml) {
                $email->embedImageAttachments();
                $message = $converter->convert($email->textHtml);
                error_log('message:'.$message, 0);
            } else {
                $message = $email->textPlain;
            }


            if ($email->hasAttachments()) {
                $has_attach = 'y';
            } else {
                $has_attach = 'n';
            }

            $is_task = $this->taskFinderModel->exists($task_id);
            $project_id = ($is_task) ? $this->taskFinderModel->getProjectId($task_id) : 0;
            $is_in_project = ($project_id = $data['project_id']) ? true : false;

            if (!is_null($task_id) && $is_task && $is_in_project) {
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
                    $comment = (isset($subject) ? "#$subject\n\n" : '') . (isset($message) ? $message : '');
                    $values = array(
                        'task_id' => $task_id,
                        'comment' => $comment,
                        'user_id' => is_null($connect_to_user) ? '' : $connect_to_user['id'],
                    );

                    $this->commentModel->create($values);
                    if (!empty($email->getAttachments()) && $this->configModel->get('mailmagik_include_files_comments', '0') == 1) {
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
}
