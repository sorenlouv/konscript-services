<?php

include("../inc/Check.class.php");
include("../inc/header.php");

if(!isset($_GET["id"]) || empty($_GET["id"]) || !is_dir($web_root.$_GET["id"])){    
	header("Location: index.php");
	exit();
}
                 
$check = new Check();
$check->setProjectId($_GET["id"]);

$check->checkProject($check->getPathToDev());
$check->checkProject($check->getPathToNewestVersion());    
$check->checkGithub();
$check->checkVhostApache();
$check->checkVhostNginx();
$check->checkRestart();

// update status
$connection = New DbConn();
$connection->connect();

$number_of_errors = $check->getNumberOfErrors();	
$updateProject = $connection->prep_stmt("UPDATE projects SET errors=? WHERE id=?");  	   
$updateProject->bind_param("is", $number_of_errors, $_GET["id"]);    	
$updateProject->execute() or die("Error: ".$result->error);                        
    
?>
<table>
    <?php 
    $tips = array();
    foreach($check->getChecks() as $id=>$check): 
		$class = $check["status"] == true ? "success" : "error";    
		
		//add tips to array
		if(!empty($check["tip"])){
		    $tips[$id] = $check["tip"];
		}    
		?>

		<tr class="<?php echo $class ?>" rel="<?php echo $id; ?>"><td><?php echo $check[$class]; ?></td></tr>
    <?php endforeach; ?>
</table>

<div id="showTip">
<h2>Tips</h2>
<?php foreach($tips as $id=>$tip):
    echo '<div id="tip_'.$id.'">'.$tip.'</div>';
endforeach; ?>
</div>
