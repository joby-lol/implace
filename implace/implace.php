<?php
define('_IMPLACE','TRUE');

//set up the job
$IMPLACE_JOB = [
	 "EXECUTION_START" => microtime(true)
	,"REQUEST_FILE" => $_GET['f']
	,"REQUEST_ACTIONS" => $_GET['implace']
];
//don't allow shenanigans with the filename, if it's being used correctly there will be none of this
$IMPLACE_JOB["REQUEST_FILE"] = str_replace('./', '', $IMPLACE_JOB["REQUEST_FILE"]);
$IMPLACE_JOB["REQUEST_FILE"] = str_replace('..', '', $IMPLACE_JOB["REQUEST_FILE"]);

//load config
include('implace_config.php');

//debug output header
if ($IMPLACE_CONFIG['DEBUG_OUTPUT']) {
	header('Content-Type: text/plain');
}

//parse request actions
$actions = split(';',$IMPLACE_JOB['REQUEST_ACTIONS']);
$IMPLACE_JOB['ACTIONS'] = [];
foreach ($actions as $action) {
	$job = split(':',$action);
	$IMPLACE_JOB['ACTIONS'][trim($job[0])] = trim($job[1]);
}

//function for handling errors
function IMPLACE_ERROR($number) {
	global $IMPLACE_CONFIG;
	$error_file = '/error/'.$number.'.png';
	if (!is_file($IMPLACE_CONFIG['IMPLACE_PATH'].$error_file)) {
		$error_file = '/error/500.png';
	}
	echo("IMPLACE ERROR\n");
	echo("Location: ".$IMPLACE_CONFIG['IMPLACE_URL']."$error_file\n");
}

//check for errors first
if (isset($IMPLACE_JOB['ACTIONS']['error']) && false) {
	//IMPLACE_ERROR($IMPLACE_JOB['ACTIONS']['error']);
}else {
	//there are no errors, so load the main implace file
	include('implace_main.php');
}

//record execution time and memory use
$IMPLACE_JOB['PEAK_MEMORY_USAGE'] = round(memory_get_peak_usage()/1024).'kb';
$IMPLACE_JOB['EXECUTION_END'] = microtime(true);
$IMPLACE_JOB['EXECUTION_TIME'] = round(($IMPLACE_JOB['EXECUTION_END']-$IMPLACE_JOB['EXECUTION_START'])*1000).'ms';

//debugging output
var_dump($IMPLACE_CONFIG);
var_dump($IMPLACE_JOB);
?>