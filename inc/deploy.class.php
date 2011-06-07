<?php
include("inc/check.class.php");
class deploy extends check{
    function deploy($payload){    
        $this->payload = json_decode($payload);
        $this->setProjectName($this->payload->repository->name);
           
        if($this->getFullBranch() == 'refs/heads/master'){        
          $this->setPathToCurrentVersion("/srv/www/".$this->payload->repository->name."/dev");                                                  
          $this->setPathToNextVersion("/srv/www/".$this->payload->repository->name."/dev");   
           
           //Set Branch vars
           $this->branch_short = "master";
           $this->branch_folder = "dev";
        }elseif($this->getFullBranch() == 'refs/heads/prod'){        
            $this->validation_prod();
            $this->branch_short = "prod";
            $this->branch_folder = "prod";
        }       
        
        $this->validation();
    }   
       
    function getFullBranch(){
        return $this->payload->ref;
    }   
    
    function getNumberOfErrors(){        
        return $this->number_of_errors;
    }
    
    function setPathToNextVersion($path){
        $this->pathToNextVersion = $path;
    }    
    
    function setPathToCurrentVersion($path){
        $this->pathToCurrentVersion = $path;
    }            
    
    
    function cloneProd(){    
        if($this->getFullBranch() == 'refs/heads/prod'){                   
            //clone current version                     
            recurse_copy($this->pathToCurrentVersion, $this->pathToNextVersion);            
            
            //add temp virtual host
            unlink("/srv/www/temp/link1");
            symlink($this->pathToNextVersion , "/srv/www/temp/link1");                        
        }        
    }
    
    function validation_prod(){  
       $pathToProd = "/srv/www/".$this->payload->repository->name."/prod/";           
       $this->checkFolderWritable($pathToProd);          
    
       if($this->getNumberOfErrors()==0){
          $current_version = (int) get_latest_prod_version($pathToProd);     
          $next_version = $current_version + 1;              
          
          $this->setPathToCurrentVersion($pathToProd.$current_version);                                                  
          $this->setPathToNextVersion($pathToProd.$next_version);
          
          //validate that current version exists, and the next doesn't
          $this->checkFolderCannotExist($this->pathToNextVersion);              
          $this->checkFolderMustExist($this->pathToCurrentVersion);               
       }                       
    }
        
    
    function validation(){                                    
        //check git init            
        $this->checkGitInit($this->branch_folder);                
            
        //check git remote
        $this->checkGitRemote($this->branch_folder);      

        //check folder writability
        $folders = array($this->getPathToBranchFolder($this->branch_folder), $this->getPathToBranchFolder($this->branch_folder)."/.git");    
        foreach($folders as $folder){
            $this->checkFolderWritable($folder);       
        }                
    
        $this->checkBranch($this->payload);
        $this->checkRepName($this->payload);
        $this->checkGithubAccount($this->payload);
        $this->checkPayloadHost();
        //$this->checkPathRegex($this->branch_folder); 
        $this->checkFolderMustExist($this->branch_folder); //check folder exist. eg. uru/dev              
    }
    

    function gitPull(){                
        $git_response = Git::git_callback('pull konscript '.$this->branch_short, $this->pathToNextVersion, true);               
        $this->checkGitPull($git_response, $this->pathToNextVersion);                        
    }        
               
    function log_to_db(){        

        //make query
        $query = "INSERT INTO deployments (repository_name, commit_hash, last_commit_msg, number_of_commits, number_of_errors, payload, branch, ip_addr, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '".time()."')";

        //make connection do db
        $connection = New DbConn();
        $connection->connect();

        //prepare query statement
        $deployment = $connection->prep_stmt($query);                    
        
        //set variables
        $number_of_commits = count($this->payload->commits);  //count number of commit
        $last_commit_message = $this->payload->commits[$number_of_commits-1]->message; //select last commit message
        $encoded_payload = json_encode($this->payload);
        $number_of_errors = $this->getNumberOfErrors();
        
        //Bind parameters
        $deployment->bind_param("sssiisss", 
            $this->payload->repository->name, //Repository name
            $this->payload->after, //commit hash
            $last_commit_message, //The last commit message in the push
            $number_of_commits, //number of commits in the push
            $number_of_errors, //number of errors encountered during the deployment
            $encoded_payload, //the payload, encoded in json
            $this->payload->ref, //the branch
            $_SERVER["REMOTE_ADDR"] //ip address of client
        ); 

        //Executing the statement
        $deployment->execute() or die("Error: ".$deployment->error);                
        

        
        //make query
        $query = "INSERT INTO deployment_errors (deployment_id, msg) VALUES (?, ?)";

        //prepare query statement
        $addErrors = $connection->prep_stmt($query);  
            
        //bind parameters
        $addErrors->bind_param("is", $deployment_id, $msg);                
        
        //set variables
        $deployment_id = $deployment->insert_id;        
                                
        foreach($this->getChecks() as $check){
            if($check["status"] == false){        
                $msg = $check["error"];
                
                //Executing the statement
                $addErrors->execute() or die("Error: ".$addErrors->error);                        

            }
        }                  
    }    
    
}    
?>
