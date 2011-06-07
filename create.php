<?php
    include("inc/Git.php");   
               
    /** save project **/
    if($_POST){
        $errors = array();
        $projectName = $_POST["projectName"];
        $full_path_to_project = "/srv/www/".$projectName;
        
        //Check if folder already exists!
        if(is_dir($full_path_to_project)){
            $errors[] = "Mappen eksisterer allerede";
        }
        
        if(count($errors)==0){
        
            //project root - 02770: leading zero is required; 2 is the sticky bit (set guid); 770 is rwx,rwx,---
            mkdir($full_path_to_project, 02770);
            
            //dev
            $full_path_to_dev = "/srv/www/".$projectName."/dev";
            mkdir($full_path_to_dev, 02770);
            $repo = Git::create($full_path_to_dev); //git init
            $repo->run('remote add konscript git://github.com/konscript/'.$projectName.'.git'); //git remote add; using read-only path!

            //prod
            $full_path_to_prod = "/srv/www/".$projectName."/prod";
            mkdir($full_path_to_prod, 02770);
            mkdir($full_path_to_prod."/1", 02770); //first prod version is always empty!   
            $repo = Git::create($full_path_to_prod."/1");
            $repo->run('remote add konscript git://github.com/konscript/'.$projectName.'.git');     
            
            header("Location: check.php?projectName=".$projectName);
            
        //on error
        }else{
            print_r($errors);
        }
    }
?>

<form action="create.php" method="post">
Project name: <input type="text" name="projectName" />
<input type="submit" name="save" value="Save" />
</form>
