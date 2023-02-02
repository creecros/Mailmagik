<?php

namespace Kanboard\Plugin\Kbphpimap;

use Kanboard\Core\Plugin\Base;
use Kanboard\Plugin\Kbphpimap\Action\ConvertEmailToTask;
use Kanboard\Plugin\Kbphpimap\Action\ConvertEmailToComment;


class Plugin extends Base

{
	public function initialize()
	{
        if (!file_exists(DATA_DIR . '/files/kbphpimap/files')) { mkdir(DATA_DIR . '/files/kbphpimap/files', 0755, true); }

	    // Hooks
        $this->template->hook->attach('template:task:sidebar:information', 'kbphpimap:task/emails');
        
        //CONFIG HOOK
        $this->template->hook->attach('template:config:email', 'kbphpimap:config/config');
        
        //css
        $this->hook->on('template:layout:css', array('template' => 'plugins/Kbphpimap/Assets/css/kbphpimap.css'));
		
		$this->actionManager->register(new ConvertEmailToTask($this->container));
		$this->actionManager->register(new ConvertEmailToComment($this->container));

	}
	
	public function getPluginName()	
	{ 		 
		return 'Kbphpimap'; 
	}

	public function getPluginAuthor() 
	{ 	 
		return 'Craig Crosby'; 
	}

	public function getPluginVersion() 
	{ 	 
		return '1.1.0'; 
	}

	public function getPluginDescription() 
	{ 
		return 'handle emails'; 
	}

	public function getPluginHomepage() 
	{ 	 
		return 'https://github.com/creecros/kbphpimap'; 
	}
}
