<?php
include("conn.inc.php");
include("util.php");
include("Git.php");

/**
 * Main class for checks
 **************/

class Check {
    var $checks = array();
    var $project_id;
    var $number_of_errors = 0;
    var $stageFolder;
    
    // constructor - set root
    function check(){
		set_error_handler(array($this, "custom_error_handler"), E_WARNING);
    	global $web_root;
    	$this->web_root = $web_root;
    }
    
	function custom_error_handler($errno, $errstr) {
			$this->addCustomError($errstr);			
	}    
        
    function getChecks(){
        return $this->checks;
    }        
    
	function outputResult($verbose = false){	
		
		// output errors
			$html = '<div class="flash-message error">';
			foreach($this->checks as $check){			
				if($check["status"] == 0){
					$html .= "Error: " . $check["error"] . " [" .$check["name"]."]<br/>";
				}elseif(isset($check["success"]) && $verbose == true){
					$html .= "Success: " . $check["success"] . " [" .$check["name"]."]<br/>";
				}
			}
			$html .= "</div>";
		
		// output success
		if($verbose == false && $this->getNumberOfErrors() == 0){
			$html = '<div class="flash-message success">Project successfully deployed!</div>';
		}				
		
		return $html;
	}	    
       
    function getPathToGitRemote(){            
        return "git@github.com:konscript/".$this->project_id.'.git'; 
    }        
    
    function getDefaultDevDomain(){            
        return $this->project_id.'.konscript.com'; 
    }            
    
    function getNumberOfErrors(){        
        return $this->number_of_errors;
    }    
    
    function getPathToStageFolder(){
        if($this->stageFolder=="dev"){
            return $this->web_root.$this->project_id."/dev";
        }else{
              $prod_folder = $this->web_root.$this->project_id."/prod/";
              if(!isset($this->latestProdVersion)){
                  $this->latest_prod_version = get_latest_prod_version($prod_folder);                
              }
              return $prod_folder.$this->latest_prod_version;
        }
    }        
    
    function setProjectId($project_id){
        $this->project_id = $project_id;
    }        

    function setStageFolder($stageFolder){
        $this->stageFolder = $stageFolder;
    }
                            
   function addCheck($status, $msg, $calling_function){        
        $msg["name"] = $calling_function;
        
        // an error occured
        if($status>0){
            $this->number_of_errors++;
            $msg["status"] = 0;
            
        // no errors occured
        }else{
            $msg["status"] = 1;
        }
        $this->checks[] = $msg;  
   }           
   
	function addCustomError($custom_error_msg){
		$status = 1; //error
		$msg["error"] = $custom_error_msg;
		$this->addCheck($status, $msg, __function__);                  
	}   
   
	/**
	 * purge entire nginx cache for the current project
	 ***/	  
	function clearCache(){    	
		$path_to_nginx_cache ="/var/cache/nginx/cached/".$this->project_id;   		
		$status = 1;		
		
		if(is_dir($path_to_nginx_cache)){
			$chdir = is_dir($path_to_nginx_cache) ? chdir($path_to_nginx_cache) : false;

			if($chdir && getcwd()==$path_to_nginx_cache && trim(shell_exec("pwd"))==$path_to_nginx_cache){
				//exec("find -type f -exec rm -f {} \;", $output, $status);
				$status = 0;
			}
		}

		// build error message
		$error_msg = "Cache not cleared in: $path_to_nginx_cache (";
		$error_msg .= " PHP: ".getcwd();
		$error_msg .= " Shell: ".shell_exec("pwd").")";

		$msg = array(
			"success"=>"Cache cleared in: $path_to_nginx_cache", 
			"error"=>$error_msg
		);
		$this->addCheck($status, $msg, __function__);		
	}		              
    
/*************** validations **************/    
    
    /**
     * check if git has been initialized 
     */    
    function checkGitInit(){
        $path = $this->getPathToStageFolder()."/.git";
        $status = is_dir($path) ? 0 : 1;
        $msg = array("success"=>"Git folder is created in $path", "error"=>"Initialize Git in ".$path);
        $this->addCheck($status, $msg, __function__);                  
    }
    
    /**
     * check if remote 'konscript' has been added            
     */    
    function checkGitRemote(){                                  
        $path = $this->getPathToStageFolder();
        $status = Git::git_callback('remote -v | grep "'.$this->getPathToGitRemote().'"', $path);
        $msg = array(
            "success"=>"Remote 'konscript' was found in $path", 
            "error"=>"Remote 'konscript' missing in: ".$path, 
            "tip"=> 'cd '.$path.' && git remote add konscript '.$this->getPathToGitRemote()
        );
        $this->addCheck($status, $msg, __function__); 
    }             

