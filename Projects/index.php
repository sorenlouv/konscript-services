<?php
include("../inc/Check.class.php");
include("../inc/header.php");

/**
 * download zip file
 */
if(isset($_GET["id"]) && isset($_GET["branch"])){
	downloadZip($_GET["id"], $_GET["branch"]);	
}

/**
 * clear cache
 */
if(isset($_GET["id"]) && isset($_GET["clearCache"])){
	$check = new Check();
	$check->clearCache($_GET["id"]);
	
	if($check->getNumberOfErrors()>0){
		$check->clearCache("global");
		echo"Global cache cleared!";
	}else{
		echo "Cache cleared!";
	}
}

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
		<img class="screenshot" src="/<?php echo $screenshot; ?>" alt=""/>
		<div class="info">
			<p class="title"><?php echo $projects["title"]; ?></p>
			<p class="url">
				<a href="?id=<?php echo $projects["id"]; ?>&branch=prod"><img src="/img/zip.png"></a>
				<a href="http://<?php echo $projects["primary_domain"]; ?>"><?php echo $projects["primary_domain"]; ?></a>
			</p>
			<p class="url">
				<a href="?id=<?php echo $projects["id"]; ?>&branch=dev"><img src="/img/zip.png"></a>
				<a href="http://<?php echo $projects["dev_domain"]; ?>"><?php echo $projects["dev_domain"]; ?></a>
			</p>
			<div class="toolbar">
				<p class="version">Version: <?php echo $projects["current_version"]; ?></p>
				<?php if($projects["use_cache"]==1):?>							
				<a href="/Projects/index.php?clearCache=1&id=<?php echo $projects["id"]; ?>">Clear cache</a>
				<?php endif; ?>
				<a href="/Projects/cron.php?errorStatus=1&screenshot=1&id=<?php echo $projects["id"]; ?>">Update</a>
				<a href="/Projects/edit.php?id=<?php echo $projects["id"]; ?>">Edit</a>
			</div>
		</div>
	</div>
	<div class="clear"></div>
<?php
}

?>
