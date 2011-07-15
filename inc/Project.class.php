<?php

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
     * check if a Github account has been created
     *************************************************/
   function checkGithub(){
        $status = Git::git_callback('ls-remote '. $this->check->getPathToGitRemote());
        $msg = array("success"=>"Project was found on GitHub", "error"=>"Create project on GitHub");
        $this->check->addCheck($status, $msg, __function__);           
   }

    /**
     * Check Post hook - check if we have receive any payload from GitHub on the current project            
     *************************************************/    
    function checkHooks(){
        $connection = New DbConn();
        $connection->connect();
        $an_hour_ago = time()-3600;
        $query = "SELECT count(id) as antal FROM deployments WHERE repository_name = ? && created>".$an_hour_ago;
        $stmt = $connection->prep_stmt($query);    
        $stmt->bind_param("s", $this->projectName);
        $stmt->execute() or die("Error: ".$stmt->error); //Executing the statement
        $stmt->bind_result($number_of_deploys);    
        $stmt->fetch();    
        $status = $number_of_deploys > 0 ? 0 : 1;                
        $msg = array(
            "success"=>"Found payload from Github hook", 
            "error"=>"No payload has been received during the last hour.",
            "tip" => "Push to development repository from you local machine:<br> git push konscript master"
        );
        $this->check->addCheck($status, $msg, __function__);
    }      
        
    /**
     *
     *************************************************/
    function checkVirtualHost(){
        $status = 1;
        $file = "/etc/apache2/sites-available/".$this->projectName;
        $msg = array("success"=>"Virtual host correctly setup");
        
        if(file_exists($file)){
            $content = file_get_contents($file);
            if(strpos($content, "DocumentRoot /srv/www/".$this->projectName."/prod") !== false && 
                strpos($content, "DocumentRoot /srv/www/".$this->projectName."/dev") !== false){
                $status = 0;
            }else{
                $msg["error"] = "The virtual host file is misconfigured:<br> ".nl2br($content);
            }
        }else{
            $msg["error"] = "No virtual host file was found";
        }            
        $msg["tip"] = "Create virtual host file: /etc/apache2/sites-available/".$this->projectName." and add the following content (modifications might be necessary): <br> " . $this->getVirtualHostTemplate() ."<br> 
        Remember to enable the new virtual host: <br> sudo a2ensite " . $this->projectName;
        
        $this->check->addCheck($status, $msg, __function__);
    }        

    /**
     * 
     *************************************************/       
    function getVirtualHostTemplate(){
        $tmp = '
        <VirtualHost 178.79.137.106:80>
             ServerAdmin la@konscript.com
             ServerName ' . $this->projectName. '-prod.konscript.com
             //ServerAlias '.$this->projectName.'.com
             DocumentRoot '.$this->check->getPathToStageFolder("prod").'
        </VirtualHost>
                
        <VirtualHost 178.79.137.106:80>
             ServerAdmin la@konscript.com
             ServerName '.$this->projectName.'.konscript.com
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
