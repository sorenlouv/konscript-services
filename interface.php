<?php
include("inc/Deploy.class.php");

$connection = New DbConn();
$connection->connect();

// produce query
$query = "SELECT deployments.id, repository_name, author_name, last_commit_msg, number_of_commits, created,  number_of_errors, function_name, error_msg FROM deployments 
LEFT JOIN deployment_errors on deployments.id=deployment_errors.deployment_id 
ORDER BY deployments.id DESC";
$result = $connection->query($query);

// deploy to prod
if(isset($_GET["deployType"]) && isset($_GET["project"])){
	new Deploy($_GET["project"], $_GET["deployType"]);	
}

while ($deployment = $result->fetch_object()) {    
    $created = $deployment->created;
    $rows[$created]["id"] = $deployment->id;
    $rows[$created]["repository_name"] = $deployment->repository_name;
    $rows[$created]["author_name"] = $deployment->author_name;
    $rows[$created]["number_of_errors"] = $deployment->number_of_errors;
    $rows[$created]["last_commit_msg"] = $deployment->last_commit_msg;
    $rows[$created]["number_of_commits"] = $deployment->number_of_commits;
    $rows[$created]["created"] = $created;
    $rows[$created]["errors"][] = array($deployment->function_name, $deployment->error_msg);    
}

$html = "";
foreach($rows as $commit){
    $class = $commit["number_of_errors"]==0 ? "success" : "error";
    $html .= "<tr class='commit $class' rel='".$commit["created"]."'>
        <td class='repname'>".$commit["repository_name"]."</td>
        <td class='author'>".$commit["author_name"]."</td>
        <td class='last_commit_msg'>".$commit["last_commit_msg"]."</td>
        <td class='number_of_commits'>".$commit["number_of_commits"]."</td>
        <td>".date("d/m, H:i", $commit["created"])."</td>
        <td class='deploy'><a class='existing' href='?deployType=existing&project=".$commit["repository_name"]."'>Existing</a> | <a href='?deployType=new&project=".$commit["repository_name"]."'>New</a></td>
        </tr>";
    
    if($commit["number_of_errors"]>0){
        foreach($commit["errors"] as $array){
            $html .= "
            <tr class='details i_".$commit["created"]."'>
                <td>&nbsp;</td>
                <td><a href='check.php?projectName=".$commit["repository_name"]."'>(check)</a></td>
                <td><b>".$array[0]."</b><br>".$array[1]."</td>
                <td>&nbsp;</td>
            </tr>";
        }
    }    
}

include("inc/header.php")
?>

    <a href="create.php">Create new Project</a> | <a href="check.php">Check projects</a>
    <table id='deploylist'>
		<thead>
			<tr>
				<td class="repname">Project</td>
				<td class="author">Author</td>
				<td class="last_commit_msg">Last commit message</td>
				<td class="number_of_commits">Commits</td>
				<td class='created'>Date</td>
				<td class='deploy'>Deploy to:</td>
			</tr>
		</thead>
    <?php echo $html; ?>
    </table>
</body>
</html>

