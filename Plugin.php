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

        $this->initConfig(array(
            'kbphpimap_taskemail_pref' => '1',
            'kbphpimap_pref' => '2',
        ));

        // Hooks
        $option = $this->configModel->get('kbphpimap_taskemail_pref', '1');
        if ( $option == 1) { $this->template->hook->attach('template:task:sidebar:information', 'kbphpimap:task/emails'); } 
        
        
        //CONFIG HOOK
        $this->template->hook->attach('template:config:email', 'kbphpimap:config/config');
        
        //css
        $this->hook->on('template:layout:css', array('template' => 'plugins/Kbphpimap/Assets/css/kbphpimap.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/Kbphpimap/Assets/js/kbphpimap.js'));
        
        //ACTIONS
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

    private function initConfig(array $configs)
    {
        $values = array();

        foreach ($configs as $name => $value) {
            if ($this->configModel->get($name) == '')
            {
                $values += array($name => $value);
            }
        }

        if(!empty($values))
        {
            $this->configModel->save($values);
        }
    }

}
