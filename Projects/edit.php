<?php
include("../inc/conn.inc.php");
include("../inc/util.php");
include("../inc/header.php");

$connection = New DbConn();
$connection->connect();	

if($_POST){

	// update screenshot
	if($_POST["prod_address_check"]!=$_POST["prod_address"] || $_POST["dev_address_check"]!=$_POST["dev_address"]){	
		$screenshot = 1;
	}else{
		$screenshot = 0;	
	}
	
	// update symlink
	if(isset($_POST["current_version"]) && $_POST["current_version_check"]!=$_POST["current_version"]){
		$target = $web_root.$_POST["id"]."/prod/".$_POST["current_version"];
		$link = $web_root.$_POST["id"]."/prod/current";
		unlink($link);
		symlink($target, $link);
	}
	
	// update db           
    $update_project = $connection->prep_stmt("UPDATE projects SET title=?, prod_address=?, dev_address=?, current_version=?, screenshot=? WHERE id=?");          
    $update_project->bind_param("ssssis", $_POST["title"], $_POST["prod_address"], $_POST["dev_address"], $_POST["current_version"], $screenshot, $_POST["id"]);        
    $update_project->execute() or die("Error: ".$update_project->error);                    
	
}
     
$project = $connection->prep_stmt("SELECT * FROM projects WHERE id=?"); //prepare query statement
$project->bind_param("s", $project_id);			//bind parameters
$project_id = $_GET["id"]; 						//set variables
$project->execute() or die("Error: ".$project->error); //Executing the statement
$project->bind_result($id, $title, $prod_address, $dev_address, $current_version, $screenshot, $exclude, $errors); //bind result variables

// fetch values
while ($project->fetch()) {
?>

	<div class="projectEdit">
		<form action="<?=$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>" method="post">
			<input type="hidden" value="<?php echo $id; ?>" name="id">
			<input type="text" value="<?php echo $title; ?>" name="title"><br>
		
			http://<input type="text" value="<?php echo $prod_address; ?>" name="prod_address"><br>
			<input type="hidden" value="<?php echo $prod_address; ?>" name="prod_address_check">
		
			http://<input type="text" value="<?php echo $dev_address; ?>" name="dev_address"><br>
			<input type="hidden" value="<?php echo $dev_address; ?>" name="dev_address_check">		
				
			Version: <select name="current_version">
				<?php
				$versions = get_list_of_folders($web_root.$id."/prod");
				foreach($versions as $version):
					$selected = $current_version == $version ? " selected" : "";
					?>
					<option <?php echo $selected; ?>><?php echo $version; ?></option>
				<?php endforeach; ?>					
			</select>		
			<input type="hidden" value="<?php echo $current_version; ?>" name="current_version_check"><br>		
		<input type="submit">
	</form>

	</div>


<?php
}

/* close statement */
$project->close();

?>


