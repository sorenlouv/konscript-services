<?php

function getValidHostname($hostnames, $i=0){
	$ip = gethostbyname($hostnames[$i]);
	if ( preg_match('/^\d+/', $ip) != 0 ) {
		$address = $hostnames[$i];
		return $address;
	}elseif((count($hostnames)-1)>$i){
		$i++;
		return getValidHostname($hostnames, $i);
	}else{
		return false;
	}
}

function update_screenshot($hostnames, $project_id){
	global $service_root;
	$hostname = getValidHostname($hostnames);
	
	if($hostname){
		echo $project_id;	
		set_time_limit(360);
		$command = $service_root."wkhtmltoimage --height 1024 $hostname ".$service_root."img/screenshots/".$project_id.".jpg";
		exec($command, $status_msg, $status_code);
		return array($status_msg, $status_code);
	}else{
		return false;
	}
}

// format print_r
function dump($data) {
    if(is_array($data)) {
        print "<pre>-----------------------\n";
        print_r($data);
        print "-----------------------</pre>";
    } elseif (is_object($data)) {
        print "<pre>==========================\n";
        var_dump($data);
        print "===========================</pre>";
    } else {
        print "=========&gt; $data: ";
        var_dump($data);
        print " &lt;=========<br>";
    }
} 

// recursively copy entire directory    
function recursive_copy($src,$dst) { 

    if(is_dir($dst) || !is_dir($src)){
        echo "recurse_copy error";
        exit();
    }

    $dir = opendir($src); 
    mkdir($dst, 01770);
    
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recursive_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
}     

// download and extract latest version of wordpress to prod and dev	
function wp_get_latest($projectName, $wordpress) {

	if($wordpress){
		$command = "./bash/wordpress.sh ".$projectName;
		exec($command, $output, $return_code);		
	
		// something went wrong!
		if($return_code != 0){
			
			$msg = "command: ".$command."<br>";
			$msg .= "return code: ".$return_code."<br>";
									
			$msg .= "<pre>";
			$msg .= print_r( $output, true );
			$msg .= "</pre>";
			echo $msg;
			return 1;
		
		// wordpress installation went smooth
		}else{	
			return 0;	
		}	
		
	// wordpress installation not chosen
	}else{
		return 0;	
	}
	
}

/********
 * Create and download zipped project
 ********/
function downloadZip($project_name, $branch){	

	// download production version
	if($branch=="prod"){
		$path = $project_name.'/prod/current';
		$dbname = $project_name.'-prod';
	
	// download development version		
	}else{
		$path = $project_name."/dev";
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
	}   	
}	

/**
 * recursive remove directory
 */
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
 
//get the folder with the highest number (newest version)
function get_latest_prod_version($dir){
    $folders = get_list_of_folders($dir);
    $versions = array();
    foreach($folders as $folder){
        if(is_numeric($folder) == true){
            $versions[] = $folder;
        }
    }       
    
    //return the newest folder. If none exist return false   
    return count($versions) == 0 ? 1 : $versions[0];
}

/** 
 * get a list of folders in a specific path
 */
function get_list_of_folders($dir){
    
    $folders = array();    
    if (is_dir($dir)) {    		
    
		// append trailing slash if omitted
    	$lastLetter = substr($dir, -1);	    		    		    	
    	$dir .= $lastLetter != "/" ? "/" : "";
    	
        $dh = opendir($dir);
        while (($file = readdir($dh)) !== false) {
            if(is_dir($dir . $file) == true && $file!=".." && $file!="."){

                $folders[] = $file;  
            }              
        }
        closedir($dh);
    }    
	rsort($folders, SORT_NUMERIC);       
    return $folders;
}

/**
 * zip file/folder
 * usage: Zip('/folder/to/compress/', './compressed.zip');
 */
function Zip($source, $destination)
{
    if (extension_loaded('zip') === true)
    {
        if (file_exists($source) === true)
        {

                $zip = new ZipArchive();

                if ($zip->open($destination, ZIPARCHIVE::CREATE) === true)
                {
                        $source = realpath($source);

                        if (is_dir($source) === true)
                        {
                                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

                                foreach ($files as $file)
                                {
                                        $file = realpath($file);

                                        if (is_dir($file) === true)
                                        {
                                                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                                        }

                                        else if (is_file($file) === true)
                                        {
                                                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                                        }
                                }

                        }

                        else if (is_file($source) === true)
                        {
                                $zip->addFromString(basename($source), file_get_contents($source));
                        }
                }

                return $zip->close();
        }
    }

    return false;
}
?>
