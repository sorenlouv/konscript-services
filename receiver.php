<?php

require 'inc/conn.inc.php';
include("inc/util.php");
include("inc/deploy.class.php");

//Receive and decode the json payload string
//$payload = $_REQUEST['payload'];
$payload = file_get_contents("./payload");    

if(!isset($payload)){
    echo "No payload was received!";
    exit();
}   

$deploy = new deploy($payload);

//passed all preliminary validators
if ($deploy->getNumberOfErrors() == 0){

        //PROD ONLY: clone current version and create symlinks for temp.konscript.dk
        $deploy->cloneProd();
        $deploy->gitPull();                          
                
}

//failed one or more validators
//note: errors can also occur inside the previous if-statement. Therefore, it is not an if/else but two if-statements!
        //array_reverse($errors)

        
        foreach($deploy->getChecks() as $check){
            if($check["status"] == false){
                echo "!!! ". $check["error"];
            }elseif(isset($check["success"])){
                echo $check["success"];
            }
            echo "<br>";
        }

        //print_r($deploy->getChecks());

        $deploy->log_to_db();
  
?>
