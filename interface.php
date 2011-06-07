<?php
require 'inc/conn.inc.php';

$connection = New DbConn();
$connection->connect();

//prepare statement
$query = "SELECT * FROM deployments 
LEFT JOIN deployment_errors on deployments.id=deployment_errors.deployment_id ORDER BY deployments.id DESC";
$result = $connection->query($query);

while ($deployment = $result->fetch_object()) {    
    $created = $deployment->created;
    $rows[$created]["name"] = $deployment->repository_name;
    $rows[$created]["branch"] = $deployment->branch;
    $rows[$created]["number_of_errors"] = $deployment->number_of_errors;
    $rows[$created]["last_commit_msg"] = $deployment->last_commit_msg;
    $rows[$created]["number_of_commits"] = $deployment->number_of_commits;
    $rows[$created]["created"] = $created;
    $rows[$created]["errors"][] = $deployment->msg;    
}

$html = "";
foreach($rows as $commit){
    $class = $commit["number_of_errors"]==0 ? "success" : "error";
    $html .= "<tr class='commit $class' rel='".$commit["created"]."'>
        <td class='repname'>".$commit["name"]."</td>
        <td class='branch'>".$commit["branch"]."</td>
        <td class='last_commit_msg'>".$commit["last_commit_msg"]."</td>
        <td class='number_of_commits'>".$commit["number_of_commits"]."</td>
        <td>".date("d/m Y", $commit["created"])."</td></tr>";
    
    if($commit["number_of_errors"]>0){
        foreach($commit["errors"] as $error){
            $html .= "
            <tr class='details i_".$commit["created"]."'>
                <td>&nbsp;</td>
                <td><a href='check.php?projectName=".$commit["name"]."'>(check)</a></td>
                <td>".$error."</td>
                <td>&nbsp;</td>
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
    <tr><td class="repname">Rep. name</td><td class="branch">Branch</td><td class="last_commit_msg">Last commit message</td><td class="number_of_commits">Commits</td><td class='created'>Date</td></tr>
    </thead>
    <?php echo $html; ?>
    </table>
</body>
</html>

