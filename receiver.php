<?php
include("inc/util.php");
include("inc/Check.class.php");
include("inc/Deploy.class.php");

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

$check = new Check();
$deploy = new Deploy($payload, $check);

//passed all preliminary validators
if ($check->getNumberOfErrors() == 0){
	$git_response = Git::git_callback('pull konscript master', "/srv/www/".$deploy->payload->repository->name."/dev", true);
	$deploy->checkGitPull($git_response);                                          
}

echo "Outputting checks: <br>";
foreach($check->getChecks() as $check){
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
