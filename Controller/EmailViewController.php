<?php

namespace Kanboard\Plugin\Kbphpimap\Controller;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Model\UserModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\TaskFinderModel;
use Kanboard\Model\CommentModel;
use Kanboard\Model\TaskFileModel;
use Kanboard\Model\TaskLinkModel;
use Kanboard\Model\LinkModel;
use Kanboard\Model\TaskExternalLinkModel;
use Kanboard\Model\TaskTagModel;
use Kanboard\Model\UserMetadataModel;
use PhpImap;
use League\HTMLToMarkdown\HtmlConverter;


/**
 * Kbphpimap Plugin
 *
 * @author Craig Crosby
 */
class EmailViewController extends BaseController
{
    const PREFIX = 'Task#';
    const FILES_DIR = '/files/kbphpimap/files/';
    
    public function load()
    {
        $task = $this->getTask();
        $this->response->html($this->helper->layout->task('kbphpimap:task_emails/task_load', array(
            'task' => $task,
            'project' => $this->projectModel->getById($task['project_id']),
            'title'   => $task['title'],
            'tags'    => $this->taskTagModel->getTagsByTask($task['id']),
        )));
    }

    public function view()
    {
        $task = $this->getTask(); 
        $emails = array();
        
        $mailbox = $this->login();
        
        try {
        	// Search in mailbox folder for specific emails
        	// PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
        	// Here, we search for "all" emails
        	$mails_ids = $mailbox->searchMailbox('TO ' . self::PREFIX);
        } catch(PhpImap\Exceptions\ConnectionException $ex) {
        	die();
        }
        
        foreach($mails_ids as $mail_id) {
            
            $i = 0;

        	// Get mail by $mail_id
        	$email = $mailbox->getMail(
        		$mail_id, // ID of the email, you want to get
        		false // Do NOT mark emails as seen
        	);
        
        	$from_name = (isset($email->fromName)) ? $email->fromName : $email->fromAddress;
        	$from_email = $email->fromAddress;
        	foreach($email->to as $to){
        	    if ($i === 0 && $to != null) {
            	    (strpos($to, self::PREFIX) == 0) ? $task_id = trim(str_replace(self::PREFIX, '', $to), ' ') : $task_id = null;
        	    }
        	    $i++;
        	}
        	$subject = $email->subject;
        	$message_id = $email->messageId;
        	$date = $email->date;
            
        	if($email->textHtml) {
        	    $email->embedImageAttachments();
        		$message = $email->textHtml;
        	} else {
        		$message = $email->textPlain;
        	}
        	
            if($email->hasAttachments()) {
            		$has_attach = 'y';
            	} else {
            		$has_attach = 'n';
            	}
            
            $attached_files = array();

            $images = array();
            if (!is_null($task_id) && intval($task_id) === intval($task['id'])) {
                
                if(!empty($email->getAttachments())) {
                		$attachments = $email->getAttachments();
                		foreach ($attachments as $attachment) {
                		    $attached_files[] = $attachment->name;
                		    if (!file_exists(DATA_DIR . self::FILES_DIR . $task['id'])) { mkdir(DATA_DIR . self::FILES_DIR . $task['id'], 0755, true); }
                            $attachment->setFilePath(DATA_DIR . self::FILES_DIR . $task['id'] . '/' . $attachment->name);
                            if (!file_exists(DATA_DIR . self::FILES_DIR . $task['id'] . '/' . $attachment->name)) { $attachment->saveToDisk(); }
                		}
                	} 

                if (!$this->userModel->getByEmail($from_email)) { $connect_to_user = null; } else { $connect_to_user = $this->userModel->getByEmail($from_email); }

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
        
        $this->response->html($this->helper->layout->task('kbphpimap:task_emails/task', array(
            'task' => $task,
            'project' => $this->projectModel->getById($task['project_id']),
            'emails' => $emails,
            'title'   => $task['title'],
            'tags'    => $this->taskTagModel->getTagsByTask($task['id']),
        )));
        
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
     * Email delete
     *
     * @access public
     */
    public function delete()
    {
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');

        $mailbox = $this->login();
        	
        $mailbox->deleteMail($mail_id);
        $mailbox->disconnect();
        
        $this->view($task_id);
        
    }
    
    /**
     * Convert Task Email to Task Butoon
     *
     * @access public
     */
    public function convert()
    {
        $converter = new HtmlConverter();
        $mail_id =  $this->request->getIntegerParam('mail_id');
        $task_id =  $this->request->getIntegerParam('task_id');
        $task = $this->getTask(); 

        $mailbox = $this->login();
        $email = $mailbox->getMail(
        	$mail_id, 
        	false 
        );
        	
        $subject = $email->subject;
        $message_id = $email->messageId;
        $date = $email->date;
        	
        if($email->textHtml) {
        	$email->embedImageAttachments();
            $message = $converter->convert($email->textHtml);
        } else {
        	$message = $email->textPlain;
        }
        $message = $email->textPlain;
        	
        	
        if($email->hasAttachments()) {
            		$has_attach = 'y';
            	} else {
            		$has_attach = 'n';
            	}
            

        $task_id = $this->taskCreationModel->create(array(
            'project_id' => $task['project_id'],
            'title' => $subject,
            'description' => isset($message) ? $message : '',
        ));
                    
        if(!empty($email->getAttachments())) {
                $attachments = $email->getAttachments();
                foreach ($attachments as $attachment) {
                    if (!file_exists(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id)) { mkdir(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id, 0755, true); }
                    $tmp_name = DATA_DIR . '/files/kbphpimap/tmp/' . $task_id . '/' . $attachment->name;
                    $attachment->setFilePath($tmp_name);
                    if (!file_exists($tmp_name)) { $attachment->saveToDisk(); }
                    $file = file_get_contents($tmp_name);
                    $this->taskFileModel->uploadContent($task_id, $attachment->name, $file, false);
                    unlink($tmp_name);
                }
        } 
                
                
        $option = $this->configModel->get('kbphpimap_pref', '2');
                
        if ( $option == 2) { $mailbox->markMailAsRead($mail_id); } else { $mailbox->deleteMail($mail_id); }
     
        $this->show($task_id);
    }

        
    /**
     * Show a task
     *
     * @access public
     */
    public function show($task_id)
    {
        $task = $this->taskFinderModel->getDetails($task_id);
        $subtasks = $this->subtaskModel->getAll($task['id']);
        $commentSortingDirection = $this->userMetadataCacheDecorator->get(UserMetadataModel::KEY_COMMENT_SORTING_DIRECTION, 'ASC');

        $this->response->html($this->helper->layout->task('task/show', array(
            'task' => $task,
            'project' => $this->projectModel->getById($task['project_id']),
            'files' => $this->taskFileModel->getAllDocuments($task['id']),
            'images' => $this->taskFileModel->getAllImages($task['id']),
            'comments' => $this->commentModel->getAll($task['id'], $commentSortingDirection),
            'subtasks' => $subtasks,
            'internal_links' => $this->taskLinkModel->getAllGroupedByLabel($task['id']),
            'external_links' => $this->taskExternalLinkModel->getAll($task['id']),
            'link_label_list' => $this->linkModel->getList(0, false),
            'tags' => $this->taskTagModel->getTagsByTask($task['id']),
        )));
    }    
    
    /**
     * Email delete
     *
     * @access public
     */
    private function login()
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
        
}
