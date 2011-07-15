<?php
include("inc/conn.inc.php");
include("inc/Git.php");

class Check {
    var $root = "/srv/www/";
    var $checks = array();
    var $projectName;
    var $number_of_errors = 0;
    var $stageFolder;
        
    function getChecks(){
        return $this->checks;
    }        
       
    function getPathToGitRemote(){            
        return "git://github.com/konscript/".$this->projectName.'.git';  
    }        
    
    function getNumberOfErrors(){        
        return $this->number_of_errors;
    }    

    function getPathToStageFolder(){
        if($this->stageFolder=="dev"){
            return $this->root.$this->projectName."/dev";
        }else{
              $prod_folder = $this->root.$this->projectName."/prod/";
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
    
/*************** validations **************/    
    
    //check if git has been initialized 
    function checkGitInit(){
        $path = $this->getPathToStageFolder($this->stageFolder)."/.git";
        $status = is_dir($path) ? 0 : 1;
        $msg = array("success"=>"Git folder is created in $path", "error"=>"Initialize Git in ".$path);
        $this->addCheck($status, $msg, __function__);                  
    }
    
    //check if remote 'konscript' has been added            
    function checkGitRemote(){                                  
        $path = $this->getPathToStageFolder($this->stageFolder);
        $status = Git::git_callback('remote -v | grep "'.$this->getPathToGitRemote().'"', $path);
        $msg = array(
            "success"=>"Remote 'konscript' was found in $path", 
            "error"=>"Remote 'konscript' missing in: ".$path, 
            "tip"=> 'cd '.$path.' && git remote add konscript git://github.com/konscript/'.$this->projectName.'.git'
        );
        $this->addCheck($status, $msg, __function__); 
    }             
        
    //check the directory has the sufficient permissions    
    function checkFolderWritable($git = ""){    
        $path = $this->getPathToStageFolder($this->stageFolder).$git;
        $status = is_writable($path) ? 0 : 1;        
        $msg = array("success"=>"Folder is writable in $path", "error"=>"Folder not writable: ".$path, "tip"=> " Change permission for the folder and contents recursively:<br> chmod 770 ".$path." -R");
        $this->addCheck($status, $msg, __function__);    
    }        
    
    function checkFolderMustExist($git = ""){
        $path = $this->getPathToStageFolder($this->stageFolder).$git;
        $status = is_dir($path) ? 0 : 1;
        $msg = array("success"=>"Folder exists in $path", "error"=>"Folder does not exist: ".$path, "tip"=>"Create directory: <br>mkdir ".$path);
        $this->addCheck($status, $msg, __function__);            
    }        
    
}       
?>
