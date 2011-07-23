<?php
include("inc/Check.class.php");
include("inc/Project.class.php"); 
                  
if(!isset($_GET["projectName"]) || empty($_GET["projectName"]) || !is_dir("/srv/www/".$_GET["projectName"])){    
	header("Location: listProjects.php");
	exit();
}

$check = new Check();
$check_prod = new Project($_GET["projectName"], "prod", $check);
$check_dev = new Project($_GET["projectName"], "dev", $check);    

//General validations
$check->checkGithub();		   //check GitHub repository 	      	                       	
$check_prod->checkHooks(); //Check post-receive hooks		            		   
$check_prod->checkVirtualHost();  //Check virtual hosts                            
    
include("inc/header.php")
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

