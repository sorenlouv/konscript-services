<?php
include("../inc/conn.inc.php");
include("../inc/util.php");

$connection = New DbConn();
$connection->connect();

/**
 * fetch project list
 **/

// get projects stored in database
$result = $connection->query("SELECT id FROM projects");
while($projects = $result->fetch_assoc()){
	$db_projects[] = $projects["id"];
}

// get projects stored in physical folders
$folder_projects = get_list_of_folders($web_root);

// get difference
$diff = array_diff($folder_projects, $db_projects);

// continue if new projects was found
if(count($diff) == 0){
	exit();
}

/**
 * update project list
 **/
 
if($_POST){
	//prepare query statement
	$addProject = $connection->prep_stmt("INSERT INTO projects (id, exclude) VALUES (?, ?)");  
		
	//bind parameters
	$addProject->bind_param("si", $project_id, $exclude);                
	 
	//Executing the statement                                
	foreach($diff as $project_id){
			$formatted_project_id = str_replace(".", "_", $project_id);
		    $exclude = $_POST[$formatted_project_id."_exclude"];            
		    $addProject->execute() or die("Error: ".$addProject->error);                        
	} 
	header("Location: index.php");
}


/**
 * output project list
 **/
$output = '
<form action="batchAdd.php" method="POST">
<table id="newProjects">
<thead><td class="projectId">&nbsp;</td> <td class="radio">Add</td> <td class="radio">Ignore</td></thead>';

foreach($diff as $project_id){
	$output .=' <tr>
		<td class="projectId">'.$project_id.'</td>
		<td class="radio"><input checked type="radio" name="'.$project_id.'_exclude" value="0" /></td>
		<td class="radio"><input type="radio" name="'.$project_id.'_exclude" value="1" /></td>
	</tr>';	
}
$output .= '</table><input type="submit" value="Save" /></form>';
echo $output;
?>
