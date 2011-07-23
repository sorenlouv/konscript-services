<?php
include("inc/util.php"); 

$projects = getProjects("/srv/www/");   
echo '<table width="300px"><tr><td>Prod</td><td>Dev</td><td>Check for errors</td></tr>'; 
foreach($projects as $project){
	echo '<tr>
	<td><a href="listProjects.php?projectToZip='.$project.'&branch=prod"><img height="20" src="img/zip.png" title="Download as zip"></a></td>
	<td><a href="listProjects.php?projectToZip='.$project.'&branch=dev"><img height="20" src="img/zip.png" title="Download as zip"></a></td>
	<td><a href="check.php?projectName='.$project.'">'.$project.'</a></td>
	</tr>';
}   
echo "</table>"; 

//echo getTempLink();

/**
 * download zip file
 */
if(isset($_GET["projectToZip"]) && isset($_GET["branch"])){

	$project_name = $_GET["projectToZip"];

	// download production version
	if($_GET["branch"]=="prod"){
		$pathToProd = "/srv/www/".$project_name."/prod/";
		$latest = get_latest_prod_version($pathToProd);
		$path = $project_name.'/prod/'.$latest; //no trailing slash!
		$dbname = $project_name.'-prod';
		
	// download development version		
	}else{
		$path = $project_name."/dev"; //no trailing slash!
		$dbname = $project_name.'-dev';
	}	
	
	// create files
	$command = "./bash/clone_project.sh $project_name $path $dbname";
	exec($command, $output, $return_code);	
	
	if($return_code != 0){
			echo "return code: ".$return_code."<br>";
			echo "command: ".$command."<br>";
			echo "<pre>";
			print_r( $output );
			echo "</pre>";
	}else{	
		header("Location: ./temp/".$project_name.".tar");
		//downloadTar("./temp/folder.tar", "folder.tar");
	}   	
}

?>
