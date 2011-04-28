<?php
include("inc/conn.inc.php");
include("inc/util.php");    
include("inc/check.class.php"); 
                  
if(!isset($_GET["projectName"]) || empty($_GET["projectName"]) || !is_dir("/srv/www/".$_GET["projectName"])){    
    $projects = getProjects("/srv/www/");    
    foreach($projects as $project){
        echo '<a href="?projectName='.$project.'">'.$project.'</a><br>';
    }   
    
    echo getTempLink();
}else{
    $check = new check($_GET["projectName"]);
    
    //check GitHub repository    
    $check->checkGithub();
        
    $branch_folders = array($check->getPathToBranchFolder("prod"), $check->getPathToBranchFolder("dev"));   
    foreach($branch_folders as $branch_folder){
        //check folders
        $check->checkFolderMustExist($branch_folder);    
            
        //check git init            
        $check->checkGitInit($branch_folder);                
            
        //check git remote
        $check->checkGitRemote($branch_folder);        
    }

    //check folder writability
    $folders = array($check->getPathToBranchFolder("dev"), $check->getPathToBranchFolder("dev")."/.git", $check->getPathToBranchFolder("prod"), $check->getPathToBranchFolder("prod")."/.git");    
    foreach($folders as $folder){
        $check->checkFolderWritable($folder);       
    }        
    
    //Check post-receive hooks
    $check->checkHooks();
                
    //Check virtual hosts    
    $check->checkVirtualHost();                      
?>

<link href='css/global.css' type='text/css' rel='stylesheet'>
<table>
    <?php 
    foreach($check->getChecks() as $check): 
    $class = $check["status"] == true ? "success" : "error";
    ?>

    <tr class="<?php echo $class ?>"><td><?php echo $check[$class]; ?></td></tr>
    <?php endforeach; ?>
</table>
<?php
} 
?>
