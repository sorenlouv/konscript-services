<?php

/**
 * Check individual project for errors
 ****************/

class Project{

    var $check;

    //constructor
    function Project($projectName, $stageFolder, $check){
    	//set variables
        $check->setProjectName($projectName);
        $check->setStageFolder($stageFolder);
        $this->setCheck($check);        
        		    
	    //validators	            	       	    
        $check->checkGitRemote();  
	    $check->checkFolderMustExist();
	    $check->checkFolderMustExist("/.git");
	    $check->checkFolderWritable();
	    $check->checkFolderWritable("/.git");
    }    
    
    function setCheck($check){
        $this->check = $check;
    }          

       
    /**
     *
     *************************************************/
    function checkVirtualHost(){
        $status = 1;
        $file = "/etc/apache2/sites-available/".$this->check->projectName;
        $msg = array("success"=>"Virtual host correctly setup");
        
        if(file_exists($file)){
            $content = file_get_contents($file);
            if(strpos($content, "DocumentRoot /srv/www/".$this->check->projectName."/prod") !== false && 
                strpos($content, "DocumentRoot /srv/www/".$this->check->projectName."/dev") !== false){
                $status = 0;
            }else{
                $msg["error"] = "The virtual host file is misconfigured:<br> ".nl2br($content);
            }
        }else{
            $msg["error"] = "No virtual host file was found";
        }            
        $msg["tip"] = "Create virtual host file: /etc/apache2/sites-available/".$this->check->projectName." and add the following content (modifications might be necessary): <br> " . $this->getVirtualHostTemplate() ."<br> 
        Remember to enable the new virtual host: <br> sudo a2ensite " . $this->check->projectName;
        
        $this->check->addCheck($status, $msg, __function__);
    }        

    /**
     * 
     *************************************************/       
    function getVirtualHostTemplate(){
        $tmp = '
        <VirtualHost 178.79.137.106:80>
             ServerAdmin la@konscript.com
             ServerName ' . $this->check->projectName. '-prod.konscript.com
             //ServerAlias '.$this->check->projectName.'.com
             DocumentRoot '.$this->check->getPathToStageFolder("prod").'
        </VirtualHost>
                
        <VirtualHost 178.79.137.106:80>
             ServerAdmin la@konscript.com
             ServerName '.$this->check->projectName.'.konscript.com
             DocumentRoot '.$this->check->getPathToStageFolder("dev").'

              <Directory '.$this->check->getPathToStageFolder("dev").'>
                  <IfModule mod_php5.c>
                     php_value error_reporting 214748364
                     php_flag display_errors 1
                  </IfModule>
              </Directory>

        </VirtualHost>';      
        return nl2br(htmlspecialchars($tmp));
    }            
}
?>
