<?php
include("../inc/Check.class.php");
include("../inc/header.php");

    // save project
    if($_POST){
    
        // init check
		$check = new Check();
        $check->setProjectId($_POST["project_id"]);    
        
		$_POST["dev_domain"] = $check->getDefaultDevDomain();			
        $full_path_to_project = $web_root.$_POST["project_id"];     

		// mysql connection
		$connection = New DbConn();
		$connection->connect();

        // Validations
        $check->folderCannotExist($full_path_to_project); // folder cannot exist
		$check->checkGithub(); // github project must exist
		$check->notEmpty($_POST["primary_domain"]);
		$check->notEmpty($_POST["project_id"]);		

		if ($check->getNumberOfErrors() == 0){        
				// create project root - 02770: leading zero is required; 2 is the sticky bit (set guid); 770 is rwx,rwx,---
				mkdir($full_path_to_project, 02770);

				// create dev
				$full_path_to_dev = $full_path_to_project."/dev";
				mkdir($full_path_to_dev, 02770);
				$repo = Git::create($full_path_to_dev); //git init        
				$repo->run('remote add konscript '. $check->getPathToGitRemote());
				
				// wordpress: download and extract latest version
				wp_get_latest($_POST["project_id"], isset($_POST["wordpress"]));            
				
				// create prod: clone dev to prod
				$full_path_to_prod = $full_path_to_project."/prod";
				mkdir($full_path_to_prod, 02770);
				recursive_copy($full_path_to_dev, $full_path_to_prod."/1"); 				
		
				// add symlink
				$target = $full_path_to_prod."/1";
				$link = $full_path_to_prod."/current";
				symlink($target, $link);			

				// create prod database
				$connection->query("CREATE DATABASE IF NOT EXISTS `".$_POST["project_id"]."-prod`");
			
				// create dev database
				$connection->query("CREATE DATABASE IF NOT EXISTS `".$_POST["project_id"]."-dev`");
						 						
				// add vhost for Nginx
				$vhost_nginx_filename = "/etc/nginx/sites-available/".$_POST["project_id"];
				$vhost_nginx_content = vhost_nginx($_POST["project_id"], $_POST["primary_domain"], $_POST["dev_domain"], $_POST["additional_domains"]);		
				$check->writeToFile($vhost_nginx_filename, $vhost_nginx_content);
				
				//add symlink for nginx
				$nginx_symlink = "/etc/nginx/sites-enabled/".$_POST["project_id"];
				if(is_link($nginx_symlink)){	unlink($nginx_symlink);		}
				symlink($vhost_nginx_filename, $nginx_symlink);

				// add vhost for Apache
				$vhost_apache_filename = "/etc/apache2/sites-available/".$_POST["project_id"];
				$vhost_apache_content = vhost_apache($_POST["project_id"], $_POST["primary_domain"], $_POST["dev_domain"]);
				$check->writeToFile($vhost_apache_filename, $vhost_apache_content);
				
				//add symlink for Apache
				$apache_symlink = "/etc/apache2/sites-enabled/".$_POST["project_id"];
				if(is_link($apache_symlink)){ unlink($apache_symlink); }
				symlink($vhost_apache_filename, $apache_symlink);			
        }
        
        // on success
		if ($check->getNumberOfErrors() == 0){        

			// format additional domains - remove "http://", "www." and spaces 
			$strip = array("http://", "www.");	
			$_POST["additional_domains"] = str_replace($strip, "", $_POST["additional_domains"]);		
			$_POST["additional_domains"] = str_replace(",", " ", $_POST["additional_domains"]);
			$_POST["additional_domains"] = str_replace("  ", " ", $_POST["additional_domains"]);					
			$_POST["primary_domain"] = str_replace($strip, "", $_POST["primary_domain"]);	
			$_POST["use_cache"] = 0;
			$_POST["screenshot"] = 1;
			$_POST["current_version"] = 1;			
 						
			// prepare and execute
			$create_project = $connection->prep_stmt("INSERT INTO projects (id, title, primary_domain, additional_domains, dev_domain, screenshot, current_version, use_cache, created, modified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '".time()."', '".time()."')");     
			$create_project->bind_param("sssssiii", 
			$_POST["project_id"], $_POST["title"], $_POST["primary_domain"], $_POST["additional_domains"], $_POST["dev_domain"], $_POST["screenshot"], $_POST["current_version"], $_POST["use_cache"]);        
			$create_project->execute() or die("Error: ".$create_project->error);     		
		
			header("Location: /Projects/check.php?id=".$_POST["project_id"]);                  
		}else{
			echo $check->outputResult();
		}
    }   
?>

<div class="projectEdit">
	<form action="<?=$_SERVER['PHP_SELF']; ?>" method="post">
		<div class="field clearfix">
			<label for="project_id">Project id:</label>
			<input type="text" name="project_id" id="project_id"> *
		</div>
		
		<div class="field clearfix">
			<label for="title">Title:</label>
			<input type="text" name="title" id="title">
		</div>

		<div class="field clearfix">
			<label for="primary_domain">Primary domain:</label>			
			<input type="text" name="primary_domain" id="primary_domain"> *
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
