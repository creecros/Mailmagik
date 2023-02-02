<?php

namespace Kanboard\Plugin\Kbphpimap\Action;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Model\UserModel;
use Kanboard\Model\ProjectModel;
use PhpImap;
use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;

/**
 * Email a task notification of impending due date 
 */
class ConvertEmailToTask extends Base
{
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

        
        $project = $this->projectModel->getById($data['project_id']);
        $emails = array();
        
        $mailbox = $this->login();
        
        try {
        	// Search in mailbox folder for specific emails
        	// PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
        	// Here, we search for "all" emails
        	$mails_ids = $mailbox->searchMailbox('UNSEEN');
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
            	    (strpos($to, 'Project#') == 0) ? $project_id = str_replace('Project#', '', $to) : $project_id = null;
        	    }
        	    $i++;
        	}
        	$subject = $email->subject;
        	$message_id = $email->messageId;
        	$date = $email->date;
        	$message = $email->textPlain;
        	
        	
            if($email->hasAttachments()) {
            		$has_attach = 'y';
            	} else {
            		$has_attach = 'n';
            	}
            
            if (!is_null($project_id) && intval($project_id) === intval($project['id'])) {
                
                if (!$this->userModel->getByEmail($from_email)) { $connect_to_user = null; } else { $connect_to_user = $this->userModel->getByEmail($from_email); }
                
                $task_id = $this->taskCreationModel->create(array(
                    'project_id' => $project_id,
                    'title' => $subject,
                    'description' => isset($message) ? $message : '',
                    'creator_id' => is_null($connect_to_user) ? '' : $connect_to_user['id'],
                    'column_id' => $this->getParam('column_id'),
                    'color_id' => $this->getParam('color_id'),
                ));
                
                if(!empty($email->getAttachments())) {
                		$attachments = $email->getAttachments();
                		foreach ($attachments as $attachment) {
                		    if (!file_exists(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id)) { mkdir(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id, 0755, true); }
                            $attachment->setFilePath(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id . '/' . $attachment->name);
                            if (!file_exists(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id . '/' . $attachment->name)) { $attachment->saveToDisk(); }
                            $file = file_get_contents(DATA_DIR . '/files/kbphpimap/tmp/' . $task_id . '/' . $attachment->name);
                            $this->taskFileModel->uploadContent($task_id, $attachment->name, $file, false);
                		}
                	} 
                
                $mailbox->markMailAsRead($mail_id);
                
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
      
}
