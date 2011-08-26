<?php
include("../inc/Check.class.php");
include("../inc/header.php");
                     
    // save project
    if($_POST){
        
        $project_id = $_POST["project_id"];
        $full_path_to_project = $web_root.$project_id;
        
        // init check
		$check = new Check();
        $check->setProjectId($project_id);
		        
        // Validations
        $check->checkFolderCannotExist($full_path_to_project); // folder cannot exist
		$check->checkGithub(); // github project must exist
	  
		if ($check->getNumberOfErrors() == 0){        

            // create project root - 02770: leading zero is required; 2 is the sticky bit (set guid); 770 is rwx,rwx,---
            mkdir($full_path_to_project, 02770);
            
            // create dev
            $full_path_to_dev = $full_path_to_project."/dev";
            mkdir($full_path_to_dev, 02770);
            $repo = Git::create($full_path_to_dev); //git init        
            $repo->run('remote add konscript '. $check->getPathToGitRemote());
            
            // wordpress: download and extract latest version
            wp_get_latest($project_id, $_POST["wordpress"]);            
            
            // create prod: clone dev to prod
            $full_path_to_prod = $full_path_to_project."/prod";
            mkdir($full_path_to_prod, 02770);
			recursive_copy($full_path_to_dev, $full_path_to_prod."/1"); 
			
			// add symlink
			$target = $full_path_to_prod."/1";
			$link = $full_path_to_prod."/current";
			symlink($target, $link);			

			// create databases
			$connection = New DbConn();
			$connection->connect();
		
		    // create prod database
			$connection->query("CREATE DATABASE IF NOT EXISTS `".$project_id."-prod`");
		    
		    // create dev database
			$connection->query("CREATE DATABASE IF NOT EXISTS `".$project_id."-dev`");
			
			// add project to db
			$update_project = $connection->prep_stmt("INSERT INTO projects (id, title, primary_domain, additional_domains, dev_domain, screenshot, current_version) VALUES (?, ?, ?, ?, ?, ?, ?)");          						
			
			// format additional domains - remove "http://", "www." and spaces 
			$strip = array("http://", "www.");
			
			// set variables	
			$_POST["additional_domains"] = str_replace($strip, "", $_POST["additional_domains"]);		
			$_POST["additional_domains"] = str_replace(",", " ", $_POST["additional_domains"]);
			$_POST["additional_domains"] = str_replace("  ", " ", $_POST["additional_domains"]);					
			$_POST["primary_domain"] = str_replace($strip, "", $_POST["primary_domain"]);	
			$_POST["dev_domain"] = $check->getDefaultDevDomain();			
			$_POST["screenshot"] = 1;
			$_POST["current_version"] = 1;
			
			// prepare and execute
			$update_project->bind_param("sssssii", 
			$project_id, $_POST["title"], $_POST["primary_domain"], $_POST["additional_domains"], $_POST["dev_domain"], $_POST["screenshot"], $_POST["current_version"]);        
			$update_project->execute() or die("Error: ".$update_project->error);     		
             							
			// add vhost for Nginx
			$vhost_nginx_filename = "/etc/nginx/sites-available/".$project_id;
			$vhost_nginx_content = vhost_nginx($project_id, $_POST["primary_domain"], $_POST["dev_domain"], $_POST["additional_domains"]);		
			file_put_contents($vhost_nginx_filename, $vhost_nginx_content);

			// add vhost for Apache
			$vhost_apache_filename = "/etc/apache2/sites-available/".$project_id;
			$vhost_apache_content = vhost_apache($project_id, $_POST["primary_domain"], $_POST["dev_domain"]);
			file_put_contents($vhost_apache_filename, $vhost_apache_content);
                        
            header("Location: /Projects/check.php?id=".$project_id);            
        }
        
		echo $check->outputResult();
    }   
?>

<div class="projectEdit">
	<form action="<?=$_SERVER['PHP_SELF']; ?>" method="post">
		<div class="field clearfix">
			<label for="project_id">Project id:</label>
			<input type="text" name="project_id" id="project_id">
		</div>
		
		<div class="field clearfix">
			<label for="title">Title:</label>
			<input type="text" name="title" id="title">
		</div>

		<div class="field clearfix">
			<label for="primary_domain">Primary domain:</label>			
			<input type="text" name="primary_domain" id="primary_domain">
		</div>

		<div class="field clearfix">			
			<label for="additional_domains">Additional domains:</label>								
			<input type="text" name="additional_domains" id="additional_domains">
		</div>

		<div class="field clearfix">
			<label for="wordpress">Download and install wordpress:</label>							
			<input type="checkbox" name="wordpress" value="true" id="wordpress" />
		</div>
				
		<input type="submit">
	</form>
</div>
