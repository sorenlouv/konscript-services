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

	if($_GET["branch"]=="prod"){
		$pathToProd = "/srv/www/".$_GET["projectToZip"]."/prod/";
		$latest = get_latest_prod_version($pathToProd);
		$path = $_GET["projectToZip"].'/prod/'.$latest; //no trailing slash!
		$dbname = $_GET["projectToZip"].'-prod';
	}else{
		$path = $_GET["projectToZip"]."/dev"; //no trailing slash!
		$dbname = $_GET["projectToZip"].'-dev';
	}	
	
	//create mysqldump and zip
	$command = "./zip.sh $dbname $path";
	exec($command, $output, $return_code);	

	if($return_code != 0){
		print_r( $output );
	}
}

?>
