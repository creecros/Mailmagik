<?php

namespace Kanboard\Plugin\Kbphpimap\Controller;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use PhpImap;


/**
 * Kbphpimap Plugin
 *
 * @author Craig Crosby
 */
class EmailViewController extends BaseController
{

    public function view()
    {
        $task = $this->getTask();
        
        $emails = array();
        
        $server = $this->configModel->get('kbphpimap_server', '');
        $port = $this->configModel->get('kbphpimap_port', '');
        $user = $this->configModel->get('kbphpimap_user', '');
        $password = $this->configModel->get('kbphpimap_password', '');
        
        $mailbox = new PhpImap\Mailbox(
        	'{'.$server.':' . $port . '/imap/ssl}INBOX', 
        	$user, 
        	$password, 
        	false
        	//DATA_DIR . '/files/kbphpimap/files' // when $attachmentsDir is false we don't save attachments
        );
        
        try {
        	// Search in mailbox folder for specific emails
        	// PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
        	// Here, we search for "all" emails
        	$mails_ids = $mailbox->searchMailbox('ALL');
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
        	    if ($i === 0) {
            	    (strpos($to, 'Task#') == 0) ? $task_id = str_replace('Task#', '', $to) : $task_id = null;
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
                		    $attached_files[] = array(
                		        'filename' => $attachment->filename,
                		        'name' => $attach_names[] = $attachment->name,
                		        );
                		    if (!file_exists(DATA_DIR . '/files/kbphpimap/files/' . $task['id'])) { mkdir(DATA_DIR . '/files/kbphpimap/files/' . $task['id'], 0755, true); }
                            $attachment->setFilePath(DATA_DIR . '/files/kbphpimap/files/' . $task['id'] . '/' . $attachment->name);
                            if (!file_exists(DATA_DIR . '/files/kbphpimap/files/' . $task['id'] . '/' . $attachment->name)) { $attachment->saveToDisk(); }
                		}
                	} 

                $emails[] = array(
                    'task_id' => $task_id,
                    'from_name' => $from_name,
                    'from_email' => $from_email,
                    'subject' => $subject,
                    'message_id' => $message_id,
                    'message' => $message,
                    'date' => $date,
                    'has_attach' => $has_attach,
                    'attachments' => $attached_files,
                );
            }

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
        $filename =  $this->request->getStringParam('filename');
        
        try {
            $file = DATA_DIR . '/files/kbphpimap/files/' . $task_id . '/' . $name;
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
        
}
