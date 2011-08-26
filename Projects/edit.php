<?php
include("../inc/conn.inc.php");
include("../inc/util.php");
include("../inc/header.php");

$connection = New DbConn();
$connection->connect();	

$project_id = $_GET["id"];
$project = $connection->prep_stmt("SELECT * FROM projects WHERE id=?"); //prepare query statement
$project->bind_param("s", $project_id);			//bind parameters
$project->execute() or die("Error: ".$project->error); //Executing the statement
$project->bind_result($id, $title, $primary_domain, $additional_domains, $dev_domain, $use_cache, $current_version, $screenshot, $exclude, $errors);
$project->fetch();
$project->close();

if($_POST){

	// update screenshot - if one of the domains was changed
	// "1" is a flag, that indicates the screenshot must be updated
	$_POST["screenshot"] = ($primary_domain != $_POST["primary_domain"] || $dev_domain != $_POST["dev_domain"]) ? 1 : 0;
	
	// update symlink - if the version was changed
	if(isset($_POST["current_version"]) && $current_version!=$_POST["current_version"]){
		$target = $web_root.$_POST["id"]."/prod/".$_POST["current_version"];
		$link = $web_root.$_POST["id"]."/prod/current";
		unlink($link);
		symlink($target, $link);
	}
	
	// format additional domains - remove http:// and www.
	$strip = array("http://", "www.");				
	$_POST["additional_domains"] = str_replace($strip, "", $_POST["additional_domains"]);		
	$_POST["additional_domains"] = str_replace(",", " ", $_POST["additional_domains"]);	
	$_POST["additional_domains"] = str_replace("  ", " ", $_POST["additional_domains"]);		
	
	$_POST["primary_domain"] = str_replace($strip, "", $_POST["primary_domain"]);	
	$_POST["dev_domain"] = str_replace($strip, "", $_POST["dev_domain"]);		
	
	// update vhost for Nginx	
	$nginx = array();	
	$nginx[] = array(vhost_nginx_additional($primary_domain, $additional_domains), vhost_nginx_additional($_POST["primary_domain"], $_POST["additional_domains"]));		// additional	
	$nginx[] = array(vhost_nginx_rewrite($primary_domain), vhost_nginx_rewrite($_POST["primary_domain"])	);	// rewrite
	$nginx[] = array(vhost_nginx_primary($primary_domain), vhost_nginx_primary($_POST["primary_domain"])	); // primary
	$nginx[] = array(vhost_nginx_dev($dev_domain), vhost_nginx_dev($_POST["dev_domain"])); // dev

	$vhost_nginx_filename = "/etc/nginx/sites-available/".$project_id;			
	$vhost_nginx_content = update_vhost($vhost_nginx_filename, $nginx);		
	file_put_contents($vhost_nginx_filename, $vhost_nginx_content);
	
	// update vhost for Apache	
	$apache = array();		
	$apache[] = array(vhost_apache_primary($primary_domain), vhost_apache_primary($_POST["primary_domain"])	); // primary
	$apache[] = array(vhost_apache_dev($dev_domain), vhost_apache_dev($_POST["dev_domain"])); //Dev

	$vhost_apache_filename = "/etc/apache2/sites-available/".$project_id;			
	$vhost_apache_content = update_vhost($vhost_apache_filename, $apache);		 
	file_put_contents($vhost_apache_filename, $vhost_apache_content);	
	
	// update cache in vhost
	if($use_cache != $_POST["use_cache"]){		
		$res = $_POST["use_cache"]==0 ? updateCache($project_id, "remove") : $res = updateCache($project_id, "add");
		if($res === false){
			//something went wrong!
		}
	}
	

	// update db           
	$update_project = $connection->prep_stmt("UPDATE projects SET title=?, primary_domain=?, additional_domains=?, dev_domain=?, use_cache=?, current_version=?, screenshot=? WHERE id=?");          
	$update_project->bind_param("sssssiis", 
		$_POST["title"], $_POST["primary_domain"], $_POST["additional_domains"], $_POST["dev_domain"], $_POST["use_cache"], $_POST["current_version"], $_POST["screenshot"], $_POST["id"]);        
	$update_project->execute() or die("Error: ".$update_project->error);       

	// refresh page             
	header("Location: ".$_SERVER['PHP_SELF']."?id=".$_GET['id']."&p=".time());
}

?>

<div class="projectEdit">
	<form action="<?=$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>" method="post">
		<input type="hidden" value="<?php echo $id; ?>" name="id">
		
		<div class="field clearfix">
			<label for="title">Title:</label>
			<input type="text" value="<?php echo $title; ?>" name="title" id="title">
		</div>

		<div class="field clearfix">
			<label for="primary_domain">Primary domain:</label>			
			<input type="text" value="<?php echo $primary_domain; ?>" name="primary_domain" id="primary_domain">
		</div>

		<div class="field clearfix">			
			<label for="additional_domains">Additional domains:</label>								
			<input type="text" value="<?php echo $additional_domains; ?>" name="additional_domains" id="additional_domains">
		</div>

		<div class="field clearfix">
			<label for="dev_domain">Development domain:</label>					
			<input type="text" value="<?php echo $dev_domain; ?>" name="dev_domain" id="dev_domain">
		</div>

		<div class="field clearfix">
			<label for="use_cache">Use cache:</label>	
			<select name="use_cache" id="use_cache">	
				<?php
				$cache_values = array(0, 1);
				foreach($cache_values as $cache_value):
					$selected = $use_cache == $cache_value ? " selected" : "";
					?>
					<option <?php echo $selected; ?>><?php echo $cache_value; ?></option>
				<?php endforeach; ?>	
			</select>		
		</div>		
		
		
		<div class="field clearfix">
			<label for="current_version">Version:</label>									
			<select name="current_version" id="current_version">
				<?php
				$versions = get_list_of_folders($web_root.$id."/prod");
				foreach($versions as $version):
					$selected = $current_version == $version ? " selected" : "";
					?>
					<option <?php echo $selected; ?>><?php echo $version; ?></option>
				<?php endforeach; ?>					
			</select>		
		</div>		
		<input type="submit">
	</form>
</div>
