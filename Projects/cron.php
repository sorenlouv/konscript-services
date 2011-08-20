<?php
include("../inc/Check.class.php");
include("../inc/Project.class.php");

$connection = New DbConn();
$connection->connect();

$json = array();

/**
 * update error status
 */
if(isset($_GET["errorStatus"])){
	$result = $connection->query("SELECT id, errors FROM projects WHERE exclude='0'");
	while($project = $result->fetch_assoc()){
	
		// get status
		$check = new Check();
		$check_prod = new Project($project["id"], "prod", $check);
		$check_dev = new Project($project["id"], "dev", $check);    
		$check->checkGithub();	      	                       	
		$check_prod->checkVirtualHost();
		$number_of_errors = $check->getNumberOfErrors();	

		// update status	
		if($project["errors"] != $number_of_errors){
			$json["errorStatus"][] = $project["id"];
			$updateProject = $connection->prep_stmt("UPDATE projects SET errors=? WHERE id=?");  	   
			$updateProject->bind_param("is", $number_of_errors, $project["id"]);    	
			$updateProject->execute() or die("Error: ".$result->error);                        
		}		
	}
}

/**
 * update pending screenshots 
 */
if(isset($_GET["screenshot"])){
	$pending_screenshots = $connection->query("SELECT id, prod_address, dev_address FROM projects WHERE exclude='0' && screenshot='1'");
	while($project = $pending_screenshots->fetch_assoc()){
		$json["screenshot"][] = $project["id"];
	
		// update screenshot
		$hostnames = array($project["prod_address"], $project["dev_address"]);
		$shell_return = update_screenshot($hostnames, $project["id"]);	
	
		// update db
		$updateProject = $connection->prep_stmt("UPDATE projects SET screenshot='2' WHERE id=?");  	   
		$updateProject->bind_param("s", $project["id"]);    	
		$updateProject->execute() or die("Error: ".$pending_screenshots->error);                        	
	}
} 

// output changes
echo json_encode($json);	
?>
