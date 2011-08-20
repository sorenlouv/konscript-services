<?php
include("conn.inc.php");
include("util.php");
include("Git.php");

/**
 * Main class for checks
 **************/

class Check {
    var $checks = array();
    var $projectName;
    var $number_of_errors = 0;
    var $stageFolder;
    
    // constructor - set root
    function check(){
    	global $web_root;
    	$this->web_root = $web_root;
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
        return "git@github.com:konscript/".$this->projectName.'.git'; 
    }        
    
    function getNumberOfErrors(){        
        return $this->number_of_errors;
    }    

    function getPathToStageFolder(){
        if($this->stageFolder=="dev"){
            return $this->web_root.$this->projectName."/dev";
        }else{
              $prod_folder = $this->web_root.$this->projectName."/prod/";
              if(!isset($this->latestProdVersion)){
                  $this->latest_prod_version = get_latest_prod_version($prod_folder);                
              }
              return $prod_folder.$this->latest_prod_version;
        }
    }        
    
    function setProjectName($projectName){
        $this->projectName = $projectName;
    }        

    function setStageFolder($stageFolder){
        $this->stageFolder = $stageFolder;
    }
                            
   function addCheck($status, $msg, $calling_function){        
        $msg["name"] = $calling_function;
        if($status>0){
            $this->number_of_errors++;
            $msg["status"] = 0;
        }else{
            $msg["status"] = 1;
        }
        $this->checks[] = $msg;  
   }           
   
	/**
	 * purge entire nginx cache for the current project
	 ***/	  
	function clearCache($projectName = false){    	
		$projectName = !$projectName ? $this->projectName : $projectName;
		$path_to_nginx_cache ="/var/cache/nginx/cached/".$projectName;    	
		chdir($path_to_nginx_cache);

		$status = 1;		
		if(getcwd()==$path_to_nginx_cache && trim(shell_exec("pwd"))==$path_to_nginx_cache){
			//exec("find -type f -exec rm -f {} \;", $output, $status);
			$status = 0;
		}

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
    
}       
?>
