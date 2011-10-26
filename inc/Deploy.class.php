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
    	
    	// connect to db
    	$mysql = new Mysql();
    	        
		// set variables  
        $this->check = $check;
	    $check->setProjectId($project_id);    	        
            
	    // validators                                                    
		$check->checkProject($check->getPathToNewestVersion());		
		$check->checkRestart();
				
		// intial validators passed
		if ($check->getNumberOfErrors() == 0){
			$path = $check->web_root.$project_id."/prod/";
			$current_version = get_latest_prod_version($path);
		
			// deploy to new version
			if($deploy_type == "new"){
				$next_version = $current_version + 1;
		
				// validate that the next version doesn't exist
				$check->folderCannotExist($path.$next_version);    				          
				if($check->getNumberOfErrors() == 0){
					recursive_copy($path.$current_version, $path.$next_version);            
					
					// update version in symlink
					$pathToSymlink = $path."current";
					unlink($pathToSymlink);
					symlink($path.$next_version, $pathToSymlink);  					
					
					//update version in db
					$mysql->updateProjectVersion($next_version, $project_id);
				}
			
				$version = $next_version;
			
			// deploy to existing version
			}else{
				$version = $current_version;
			}
			
			// clear cache for current project (only if cache is enabled!)			
			if($mysql->useCache($project_id) === true){
				$check->clearCache();
			}
			
			// final validators passed
			if($check->getNumberOfErrors() == 0){						
				//$git_response = Git::git_callback('pull konscript master', $path.$version, true);
				//$check->checkGitPull($git_response);  
			}
			                                        
		}	
								
		echo $check->outputResult("Deployment was successful");		       				                

    }                                       
}    
?>
