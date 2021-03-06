<?php
class flot extends Controller
{
	function __construct()
	{
		if( ! isset($_SESSION['user']))
		{
			die('Authenticate first.'); // Todo: return json?
		}
	} 

	function ip()
	{
		// See if we're being parsed a request object
		if(array_key_exists('req', $_GET))
		{
			$ip_arr = json_decode($_GET['req']);
		}

		if( is_scalar($ip_arr)) // No array or obj, all back on default ip ranges
		{
			$ip_arr = conf('ip_ranges');
		}
		
		$out = array();
		$reportdata = new Reportdata();

		// Compile SQL
		$cnt = 0;
		$sel_arr = array('COUNT(1) as count');
		foreach ($ip_arr as $key => $value)
		{
			if( is_scalar($value))
			{
				$value = array($value);
			}
			$when_str = '';
			foreach ($value as $k => $v) {
				$when_str .= sprintf(" WHEN remote_ip LIKE '%s%%' THEN 1", $v);
			}
			$sel_arr[] = "SUM(CASE $when_str ELSE 0 END) AS r${cnt}";
			$cnt++;
		}
		$sql = "SELECT " . implode(', ', $sel_arr) . " FROM reportdata";

		// Create Out array
		if($obj = current($reportdata->query($sql)))
		{
			$cnt = $total = 0;
			foreach ($ip_arr as $key => $value)
			{
				$col = 'r' . $cnt++;

				$out[] = array('label' => $key, 'data' => array(array(0,intval($obj->$col))));

				$total += $obj->$col;
			}

			// Add Remaining IP's as other
			$out[] = array('label' => 'Other', 'data' => array(array(0,intval($obj->count - $total))));
				
		}

		echo json_encode($out);

	}

	function hw()
	{
		$out = array();
		$machine = new Machine();
		$sql = 'SELECT machine_name, count(1) as count 
			FROM machine 
			GROUP BY machine_name 
			ORDER BY count DESC';
		$cnt = 0;
		foreach ($machine->query($sql) as $obj)
		{
			$out[] = array('label' => $obj->machine_name, 'data' => array(array($cnt++, intval($obj->count))));
		}

		echo json_encode($out);//TODO: run through view
	}

	function os()
	{
		$out = array();
		$machine = new Machine();
		$sql = "SELECT count(1) as count, os_version 
				FROM machine
				group by os_version 
				ORDER BY os_version ASC";

		$cnt = 0;
		foreach ($machine->query($sql) as $obj)
		{
			$obj->os_version = $obj->os_version ? $obj->os_version : 'Unknown';
			$out[] = array('label' => $obj->os_version, 'data' => array(array(intval($obj->count), $cnt++)));
		}

		echo json_encode($out);//TODO: run through view

	}

	
}