<?php
include("inc/Check.class.php");

/**
 * Deploy project to prod from interface. Either to an existing version or clone and deploy to a new version
 ***********/

class Deploy{		
	var $check;

    function Deploy($project_id, $deploy_type){        
    	// initialize check
    	$check = new Check();
    	        
		// set variables  
        $this->check = $check;
	    $check->setProjectId($project_id);    	        
        $check->setStageFolder("prod");    
            
	    // validators                                                    
        $check->checkGitRemote();  
        $check->checkFolderMustExist();
        $check->checkFolderMustExist("/.git");        
		$check->checkFolderWritable();       
		$check->checkFolderWritable("/.git");		
		$check->checkRestart();
				
		// intial validators passed
		if ($check->getNumberOfErrors() == 0){
			$path = $check->web_root.$project_id."/prod/";
			$current_version = get_latest_prod_version($path);
		
			// deploy to new version
			if($deploy_type == "new"){
				$next_version = $current_version + 1;
		
				// validate that the next version doesn't exist
				$check->checkFolderCannotExist($path.$next_version);    				          
				if($check->getNumberOfErrors() == 0){
					recursive_copy($path.$current_version, $path.$next_version);            
					
					// change current symlink
					$pathToSymlink = $path."current";
					unlink($pathToSymlink);
					symlink($path.$next_version, $pathToSymlink);  					
					
					//update version in db
					$connection = New DbConn();
					$connection->connect();						
					$update_project = $connection->prep_stmt("UPDATE projects SET current_version=? WHERE id=?");          
					$update_project->bind_param("is",$next_version, $project_id);        
					$update_project->execute() or die("Error: ".$update_project->error);   					
				}
			
				$version = $next_version;
			
			// deploy to existing version
			}else{
				$version = $current_version;
			}
			
			// clear cache for current project
			$check->clearCache();
			
			// final validators passed
			if($check->getNumberOfErrors() == 0){						
				$git_response = Git::git_callback('pull konscript master', $path.$version, true);
				$check->checkGitPull($git_response);  
			}
			                                        
		}	
								
		echo $check->outputResult();		       				                

    }                                       
}    
?>
