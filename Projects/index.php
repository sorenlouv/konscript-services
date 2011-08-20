<?php
include("../inc/conn.inc.php");
include("../inc/util.php");
include("../inc/header.php");

$connection = New DbConn();
$connection->connect();

// get projects stored in database
$result = $connection->query("SELECT * FROM projects WHERE exclude='0'");
while($projects = $result->fetch_assoc()){
	$screenshot = $projects["screenshot"] == 2 ? "img/screenshots/".$projects["id"].".jpg" : "img/no-image.jpg";	
	$status = $projects["errors"]==0 ? "success" : "error";
?>
	<div class="projectOverview">
		<div class="status"><a href="check.php?id=<?php echo $projects["id"]; ?>" class="<?php echo $status; ?>">&nbsp;</a></div>
		<img height="150" src="/<?php echo $screenshot; ?>" alt=""/>
		<div class="info">
			<p class="title"><?php echo $projects["title"]; ?></p>
			<a href="http://<?php echo $projects["prod_address"]; ?>" class="url"><?php echo $projects["prod_address"]; ?></a>
			<a  href="http://<?php echo $projects["prod_address"]; ?>" class="url"><?php echo $projects["dev_address"]; ?></a>
			<div class="toolbar">
				<p class="version">Version: <?php echo $projects["current_version"]; ?></p>
				<a href="/Projects/cron.php?errorStatus=1&screenshot=1&id=<?php echo $projects["id"]; ?>">Update</a>
				<a href="/Projects/edit.php?id=<?php echo $projects["id"]; ?>">Edit</a>
			</div>
		</div>
	</div>
	<div class="clear"></div>
<?php
}


/**
 * download zip file
 */
if(isset($_GET["projectToZip"]) && isset($_GET["branch"])){
	downloadZip($_GET["projectToZip"], $_GET["branch"]);	
}

?>
