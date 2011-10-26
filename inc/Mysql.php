<?php
include("conn.inc.php");

/*
 * Does everything related to the database
 *******************/
 
class Mysql{		
	var $connection = null;

	// constructor
    function Mysql(){        
    
    	// make sure a connection to the db exists
    	if($this->connection == null){
			$this->connection = New DbConn();
			$this->connection->connect();			    	    	
    	}
    
    }
    
    // fetch all
	function fetchAll($result)
	{    
		$array = array();
		
		if($result instanceof mysqli_stmt)
		{
		    $result->store_result();
		    
		    $variables = array();
		    $data = array();
		    $meta = $result->result_metadata();
		    
		    while($field = $meta->fetch_field())
		        $variables[] = &$data[$field->name]; // pass by reference
		    
		    call_user_func_array(array($result, 'bind_result'), $variables);
		    
		    $i=0;
		    while($result->fetch())
		    {
		        $array[$i] = array();
		        foreach($data as $k=>$v)
		            $array[$i][$k] = $v;
		        $i++;
		        
		        // don't know why, but when I tried $array[] = $data, I got the same one result in all rows
		    }
		}
		elseif($result instanceof mysqli_result)
		{
		    while($row = $result->fetch_assoc())
		        $array[] = $row;
		}
		
		return $array;
	}    
    
    // bump project version 1 up
    function updateProjectVersion($next_version, $project_id){
		$update_project = $this->connection->prep_stmt("UPDATE projects SET current_version=? WHERE id=?");          
		$update_project->bind_param("is",$next_version, $project_id);        
		$update_project->execute() or die("Error: ".$update_project->error);   		
    }
    
    function getProject($project_id){
		$get_project = $this->connection->prep_stmt("SELECT * FROM projects WHERE id=?");          
		$get_project->bind_param("s", $project_id);        
		$get_project->execute() or die("Error: ".$get_project->error);   	        
		$project = $this->fetchAll($get_project);
		return $project[0];
	}
    
    // return true if project uses cache
    function useCache($project_id){
		$project = $this->getProject($project_id);
		return $project["use_cache"] === 0 ? false : true;
    }
}

				

