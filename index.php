<?php
include("inc/Deploy.class.php");
/**
 * deploy to prod
 **/
if(isset($_GET["deployType"]) && isset($_GET["project"])){
	new Deploy($_GET["project"], $_GET["deployType"]);	
}

$connection = New DbConn();
$connection->connect();

// produce query
$result = $connection->query("SELECT deployments.id, project_id, author_name, last_commit_msg, number_of_commits, created,  number_of_errors, function_name, error_msg FROM deployments 
LEFT JOIN deployment_errors on deployments.id=deployment_errors.deployment_id 
ORDER BY deployments.id DESC");

while ($deployment = $result->fetch_object()) {    
    $created = $deployment->created;
    $rows[$created]["id"] = $deployment->id;
    $rows[$created]["project_id"] = $deployment->project_id;
    $rows[$created]["author_name"] = $deployment->author_name;
    $rows[$created]["number_of_errors"] = $deployment->number_of_errors;
    $rows[$created]["last_commit_msg"] = $deployment->last_commit_msg;
    $rows[$created]["number_of_commits"] = $deployment->number_of_commits;
    $rows[$created]["created"] = $created;
    $rows[$created]["errors"][] = array($deployment->function_name, $deployment->error_msg);    
}

$deploy_controls_added = array();

$html = "";
foreach($rows as $commit){
    $class = $commit["number_of_errors"]==0 ? "success" : "error";
    $html .= "<tr class='commit $class' rel='".$commit["created"]."'>
        <td class='project_id'>".$commit["project_id"]."</td>
        <td class='author'>".$commit["author_name"]."</td>
        <td class='last_commit_msg'>".$commit["last_commit_msg"]."</td>
        <td class='number_of_commits'>".$commit["number_of_commits"]."</td>
        <td>".date("d/m, H:i", $commit["created"])."</td>";
        
        $deploy_controls = "";
        if(!in_array($commit["project_id"], $deploy_controls_added)){
        	$deploy_controls_added[] = $commit["project_id"];
        	$deploy_controls = "
        	<a class='existing' href='?deployType=existing&project=".$commit["project_id"]."'>Existing</a> | 
	        <a href='?deployType=new&project=".$commit["project_id"]."'>New</a>";
        }
        
        $html .= "<td class='deploy'>$deploy_controls</td>
        </tr>";
    
    if($commit["number_of_errors"]>0){
        foreach($commit["errors"] as $array){
            $html .= "
            <tr class='details i_".$commit["created"]."'>
                <td>&nbsp;</td>
                <td><a href='/Projects/check.php?id=".$commit["project_id"]."'>(check)</a></td>
                <td><b>".$array[0]."</b><br>".$array[1]."</td>
                <td>&nbsp;</td>
            </tr>";
        }
    }    
}

include("inc/header.php")
?>

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