    /**
     * check the directory has the sufficient permissions. It must be writable    
     */        
    function checkFolderWritable($git = ""){    
        $path = $this->getPathToStageFolder().$git;
        $status = is_writable($path) ? 0 : 1;        
        $msg = array("success"=>"Folder is writable in $path", "error"=>"Folder not writable: ".$path, "tip"=> " Change permission for the folder and contents recursively:<br> chmod 770 ".$path." -R");
        $this->addCheck($status, $msg, __function__);    
    }        
    
    /**
     * Error if folder does NOT exist
     */    
    function checkFolderMustExist($append_directories = ""){
        $path = $this->getPathToStageFolder().$append_directories;
        $status = is_dir($path) ? 0 : 1;
        $msg = array("success"=>"Folder exists in $path", "error"=>"Folder does not exist: ".$path, "tip"=>"Create directory: <br>mkdir ".$path);
        $this->addCheck($status, $msg, __function__);            
    }   
    
	/**
	 * Error if folder exists
	 ***************************************/
    function checkFolderCannotExist($path){
        $status = !file_exists($path) ? 0 : 1;
        $msg = array("success"=>"The folder does not exist: $path", "error"=>"The folder already exists: $path");
        $this->addCheck($status, $msg, __function__);    
    }            
    
	/**
	 * Analyze the return code from the "git pull" command
	 ***************************************/    
    function checkGitPull($git_response){
        $status = $git_response[0];
        $msg = array("success"=>$git_response[1], "error"=>$git_response[1]);                      
        $this->addCheck($status, $msg, __function__);     
    }      
    
    /**
     * check if a Github account has been created
     *************************************************/
   function checkGithub(){
        $status = Git::git_callback('ls-remote '. $this->getPathToGitRemote());
        $msg = array("success"=>"Project was found on GitHub", "error"=>"Create project on GitHub");
        $this->addCheck($status, $msg, __function__);           
   }           
   
    /**
     * check that virtual file exists
     *************************************************/
    function checkVhostApache(){
        $file = "/etc/apache2/sites-available/".$this->project_id;
        $msg = array("success"=>"Apache virtual host was found", "error"=>" Apache virtual host is missing:");
        $status = file_exists($file) ? 0 : 1;
        $msg["tip"] = "Create virtual host: /etc/apache2/sites-available/".$this->project_id;        
        $this->addCheck($status, $msg, __function__);
    }        

    /**
     * check that virtual file exists
     *************************************************/
    function checkVhostNginx(){
        $file = "/etc/nginx/sites-available/".$this->project_id;
        $msg = array("success"=>"Nginx virtual host was found", "error"=>"Nginx virtual host is missing:");
        $status = file_exists($file) ? 0 : 1;
        $msg["tip"] = "Create virtual host: /etc/nginx/sites-available/".$this->project_id;        
        $this->addCheck($status, $msg, __function__);
    }        

	/**
	 * Check individual project for errors
	 ****************/   
	function checkProject($stageFolder){   
		//set variables

		$this->setStageFolder($stageFolder);
				
		//validators	            	       	    
		$this->checkGitRemote();  
		$this->checkFolderMustExist();
		$this->checkFolderMustExist("/.git");
		$this->checkFolderWritable();
		$this->checkFolderWritable("/.git");      
	}
	
	
	function writeToFile($filename, $content){
		$res = false;
		if ($this->getNumberOfErrors() == 0){        
			$res = file_put_contents($filename, $content);	
		}
		
		if($res === false){
			$status = 1;
			$msg["error"] = "$filename could not be updated";
	        $this->addCheck($status, $msg, __function__);
		}
	}
   
   /**
    * update virtual host for Apache/Nginx and check that all changes are reflected in the physical files
    *********/
	function update_vhost($filename, $fields){
		$content = file($filename);

		// remove unchanged
		foreach($fields as $field_id=>$field){
			if($field[0] == $field[1]){
				unset($fields[$field_id]);
			}
		}

		// read file per line
		foreach ($content as $line_num => $line) {	
			foreach($fields as $field_id=>$field){
	
				// trim whitespace and compare lines. If they are identical, the line will be replace by the new 
				if((strcmp(trim($line), trim($field[0])) == 0)){	
					$content[$line_num] = $field[1]."\n";
					$fields[$field_id][2] = true;
				}				
			}			
		}		

		// make sure that all changed field were updated
		$errors = array();
		foreach($fields as $field_id=>$field){
			if(!isset($field[2])){
				$errors[] = $field[0];
			}
		}

		if(count($errors)>0){
			$status = 1; //error
			$msg["error"] = "Following string missing in vhost: ".implode("", $errors);
	        $this->addCheck($status, $msg, __function__);
        }

		// array to string
		$content = implode("", $content);

		return $content;
	}   

}       
?>
