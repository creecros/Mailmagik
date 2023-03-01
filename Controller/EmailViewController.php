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
use Kanboard\Model\TaskTagModel;
use Kanboard\Model\UserMetadataModel;
use Kanboard\Model\UserModel;
use League\HTMLToMarkdown\HtmlConverter;
use PhpImap;

/**
 * Mailmagik Plugin
 *
 * @author Craig Crosby
 */
class EmailViewController extends BaseController
{
    public const PREFIX = 'Task#';
    public const FILES_DIR = '/files/mailmagik/files/';

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

                if ($email->hasAttachments()) {
                    $has_attach = 'y';
                } else {
                    $has_attach = 'n';
                }

                $attached_files = array();

                $images = array();
                if (!is_null($task_id) && intval($task_id) === intval($task['id'])) {
                    if (!empty($email->getAttachments())) {
                        $attachments = $email->getAttachments();
                        foreach ($attachments as $attachment) {
                            $attached_files[] = $attachment->name;
                            if (!file_exists(DATA_DIR . self::FILES_DIR . $task['id'])) {
                                mkdir(DATA_DIR . self::FILES_DIR . $task['id'], 0755, true);
                            }
                            $attachment->setFilePath(DATA_DIR . self::FILES_DIR . $task['id'] . '/' . $attachment->name);
                            if (!file_exists(DATA_DIR . self::FILES_DIR . $task['id'] . '/' . $attachment->name)) {
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
                        'has_attach' => $has_attach,
                        'attachments' => $attached_files,
                        'user' => $connect_to_user,
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
            $file = DATA_DIR . self::FILES_DIR . $task_id . '/' . $name;
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
     * Send attachments to a task
     *
     * @access public
     */
    public function sendAttachmentsToTask($task_id, $attachments)
    {

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
        $this->view($task_id);
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
        $converter = new HtmlConverter();
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

            $subject = $email->subject;
            $message_id = $email->messageId;
            $date = $email->date;

            if ($email->textHtml) {
                //$email->embedImageAttachments();
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

            $task_id = $this->taskCreationModel->create(array(
                'project_id' => $task['project_id'],
                'title' => $subject,
                'description' => isset($message) ? $message : '',
            ));

            if (!empty($email->getAttachments()) && $values['mailmagik_include_files'] == 1) {
                $this->sendAttachmentsToTask($task_id, $email->getAttachments());
            }

            $this->helper->mailHelper->processMessage($mailbox, $mail_id);
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task_id), false, '', '', $this->request->isAjax()));
    }

    /**
     * Convert Email To Comment Butoon
     *
     * @access public
     */
    public function convertToComment()
    {
        $converter = new HtmlConverter();
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
            $from_email = $email->fromAddress;

            if ($email->hasAttachments()) {
                $has_attach = 'y';
            } else {
                $has_attach = 'n';
            }

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
            
            $task_id = $task_id . '#comment-' . $comment_id;
            $this->helper->mailHelper->processMessage($mailbox, $mail_id);

            $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task_id), false, '', '', $this->request->isAjax(), 'comment-'.$comment_id));
        }
    }

    private function flashFailure()
    {
        $this->flash->failure(t('IMAP Server connection could not be established!'));
    }
}
