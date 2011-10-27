<?php
include("Mysql.php");
include("util.php");
include("Git.php");

/**
 * Main class for checks
 **************/

class Check {
    var $checks = array();
    var $project_id;
    var $number_of_errors = 0;
    
    // constructor - set root
    function check(){
		set_error_handler(array($this, "custom_error_handler"), E_WARNING);
    	global $web_root, $service_root;
    	$this->web_root = $web_root;
		$this->service_root = $service_root;
    }
    
	function custom_error_handler($errno, $errstr, $errfile, $errline) {
			$this->addCustomError($errstr, $errfile, $errline);			
	}    
        
    function getChecks(){
        return $this->checks;
    }        
    
	function outputResult($success = "Success!"){	
		
		// output errors
			$html = '<div class="flash-message error">';
			foreach($this->checks as $check){			
				if($check["status"] == 0){
					$html .= "Error: " . $check["error"] . "<br/>";
				// }elseif(isset($check["success"]) && $verbose == true){
				//	$html .= "Success: " . $check["success"] . " [" .$check["name"]."]<br/>";
				}
			}
			$html .= "</div>";
		
		// output success only if no errors occured
		if($this->getNumberOfErrors() == 0){
			$html = '<div class="flash-message success">'.$success.'</div>';
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
    
    function getPathToNewestVersion(){
      $prod_folder = $this->web_root.$this->project_id."/prod/";
      if(!isset($this->latestProdVersion)){
          $this->latest_prod_version = get_latest_prod_version($prod_folder);                
      }
      return $prod_folder.$this->latest_prod_version;
    }        
    
	function getPathToDev(){
        return $this->web_root.$this->project_id."/dev";
    }
    
    function setProjectId($project_id){
        $this->project_id = $project_id;
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
   
	function addCustomError($error_msg, $file, $line){
		$status = 1; //error
		$file = str_replace($this->service_root,"", $file);
		$msg["error"] = "$error_msg in $file:$line";
		$this->addCheck($status, $msg, __function__);                  
	}   
   
	/**
	 * purge entire nginx cache for the current project
	 ***/	  
	function clearCache(){    	
		$path_to_nginx_cache ="/var/cache/nginx/cached/".$this->project_id;   		
		$status = 1;		
		
		if(isset($this->project_id) && is_dir($path_to_nginx_cache)){
			$chdir = is_dir($path_to_nginx_cache) ? chdir($path_to_nginx_cache) : false;

			if($chdir && getcwd()==$path_to_nginx_cache && trim(shell_exec("pwd"))==$path_to_nginx_cache){
				exec("find $path_to_nginx_cache -type f -exec rm -f {} \;", $output, $status);
				$status = 0;
			}
		}

		// build error message
		$error_msg = "Cache could not be cleared in: $path_to_nginx_cache (";
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
     * check if remote 'konscript' has been added            
     */    
    function checkGitRemote($path){                                  
        $status = Git::git_callback('remote -v | grep "'.$this->getPathToGitRemote().'"', $path);
        $msg = array(
            "success"=>"Remote 'konscript' was found in $path", 
            "error"=>"Remote 'konscript' missing in: ".$path, 
            "tip"=> 'cd '.$path.' && git remote add konscript '.$this->getPathToGitRemote()
        );
        $this->addCheck($status, $msg, __function__); 
    }             

    /**
     * The folder must be writable    
     */        
    function folderWritable($path){    
        $status = is_writable($path) ? 0 : 1;        
        $msg = array("success"=>"Folder is writable in $path", "error"=>"Folder not writable: ".$path, "tip"=> " Change permission for the folder and contents recursively:<br> chmod 770 ".$path." -R");
        $this->addCheck($status, $msg, __function__);    
    }        
    
    /**
     * Error if folder does NOT exist
     */    
    function folderMustExist($path){
        $status = is_dir($path) ? 0 : 1;
        $msg = array("success"=>"Folder exists in $path", "error"=>"Folder does not exist: ".$path, "tip"=>"Create directory: <br>mkdir ".$path);
        $this->addCheck($status, $msg, __function__);            
    }   
    
	/**
	 * Error if folder exists
	 ***************************************/
    function folderCannotExist($path){
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
        $msg = array("success"=>"Apache virtual host was found", "error"=>" Apache virtual host is missing: $file");
        $status = file_exists($file) ? 0 : 1;
        $msg["tip"] = "Create virtual host: $file";        
        $this->addCheck($status, $msg, __function__);
    }        

    /**
     * check that virtual file exists
     *************************************************/
    function checkVhostNginx(){
        $file = "/etc/nginx/sites-available/".$this->project_id;
        $msg = array("success"=>"Nginx virtual host was found", "error"=>"Nginx virtual host is missing: $file");
        $status = file_exists($file) ? 0 : 1;
        $msg["tip"] = "Create virtual host: $file";        
        $this->addCheck($status, $msg, __function__);
    }        

	/**
	 * Check individual project for errors
	 ****************/   
	function checkProject($path){   
		$this->checkGitRemote($path);  
		$this->folderMustExist($path);
		$this->folderMustExist($path."/.git");
		$this->folderWritable($path);
		$this->folderWritable($path."/.git");      
	}
	
	/**
	 * field not empty
	 ****************/   
	function notEmpty($field){   		
	    $status = empty($field) ? 1 : 0;
	    $msg["error"] = "Fields marks with stars cannot be empty!";
	    $this->addCheck($status, $msg, __function__);	
	}	
	
    /**
     * check that Apache and Nginx have been restarted since created/modified
     *************************************************/
    function checkRestart(){
		$status = 0;
		$error_msg = "";		
        $file = $this->service_root."/bash/uptime.sh";
        $uptime = json_decode(shell_exec($file), true);
                   
                
		// mysql connection
		$mysql = new Mysql();
		$project = $mysql->getProject($this->project_id);
		$modified = $project["modified"];
		
		// does apache needs a restart?
		if($modified > $uptime["apache"]){
			$error_msg = " Apache";
			$status = 1;
		}
		
		// does nginx needs a restart?
		/*
		// disabled until I find a way to determine last reload for nginx
		if($modified > $uptime["nginx"]){
			$error_msg .= $error_msg =="" ? " Nginx" : " and Nginx" ;
			$status = 1;
		}		
		*/
        
        $msg = array("success"=>"No restart required", "error"=>"A restart for $error_msg is required");
        $msg["tip"] = "<b>test conf files:</b> sudo nginx -t && sudo apache2ctl -t <br/> <b>Restart:</b> sudo service nginx reload && sudo service apache2 reload 
";        
        $this->addCheck($status, $msg, __function__);
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
	
	function ignoreWhiteSpace($str){
		return str_replace(array("\n", "\r", "\t", " "), '', $str);
	}
	
	
   /**
    * Produce content for the new virtual host for Apache/Nginx and check that all changes are reflected in the physical files
    *********/
	function get_vhost($filename, $fields, $check=true){
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
	
				// compare lines (ignore whitespace). If they are identical, the line will be replace by the new 
				if((strcmp($this->ignoreWhiteSpace($line), $this->ignoreWhiteSpace($field[0])) == 0)){	
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
			$msg["error"] = "Following string missing in vhost: ".implode("<br>", $errors);
	        $this->addCheck($status, $msg, __function__);
        }

		// array to string
		$content = implode("", $content);

		return $content;
	}   
	
	function testPull(){
	
		$payload = '{
		  "after": "123456789", 
		  "commits": [
			{
			  "added": [
				"some\/file.htm"
			  ], 
			  "author": {
				"email": "admin@konscript.com", 
				"name": "Caesar", 
				"username": "Caesar"
			  }, 
			  "distinct": true, 
			  "id": "12345678", 
			  "message": "This is a test pull!", 
			  "modified": [], 
			  "removed": [], 
			  "timestamp": "2011-06-04T04:17:24-07:00", 
			  "url": "https:\/\/github.com\/konscript\/konteaser\/commit\/e67d6ea72842a7b76dbc6e14f0c672eb8f07f97d"
			} 
		  ], 
		  "ref": "refs\/heads\/master", 
		  "repository": {
			"name": "'.$this->project_id.'", 
			"url": "https:\/\/github.com\/konscript\/'.$this->project_id.'"
		   }
		}';

		$receiver = new Receiver($payload, $this, true);
		
		//passed all preliminary validators
		if ($this->getNumberOfErrors() == 0){
			$git_response = Git::git_callback('pull konscript master', $this->web_root.$receiver->payload->repository->name."/dev", true);
			$this->checkGitPull($git_response);                                          
		}

		$receiver->log_to_db();				
	}

}       
?>
