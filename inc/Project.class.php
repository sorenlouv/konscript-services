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
     * check that virtual file exists
     *************************************************/
    function checkVirtualHost(){
        $file = "/etc/apache2/sites-available/".$this->check->projectName;
        $msg = array("success"=>"Virtual host correctly setup", "error"=>"No virtual host file was found:". $file ." bool:".var_dump(file_exists($file)));
        $status = file_exists($file) ? 0 : 1;
        $msg["tip"] = "Create virtual host file: /etc/apache2/sites-available/".$this->check->projectName;        
        $this->check->addCheck($status, $msg, __function__);
    }        
        
}
?>
