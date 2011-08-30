<?php
include("inc/Check.class.php");
include("inc/Receiver.class.php");

//Receive the json payload string
if(isset($_REQUEST['payload'])){
    $payload = $_REQUEST['payload'];    //payload received
}else{
    echo "No payload was received!";    //payload not received
    exit();
}

$check = new Check();
$receiver = new Receiver($payload, $check);

//passed all preliminary validators
if ($check->getNumberOfErrors() == 0){
	$git_response = Git::git_callback('pull konscript master', $web_root.$receiver->payload->repository->name."/dev", true);
	$check->checkGitPull($git_response);                                          
}

echo $check->outputResult("No errors occured");		       				                

$receiver->log_to_db();
  
?>
