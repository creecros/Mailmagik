<?php

namespace Kanboard\Plugin\Kbphpimap\Action;

require __DIR__.'/../vendor/autoload.php';

use Kanboard\Controller\BaseController;
use Kanboard\Model\UserModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\ProjectUserRoleModel;
use Kanboard\Model\CommentModel;
use PhpImap;
use Kanboard\Model\TaskModel;
use Kanboard\Action\Base;

/**
 * Email a task notification of impending due date 
 */
class ConvertEmailToComment extends Base
{
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
            	    (strpos($to, 'CommentOnTask#') == 0) ? $task_id = str_replace('CommentOnTask#', '', $to) : $task_id = null;
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
            	
            $is_task = $this->taskFinderModel->exists($task_id);
            $project_id = ($is_task) ? $this->taskFinderModel->getProjectId($task_id) : 0;
            $is_in_project = ($project_id = $data['project_id']) ? true : false;
            
            if (!is_null($task_id) && $is_task && $is_in_project) {
                
                if (!$this->userModel->getByEmail($from_email)) { $connect_to_user = null; } else { $connect_to_user = $this->userModel->getByEmail($from_email); }
                
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
                    $values = array(
                        'task_id' => $task_id,
                        'comment' => isset($message) ? $message : '',
                        'user_id' => is_null($connect_to_user) ? '' : $connect_to_user['id'],
    
                    );
                    
                    $this->commentModel->create($values);
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
