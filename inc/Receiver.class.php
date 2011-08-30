<?php

/*
 * Receives payloads from GitHub and processes them. Valid payloads are pulled to appropriate projects, and invalid are ignored.
 *******************/

class Receiver{		
	var $payload;
	var $check;

    function Receiver($payload, $check){        
		//set variables  
        $this->setPayload($payload);        
        $this->setCheck($check);
        $check->setProjectId($this->payload->repository->name);    
            
	    //validators                                                    
        $this->checkBranch();
        $this->checkRepName();
        $this->checkGithubSenderAccount();
        $this->checkSenderHost();
        $check->checkProject($check->getPathToDev());
    }                                   
    
    function setPayload($payload){
        $this->payload = json_decode($payload);
    }        
    
    function setCheck($check){
        $this->check = $check;
    }       

/*************** validation methods ******************/   
    
	/**
	 * must be pushed to a valid (master) branch
	 ***************************************/    
    function checkBranch(){
        $valid_branches = array('refs/heads/master');
        $status = in_array($this->payload->ref, $valid_branches) ? 0 : 1;
        $msg = array("error"=>"Branch is not valid! ".$this->payload->ref);
        $this->check->addCheck($status, $msg, __function__);
    }

	/**
	 * a repository name (later called project id) must be given
	 ***************************************/    
    function checkRepName(){
        $status = isset($this->payload->repository->name) ? 0 : 1;
        $msg = array("error"=>"The repository name was not set");
        $this->check->addCheck($status, $msg, __function__);
    }
    
	/**
	 * the payload must have been pushed from Konscript's account
	 ***************************************/    
    function checkGithubSenderAccount(){
        $status = strpos($this->payload->repository->url, "github.com/konscript") ? 0 : 1;
        $msg = array("error"=>"The deployment was not made from Konscript's account");
        $this->check->addCheck($status, $msg, __function__);
    }
 
	/**
	 * Convert IP address of client to a hostname. This must always be github.com
	 ***************************************/    
    function checkSenderHost(){
    	$ip = $_SERVER["REMOTE_ADDR"];
    	$host = gethostbyaddr($ip);
    	
        if($ip == "127.0.0.1"){	return true; }
        	        	
        $status = substr($host, -10) == "github.com" ? 0 : 1;
        $msg = array("error"=>"Illegal host: ".$host);
        $this->check->addCheck($status, $msg, __function__);                  
    }    
    
	/**
	 * all errors will be written do db
	 ***************************************/               
    function log_to_db(){        

        //make query
        $query = "INSERT INTO deployments (project_id, author_name, commit_hash, last_commit_msg, number_of_commits, number_of_errors, payload, ip_addr, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '".time()."')";

        //make connection do db
        $connection = New DbConn();
        $connection->connect();

        //prepare query statement
        $deployment = $connection->prep_stmt($query);                    
        
        //set variables
        $number_of_commits = count($this->payload->commits);  //count number of commit
        $last_commit_message = $this->payload->commits[$number_of_commits-1]->message; //select last commit message
        $encoded_payload = json_encode($this->payload);
        $number_of_errors = $this->check->getNumberOfErrors();
        
        //Bind parameters
        $deployment->bind_param("ssssiiss", 
            $this->payload->repository->name, //Repository name
            $this->payload->commits[0]->author->name, //author
            $this->payload->after, //commit hash
            $last_commit_message, //The last commit message in the push
            $number_of_commits, //number of commits in the push
            $number_of_errors, //number of errors encountered during the deployment
            $encoded_payload, //the payload, encoded in json
            $_SERVER["REMOTE_ADDR"] //ip address of client
        ); 

        //Executing the statement
        $deployment->execute() or die("Error: ".$deployment->error);                
                
        //make query
        $query = "INSERT INTO deployment_errors (deployment_id, function_name, error_msg) VALUES (?, ?, ?)";

        //prepare query statement
        $addErrors = $connection->prep_stmt($query);  
            
        //bind parameters
        $addErrors->bind_param("iss", $deployment_id, $name, $error);                
        
        //set variables
        $deployment_id = $deployment->insert_id;        
                                
        foreach($this->check->getChecks() as $check){
            if($check["status"] == 0){        
	            $name = $check["name"];
                $error = $check["error"];
                
                //Executing the statement
                $addErrors->execute() or die("Error: ".$addErrors->error);                        

            }
        }                  
    }        
}    
?>
