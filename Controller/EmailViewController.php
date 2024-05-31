<?php

namespace Kanboard\Plugin\Mailmagik\Controller;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Model\CommentModel;
use Kanboard\Model\LinkModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\TaskExternalLinkModel;
use Kanboard\Model\TaskFileModel;
use Kanboard\Model\TaskFinderModel;
use Kanboard\Model\TaskLinkModel;
use Kanboard\Model\TaskModificationModel;
use Kanboard\Model\TaskTagModel;
use Kanboard\Model\UserMetadataModel;
use Kanboard\Model\UserModel;
use League\HTMLToMarkdown\HtmlConverter;
use PhpImap;
use PicoDb\SQLException;

/**
 * Mailmagik Plugin
 *
 * @author Craig Crosby
 */
class EmailViewController extends BaseController
{
    public const PREFIX = 'Task#';

    public function load()
    {
        $task = $this->getTask();

        if (($mailbox = $this->helper->mailHelper->login()) == false) {
            $this->flashFailure();
            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id']), false, '', '', $this->request->isAjax()));
        } else {
            $this->response->html($this->helper->layout->task('mailmagik:task_emails/task_load', array(
                'task' => $task,
                'project' => $this->projectModel->getById($task['project_id']),
                'title'   => $task['title'],
                'tags'    => $this->taskTagModel->getTagsByTask($task['id']),
            )));
        }
    }

    public function view()
    {
        $task = $this->getTask();
        $emails = array();

        if (($mailbox = $this->helper->mailHelper->login()) == false) {
            $this->flashFailure();
            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id']), false, '', '', $this->request->isAjax()));
        } else {
            $mails_ids = $this->helper->mailHelper->getTaskMails($mailbox, self::PREFIX);

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
                    $message = $email->textHtml;
                } else {
                    $message = $email->textPlain;
                }

                $attached_files = array();
                $images = array();
                if (!is_null($task_id) && intval($task_id) === intval($task['id'])) {
                    if (!empty($email->getAttachments())) {
                        $attachments = $email->getAttachments();
                        $att_dir = MM_FILES_DIR . $task['id'];
                        if (!file_exists($att_dir)) {
                            mkdir($att_dir, MM_PERM, true);
                        }
                        foreach ($attachments as $attachment) {
                            $attached_files[] = $attachment->name;
                            $att_path = $att_dir . '/' . $attachment->name;
                            $attachment->setFilePath($att_path);
                            if (!file_exists($att_path)) {
                                $attachment->saveToDisk();
                            }
                        }
                    }

                    if (!$this->userModel->getByEmail($from_email)) {
                        $connect_to_user = null;
                    } else {
                        $connect_to_user = $this->userModel->getByEmail($from_email);
                    }

                    $emails[] = array(
                        'mail_id' => $mail_id,
                        'task_id' => $task_id,
                        'project_id' => $this->projectModel->getById($task['project_id'])['id'],
                        'from_name' => $from_name,
                        'from_email' => $from_email,
                        'subject' => $subject,
                        'message_id' => $message_id,
                        'message' => $message,
                        'date' => $date,
                        'has_attach' => $email->hasAttachments() ? 'y' : 'n',
                        'attachments' => $attached_files,
                        'user' => $connect_to_user,
                        'parsed_taskdata' => $this->helper->parsing->parseData($email->textPlain),
                        'parsed_metadata' => $this->helper->parsing->parseData($email->textPlain, '$@', '@$'),
                    );
                }

                $mailbox->markMailAsRead($mail_id);
            }

            $emails = array_reverse($emails);

