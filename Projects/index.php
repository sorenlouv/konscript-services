<?php
include("../inc/Check.class.php");
include("../inc/Receiver.class.php");
include("../inc/header.php");

$connection = New DbConn();
$connection->connect();

/**
 * download zip file
 */
if(isset($_GET["downloadProject"]) && isset($_GET["branch"])){
	downloadZip($_GET["downloadProject"], $_GET["branch"]);	
}

/**
 * clear cache
 */
if(isset($_GET["clearCache"])){
	$check = new Check();
	$check->setProjectId($_GET["clearCache"]);
	$check->clearCache();	
	echo $check->outputResult("Cache was cleared for ".$_GET["clearCache"]);		
}

/**
 * Delete project
 */
if(isset($_GET["deleteProject"])){
	$project = $connection->prep_stmt("DELETE FROM projects WHERE id=?");	
	$project->bind_param("s", $_GET["deleteProject"]);
	$project->execute() or die("Error: ".$project->error);
	
	if($project->affected_rows > 0){
		unlink("/etc/apache2/sites-available/".$_GET["deleteProject"]);
		unlink("/etc/apache2/sites-enabled/".$_GET["deleteProject"]);
		unlink("/etc/nginx/sites-available/".$_GET["deleteProject"]);
		unlink("/etc/nginx/sites-enabled/".$_GET["deleteProject"]);			
		echo "Deleted vhosts!";
	}
}

/**
 * make test pull
 */
if(isset($_GET["testPull"])){
	$check = new Check();
	$check->setProjectId($_GET["testPull"]);
	$check->testPull();	
	echo $check->outputResult("Test pull was successsful!");		       				                
}

// get projects stored in database
$result = $connection->query("SELECT * FROM projects WHERE exclude='0'");
while($projects = $result->fetch_assoc()){
	$screenshot = $projects["screenshot"] == 2 ? "img/screenshots/".$projects["id"].".jpg" : "img/no-image.jpg";	
	$status = $projects["errors"]==0 ? "success" : "error";
?>
	<div class="projectOverview">
		<div class="status"><a href="check.php?id=<?php echo $projects["id"]; ?>" class="<?php echo $status; ?>">&nbsp;</a></div>
		<img class="screenshot" src="/<?php echo $screenshot; ?>" alt=""/>
		<div class="info">
			<p class="title"><?php echo $projects["title"]; ?></p>
			<p class="url">
				<a href="?downloadProject=<?php echo $projects["id"]; ?>&branch=prod"><img src="/img/zip.png"></a>
				<a href="http://<?php echo $projects["primary_domain"]; ?>"><?php echo $projects["primary_domain"]; ?></a>
			</p>
			<p class="url">
				<a href="?downloadProject=<?php echo $projects["id"]; ?>&branch=dev"><img src="/img/zip.png"></a>
				<a href="http://<?php echo $projects["dev_domain"]; ?>"><?php echo $projects["dev_domain"]; ?></a>
			</p>
			<div class="toolbar">
				<p class="version">Version: <?php echo $projects["current_version"]; ?></p>
				<?php if($projects["use_cache"]==1):?>							
					<a href="/Projects/index.php?clearCache=<?php echo $projects["id"]; ?>">Clear cache</a>
				<?php endif; ?>
				<a href="?testPull=<?php echo $projects["id"]; ?>">Test pull</a>				
				<a href="/Projects/edit.php?id=<?php echo $projects["id"]; ?>">Edit</a>
				<a class="delete" href="/Projects/index.php?deleteProject=<?php echo $projects["id"]; ?>">Delete</a>								
			</div>
		</div>
	</div>
	<div class="clear"></div>
<?php
}

?>
