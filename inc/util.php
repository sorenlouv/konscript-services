<?php
//recursively copy entire directory    
function recurse_copy($src,$dst) { 

    if(is_dir($dst) || !is_dir($src)){
        echo "recurse_copy error";
        exit();
    }

    $dir = opendir($src); 
    mkdir($dst, 01770);
    
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
}     

//recursive remove directory
function rrmdir($dir) { 

   if (is_dir($dir) && !empty($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
 } 
 
//get all projects from root folder
function getProjects($root){
    $folders = get_list_of_folders($root);
    $ignore_folders = array("viewgit", "services", "phpmyadmin", "temp");
    $projects = array_diff($folders, $ignore_folders);  
    return $projects;      
}     

//get the folder with the highest number (newest version)
function get_latest_prod_version($dir){
    $folders = get_list_of_folders($dir);
    $versions = array();
    foreach($folders as $folder){
        if(is_numeric($folder) == true){
            $versions[] = $folder;
        }
    }
        
    rsort($versions, SORT_NUMERIC);  
    
    //return the newest folder. If none exist return false   
    return count($versions) == 0 ? 1 : $versions[0];
}

//get a list of folders in a specific path
function get_list_of_folders($dir){
    
    $folders = array();    
    if (is_dir($dir)) {
            $dh = opendir($dir);
            while (($file = readdir($dh)) !== false) {
                if(is_dir($dir . $file) == true && $file!=".." && $file!="."){
                    $folders[] = $file;  
                }              
            }
            closedir($dh);
    }         
    return $folders;
}

function getTempLink(){
    return 'The <a href="http://temp.konscript.dk">temporary link</a> currently points to:<br> '. readlink("/srv/www/temp/link1");
}
?>
