<?php

include("../inc/Check.class.php");
include("../inc/Project.class.php"); 
include("../inc/header.php");

if(!isset($_GET["id"]) || empty($_GET["id"]) || !is_dir($web_root.$_GET["id"])){    
	header("Location: index.php");
	exit();
}

                 
$check = new Check();
$check_prod = new Project($_GET["id"], "prod", $check);
$check_dev = new Project($_GET["id"], "dev", $check);    

// General validations - only do these once (and not for twice for both dev and prod)
$check->checkGithub();		   //check GitHub repository 	      	                       	
$check_prod->checkVirtualHost();  //Check virtual hosts                            
    
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