            $this->response->html($this->helper->layout->task('mailmagik:task_emails/task', array(
                'task' => $task,
                'project' => $this->projectModel->getById($task['project_id']),
                'emails' => $emails,
                'title'   => $task['title'],
                'tags'    => $this->taskTagModel->getTagsByTask($task['id']),
            )));
        }
    }

    /**
     * File download
     *
     * @access public
     */
    public function download()
    {
        $task_id =  $this->request->getIntegerParam('task_id');
        $name =  $this->request->getStringParam('name');

        try {
            $file = MM_FILES_DIR . $task_id . '/' . $name;
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($file).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            }
        } catch (ObjectStorageException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Apply the update
     */
    private function update_apply($values)
    {
        list($valid, $errors) = $this->taskValidator->validateModification($values);

        if ($valid) {
            try {
                if ($this->taskModificationModel->update($values)) {
                    $this->flash->success(t('Task updated successfully.'));
                } else {
                    $this->flash->failure('Database: Duplicate key error');
                }
            } catch (SQLException $e) {
                $this->flash->failure($e->getMessage());
            }
        } else {
            $this->flash->failure(t('Unable to update your task. This field is either not a valid field or the value is invalid.'));
        }

        $this->response->redirect($this->helper->url->to('EmailViewController', 'view', array('plugin' => 'mailmagik','task_id' => $values['id'])), true);
    }

    /**
     * Prepare ans apply the update.
     */
    private function performUpdate(&$updates, &$task)
    {
        [$valid, $denied_keys] = $this->helper->parsing->verifyData($updates, $task);

        if ($valid) {
            $values = array_merge($this->helper->parsing->getAllMeta($task['id']), $updates);
            $values['id'] = $task['id'];
            $values['project_id'] = $task['project_id'];
            $values['title'] = $task['title'];
            $this->update_apply($values);
        } else {
            $this->flash->failure(t('The following keys are either invalid or not allowed: ') . implode(',', $denied_keys));
            $this->response->redirect($this->helper->url->to('EmailViewController', 'view', array('plugin' => 'mailmagik','task_id' => $task['id'])), true);
        }
    }

    /**
     * Update Task Data Bulk
     *
     * @access public
     */
    public function update_taskdata_bulk()
    {
        $task = $this->getTask();
        $updates= $this->request->getValues();
        unset($updates['csrf_token']);

        $this->performUpdate($updates, $task);
    }

    /**
     * Update Task Data
     *
     * @access public
     */
    public function update_taskdata()
    {
        $task = $this->getTask();
        $key =  $this->request->getStringParam('key');
        $value =  $this->request->getStringParam('value');
        error_log('key:'.$key.' value:'.$value,0);

        if ($this->request->getIntegerParam('is_metamagik')) {
            $key = KEY_PREFIX . $key;
        }

        error_log('key:'.$key.' value:'.$value,0);

        $updates = array(
            $key => $value,
        );

        $this->performUpdate($updates, $task);
    }

    /**
     * Send attachments to a task
     *
     * @access public
     */
    public function sendAttachmentsToTask($task_id, $attachments)
    {
        foreach ($attachments as $attachment) {
            $this->helper->mailHelper->saveandUpload($task_id, $attachment);
        }
    }

    /**
     * Email delete
     *
     * @access public
     */
    public function delete()
    {
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');

        if (($mailbox = $this->helper->mailHelper->login()) == false) {
            $this->flashFailure();
        } else {
            $mailbox->deleteMail($mail_id);
            $mailbox->disconnect();
        }

        $this->response->redirect($this->helper->url->to('EmailViewController', 'view', array(
            'plugin' => 'mailmagik',
            'task_id' => $task_id,
        )));
    }

    /**
     * Confirm delete
     *
     * @access public
     */
    public function confirmDelete()
    {
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');

        $this->response->html($this->template->render('mailmagik:task_emails/delete', array(
            'task_id' => $task_id,
            'mail_id' => $mail_id,
        )));
    }

    /**
     * Confirm convert to task
     *
     * @access public
     */
    public function confirmConvertToTask()
    {
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');

        $this->response->html($this->template->render('mailmagik:task_emails/convert_task', array(
            'task_id' => $task_id,
            'mail_id' => $mail_id,
        )));
    }

    /**
     * Confirm convert to comment
     *
     * @access public
     */
    public function confirmConvertToComment()
    {
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');

        $this->response->html($this->template->render('mailmagik:task_emails/convert_comment', array(
            'task_id' => $task_id,
            'mail_id' => $mail_id,
        )));
    }

    /**
     * Convert Task Email to Task Butoon
     *
     * @access public
     */
    public function convertToTask()
    {
        $converter = new HtmlConverter(array('strip_tags' => true));
        $values = $this->request->getValues();
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');
        $task = $this->getTask();

        if (($mailbox = $this->helper->mailHelper->login()) == false) {
            $this->flashFailure();
        } else {
            $email = $mailbox->getMail(
                $mail_id,
                false
            );

            $subject = $this->parseSubject($email->subject, self::PREFIX);
            $message_id = $email->messageId;
            $date = $email->date;

            if ($email->textHtml) {
                //$email->embedImageAttachments();
                $message = $converter->convert($email->textHtml);
            } else {
                $message = $email->textPlain;
            }

            $task_id = $this->taskCreationModel->create(array(
                'project_id' => $task['project_id'],
                'title' => $subject,
                'description' => isset($message) ? $message : '',
            ));

            if (!empty($email->getAttachments()) && $values['mailmagik_include_files'] == 1) {
                $this->sendAttachmentsToTask($task_id, $email->getAttachments());
            }

            $this->helper->mailHelper->disposeMessage($mailbox, $mail_id);
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task_id)));
    }

    /**
     * Convert Email To Comment Butoon
     *
     * @access public
     */
    public function convertToComment()
    {
        $converter = new HtmlConverter(array('strip_tags' => true));
        $user = $this->getUser();
        $params = $this->request->getValues();
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');
        $task = $this->getTask();

        if (($mailbox = $this->helper->mailHelper->login()) == false) {
            $this->flashFailure();
        } else {
            $email = $mailbox->getMail(
                $mail_id,
                false
            );

            $subject = $this->parseSubject($email->subject, self::PREFIX);
            $message_id = $email->messageId;
            $date = $email->date;

            if ($email->textHtml) {
                $email->embedImageAttachments();
                $message = $converter->convert($email->textHtml);
            } else {
                $message = $email->textPlain;
            }

            $from_email = $email->fromAddress;

            if (!$this->userModel->getByEmail($from_email)) {
                $connect_to_user = null;
                $comment = '*Email converted to comment and originally sent by ' . $from_email . '*' . "\n\n" . (isset($subject) ? "#$subject\n\n" : '') . (isset($message) ? $message : '');
            } else {
                $connect_to_user = $this->userModel->getByEmail($from_email);
                $comment = '*Email converted to comment by ' . $user['username'] . '*' . "\n\n" . (isset($subject) ? "#$subject\n\n" : '') . (isset($message) ? $message : '');
            }

            $values = array(
                'task_id' => $task_id,
                'comment' => $comment,
                'user_id' => is_null($connect_to_user) ? $user['id'] : $connect_to_user['id'],
            );

            $comment_id = $this->commentModel->create($values);

            if (!empty($email->getAttachments()) && $params['mailmagik_include_files'] == 1) {
                $this->sendAttachmentsToTask($task_id, $email->getAttachments());
            }

            $this->helper->mailHelper->disposeMessage($mailbox, $mail_id);
            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task_id)));
        }
    }

    private function parseSubject($subject, string $prefix)
    {
        return preg_replace('/\[' . $prefix . '\d{1,}\] /', '', $subject, 1);
    }

    private function flashFailure()
    {
        $this->flash->failure(t('IMAP Server connection could not be established!'));
    }
}
