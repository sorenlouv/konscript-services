<?php
include("inc/Check.class.php");

/**
 * Deploy project to prod from interface. Either to an existing version or clone and deploy to a new version
 ***********/

class Deploy{		
	var $check;

    function Deploy($project_name, $deploy_type){        
    	// initialize check
    	$check = new Check();
    	        
		// set variables  
        $this->check = $check;
	    $check->setProjectId($project_name);    	        
        $check->setStageFolder("prod");    
            
	    // validators                                                    
        $check->checkGitRemote();  
        $check->checkFolderMustExist();
        $check->checkFolderMustExist("/.git");        
		$check->checkFolderWritable();       
		$check->checkFolderWritable("/.git");		
				
		// intial validators passed
		if ($check->getNumberOfErrors() == 0){
			$path = $check->root.$project_name."/prod/";
			$current_version = get_latest_prod_version($path);
		
			// deploy to new version
			if($deploy_type == "new"){
				$next_version = $current_version + 1;
		
				// validate that the next version doesn't exist
				$check->checkFolderCannotExist($path.$next_version);    				          
				if($check->getNumberOfErrors() == 0){
					recursive_copy($path.$current_version, $path.$next_version);            
					
					// change current symlink
					$pathToCurrent = $path."current";
					unlink($pathToCurrent);
					symlink($path.$next_version, $pathToCurrent);  					
				}
			
				$version = $next_version;
			
			// deploy to existing version
			}else{
				$version = $current_version;
			}
			
			// clear cache for current project and for global
			$check->clearCache();
			$check->clearCache("global");			
			
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
