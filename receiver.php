<?php

require 'inc/conn.inc.php';
include("inc/util.php");
include("inc/deploy.class.php");

//Receive and decode the json payload string
if($_SERVER["REMOTE_ADDR"] == "127.0.0.1"){
    $payload = file_get_contents("./payload");  //localhost
}else{
    if(isset($_REQUEST['payload'])){
        $payload = $_REQUEST['payload'];    //payload received
    }else{
        echo "No payload was received!";    //payload not received
        exit();
    }
}

$deploy = new deploy($payload);

//passed all preliminary validators
if ($deploy->getNumberOfErrors() == 0){
        //PROD ONLY: clone current version and create symlinks for temp.konscript.dk
        $deploy->cloneProd();
        $deploy->gitPull();                          
                
}

echo "Outputting checks: <br>";
foreach($deploy->getChecks() as $check){
    echo $check["name"]. " - ";
    if($check["status"] == 0){
        echo "Error: " . $check["error"];
    }elseif(isset($check["success"])){
        echo "Success: " . $check["success"];
    }
    echo "<br>";
}

$deploy->log_to_db();
  
?>
