<?php
include("inc/Git.php");
class check {

    var $root = "/srv/www/";
    var $checks = array();
    var $projectName;
    var $number_of_errors = 0;
    
    function check($projectName){
        $this->setProjectName($projectName);
    }    
    
    function getChecks(){
        return $this->checks;
    }
    
    function setProjectName($projectName){
        $this->projectName = $projectName;
    }    
       
    function getPathToGitRemote(){            
        return "git://github.com/konscript/".$this->projectName.'.git';  
    }        
    
    function getPathToBranchFolder($branch_folder){
        if($branch_folder=="dev"){
            return $this->root.$this->projectName."/dev";
        }else{
              $prod_folder = $this->root.$this->projectName."/prod/";
              if(!isset($this->latestProdVersion)){
                  $this->latest_prod_version = get_latest_prod_version($prod_folder);                
              }
              return $prod_folder.$this->latest_prod_version;
        }
    }    
                            
   function addCheck($status, $msg){        
        if($status>0){
            $this->number_of_errors++;
            $msg["status"] = false;
        }else{
            $msg["status"] = true;
        }
        $this->checks[] = $msg;  
   }             
   
   
    /*** specific: receiver.php (payload) ***/              
   
    //must be pushed to valid branch (prod or master)
    function checkBranch($payload){
        $valid_branches = array('refs/heads/master', 'refs/heads/prod');
        $status = in_array($payload->ref, $valid_branches) ? 0 : 1;
        $msg = array("error"=>"Branch is not valid! ".$this->payload->ref);
        $this->addCheck($status, $msg);
    }

    //a repository name must be given
    function checkRepName($payload){
        $status = isset($payload->repository->name) ? 0 : 1;
        $msg = array("error"=>"The repository name was not set");
        $this->addCheck($status, $msg);
    }

    //the payload must have been pushed from Konscript's account
    function checkGithubAccount($payload){
        $status = strpos($payload->repository->url, "github.com/konscript") ? 0 : 1;
        $msg = array("error"=>"The deployment was not made from Konscript's account");
        $this->addCheck($status, $msg);
    }
 
    //Convert IP address of client to a hostname. This must always be github.com
    function checkPayloadHost(){
    	$ip = $_SERVER["REMOTE_ADDR"];
        if($ip == "127.0.0.1"){
        	return true;
        }
        	        	
        $status = (substr(gethostbyaddr($ip), -10) == "github.com") ? 0 : 1;
        $msg = array("error"=>"Illegal host: ".gethostbyaddr($ip));
        $this->addCheck($status, $msg);                  
    }
        
    //The directory must be located somewhere below /srv/www and have a prod or dev folder
    function checkPathRegex($branch_folder){
        $status = preg_match("'^/srv/www/[a-z-]+/(dev|prod)+/\d*$'", $this->getPathToBranchFolder($branch_folder)) ? 0 : 1;    
        $msg = array("error"=>"The path doesn't match the regex: ".$this->getPathToBranchFolder($branch_folder));
        $this->addCheck($status, $msg);
    }
        
    /*** general checks ***/          
     
    //check if git has been initialized 
    function checkGitInit($branch_folder){
        $path = $this->getPathToBranchFolder($branch_folder);
        $status = Git::git_callback('branch', $path);
        $msg = array("success"=>"Git is initialized in $path", "error"=>"Initialize Git in ".$path);
        $this->addCheck($status, $msg);                  
    }
    
    //check if remote 'konscript' has been added            
    function checkGitRemote($branch_folder){                                  
        $path = $this->getPathToBranchFolder($branch_folder);
        $status = Git::git_callback('remote -v | grep "'.$this->getPathToGitRemote().'"', $path);
        $msg = array(
            "success"=>"Remote 'konscript' was found in $path", 
            "error"=>"Remote 'konscript' missing in: ".$path, 
            "tip"=> 'cd '.$path.' && git remote add konscript git://github.com/konscript/'.$this->projectName.'.git'
        );
        $this->addCheck($status, $msg); 
    }                 
    
    function checkGitPull($git_response, $path){
        $status = $git_response[0];
        $msg = array("error"=>$git_response[1]);
        
        //roll back - delete cloned folder 
        if($status>0 && $deploy->getFullBranch() == 'refs/heads/prod'){
            //rrmdir($path);
            $msg["error"] = "(Disabled) Removed the folder: ".$path;
        }        
        $this->addCheck($status, $msg); 
    
    }

    //check the directory has the sufficient permissions    
    function checkFolderWritable($folder){        
        $status = is_writable($folder) ? 0 : 1;        
        $msg = array("success"=>"Folder is writable in $folder", "error"=>"Folder not writable: ".$folder, "tip"=> " Change permission for the folder and contents recursively:<br> chmod 770 ".$folder." -R");
        $this->addCheck($status, $msg);    
        //Write access needed for: %s");
    }    
        
    function checkFolderCannotExist($path){
        $status = !file_exists($path) ? 0 : 1;
        $msg = array("error"=>"The folder %s already exists");
        $this->addCheck($status, $msg);    
    }
    
    function checkFolderMustExist($branch_folder){
        $path = $this->getPathToBranchFolder($branch_folder);
        $status = is_dir($path) ? 0 : 1;
        $msg = array("success"=>"Folder is created in $path", "error"=>"Folder does not exist: ".$path, "tip"=>"Create directory: <br>mkdir ".$path);
        $this->addCheck($status, $msg);            
    }        
    
    /***** specific: check.php (check list) ******/
           
    //check virtual hosts
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
            $msg["error"] = "No virtual host file was found or misconfigured";
        }            
        $msg["tip"] = "Create virtual host file: /etc/apache2/sites-available/".$this->projectName." and add the following content (modifications might be necessary): <br> " . $this->getVirtualHostTemplate() ."<br> 
        Remember to enable the new virtual host: <br> sudo a2ensite " . $this->projectName;
        
        $this->addCheck($status, $msg);
    }    
        
   //check if a Github account has been created
   function checkGithub(){
        $status = Git::git_callback('ls-remote '. $this->getPathToGitRemote());
        $msg = array("success"=>"Project was found on GitHub", "error"=>"Create project on GitHub");
        $this->addCheck($status, $msg);           
   }
       
    //Check Post hook - check if we have receive any payload from GitHub on the current project            
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
        $status = $number_of_deploys>0 ? 0 : 1;                
        $msg = array(
            "success"=>"Found payload from Github hook", 
            "error"=>"No payload has been received during the last hour.",
            "tip" => "Push to development repository from you local machine:<br> git push konscript master"
        );
        $this->addCheck($status, $msg);
    }      
    
    function getVirtualHostTemplate(){
        $tmp = '
        <VirtualHost 178.79.137.106:80>
             ServerAdmin la@konscript.com
             ServerName '.$this->projectName.'.com
             ServerAlias www.' . $this->projectName . '.com
             DocumentRoot '.$this->getPathToBranchFolder("prod").'
        </VirtualHost>
                
        <VirtualHost 178.79.137.106:80>
             ServerAdmin la@konscript.com
             ServerName dev.'.$this->projectName.'.com
             DocumentRoot '.$this->getPathToBranchFolder("dev").'

          <Directory '.$this->getPathToBranchFolder("dev").'>
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
