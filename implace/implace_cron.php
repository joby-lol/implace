<?php
//load config
include_once("implace_config.php");
//if not inside implace, check key
if (_IMPLACE!='TRUE') {
	if (!$IMPLACE_CONFIG['POOR_MANS_CRON'] || 
		$IMPLACE_CONFIG['CRON_KEY'] != false && $IMPLACE_CONFIG['CRON_KEY'] == $_GET['key']) {
		//die
		die("you're not allowed to run cron");
	}
}
//decide whether to run
$cron_results_file = 'cron.txt';
$last_cron_run = file_exists($cron_results_file)?filemtime($cron_results_file):0;
if (_IMPLACE!='TRUE' || time()-$last_cron_run >= $IMPLACE_CONFIG['POOR_MANS_CRON']) {
	//running cron, touch file immediately to lock
	touch($cron_results_file);
	//decide when to stop
	$when_to_stop = time();
	$when_to_stop += (_IMPLACE!='TRUE')?$IMPLACE_CONFIG['POOR_MANS_CRON_MAX']:$IMPLACE_CONFIG['CRON_MAX'];
	//clean up cache directory
	function IMPLACE_CLEAN_CACHE ($path,$min_ts,$when_to_stop,$first_layer=false) {
		if (time() > $when_to_stop) {
			return;
		}
		$files = scandir($path);
		$file_count = 0;
		foreach ($files as $i => $file) {
			if ($file != '.' && $file != '..') {
				$file_count++;
				$thispath = $path.'/'.$file;
				if (is_dir($thispath)) {
					IMPLACE_CLEAN_CACHE($thispath,$min_ts,$when_to_stop);
				}elseif (is_file($thispath)) {
					if (filemtime($thispath) < $min_ts) {
						unlink($thispath);
						$file_count--;
					}
				}
			}
		}
		if ($file_count == 0 && !$first_layer) {
			rmdir($path);
		}
	}
	IMPLACE_CLEAN_CACHE($IMPLACE_CONFIG['ROOT_PATH'].'/'.$IMPLACE_CONFIG['CACHE_DIR'],
		time()-$IMPLACE_CONFIG['CACHE_MAX_AGE'],
		$when_to_stop,
		true);
}