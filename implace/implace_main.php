<?php
if (_IMPLACE!='TRUE') {die("improperly calling implace");}

//poor man's cron
if ($IMPLACE_CONFIG['POOR_MANS_CRON']) {
	include('implace_cron.php');
}

//function for displaying image
function IMPLACE_DISPLAY () {
	global $IMPLACE_CONFIG;
	global $IMPLACE_JOB;
	if ($IMPLACE_CONFIG['DEBUG_OUTPUT']) {
		//do nothing
	}elseif ($IMPLACE_CONFIG['PASSTHROUGH_IMAGES']) {
		//set content-type
		switch (strtolower($IMPLACE_JOB['IMAGE_EXTENSION'])) {
			case 'gif':
				header("Content-Type: image/gif");
				break;
			case 'jpeg':
				header("Content-Type: image/jpeg");
				break;
			case 'jpg':
				header("Content-Type: image/jpeg");
				break;
			case 'png':
				header("Content-Type: image/png");
				break;
			case 'wbmp':
				header("Content-Type: image/vnd.wap.wbmp");
				break;
			case 'webp':
				header("Content-Type: image/webp");
				break;
			default:
				IMPLACE_ERROR(500);
		}
		//set other http headers
		header("Content-Disposition: filename=".$IMPLACE_JOB['IMAGE_FILENAME'].'-'.$IMPLACE_JOB['ACTIONS_STRING'].'.'.$IMPLACE_JOB['IMAGE_EXTENSION']);
		
		//pass through image and die
		echo(file_get_contents($IMPLACE_JOB["IMAGE_GENERATED_PATH"]));
		die();
	}else {
		//redirect to image
		header("Location: ".$IMPLACE_JOB["IMAGE_GENERATED_URL"]);
		die();
	}
}

//identify image absolute path and verify that it exists
	$IMPLACE_JOB['IMAGE_PATH'] = $IMPLACE_CONFIG['ROOT_PATH'].'/'.$IMPLACE_JOB['REQUEST_FILE'];
	if (!is_file($IMPLACE_JOB['IMAGE_PATH'])) {
		IMPLACE_ERROR(404);
	}

//get image path info
	$pathinfo = pathinfo($IMPLACE_JOB['REQUEST_FILE']);
	$IMPLACE_JOB['IMAGE_FILENAME'] = $pathinfo['filename'];
	$IMPLACE_JOB['IMAGE_EXTENSION'] = $pathinfo['extension'];
	$IMPLACE_JOB['IMAGE_DIR_URL'] = dirname($IMPLACE_JOB['REQUEST_FILE']);
	$IMPLACE_JOB['IMAGE_DIR_PATH'] = realpath($IMPLACE_CONFIG['ROOT_PATH']);

//clean up action list, unset any actions that don't exist
	foreach ($IMPLACE_JOB['ACTIONS'] as $action => $settings) {
		if (!is_file('actions/'.$action.'.php')) {
			unset($IMPLACE_JOB['ACTIONS'][$action]);
		}else {
			include_once('actions/'.$action.'.php');
		}
		$action_settings_cleanup_function = 'implace_action_'.$action.'_settings_cleanup';
		if (function_exists($action_settings_cleanup_function)) {
			$IMPLACE_JOB['ACTIONS'][$action] = $action_settings_cleanup_function($settings);
		}
	}
	//if crop is set, unset scale, either crop or scale should always be run first
	if (isset($IMPLACE_JOB['ACTIONS']['crop']) && isset($IMPLACE_JOB['ACTIONS']['scale'])) {
		unset($IMPLACE_JOB['ACTIONS']['scale']);
	}
	if (isset($IMPLACE_JOB['ACTIONS']['crop'])) {
		$IMPLACE_JOB['ACTIONS'] = array('crop'=>$IMPLACE_JOB['ACTIONS']['crop'])+$IMPLACE_JOB['ACTIONS'];
	}
	if (isset($IMPLACE_JOB['ACTIONS']['scale'])) {
		$IMPLACE_JOB['ACTIONS'] = array('scale'=>$IMPLACE_JOB['ACTIONS']['scale'])+$IMPLACE_JOB['ACTIONS'];
	}
	//generate clean action list string
	$IMPLACE_JOB['ACTIONS_STRING'] = [];
	foreach ($IMPLACE_JOB['ACTIONS'] as $action => $settings) {
		$action = trim($action);
		$settings = trim($settings);
		$string = $action;
		if ($settings != '') {
			$string .= '-'.$settings;
		}
		$IMPLACE_JOB['ACTIONS_STRING'][] = $string;
	}
	$IMPLACE_JOB['ACTIONS_STRING'] = join('_',$IMPLACE_JOB['ACTIONS_STRING']);
	$IMPLACE_JOB['ACTIONS_STRING'] = preg_replace("/[^a-z0-9\-_]/i", '', $IMPLACE_JOB['ACTIONS_STRING']);


//decide where generated image should be
	$IMPLACE_JOB['IMAGE_GENERATED_URL'] = [];
	//directory
	$IMPLACE_JOB['IMAGE_GENERATED_URL'][] = $IMPLACE_CONFIG['CACHE_DIR'];
	$IMPLACE_JOB['IMAGE_GENERATED_URL'][] = md5($IMPLACE_JOB['IMAGE_PATH']);
	//original filename
	$IMPLACE_JOB['IMAGE_GENERATED_URL'][] = $IMPLACE_JOB['IMAGE_FILENAME'];
	//add action info
	//join with extension and add base paths and urls to the front
	$IMPLACE_JOB['IMAGE_GENERATED_URL'] = join('/',$IMPLACE_JOB['IMAGE_GENERATED_URL']);
	$IMPLACE_JOB['IMAGE_GENERATED_URL'] .= '-'.$IMPLACE_JOB['ACTIONS_STRING'];
	$IMPLACE_JOB['IMAGE_GENERATED_URL'] .= '.'.$IMPLACE_JOB['IMAGE_EXTENSION'];
	$IMPLACE_JOB['IMAGE_GENERATED_PATH'] = $IMPLACE_CONFIG['ROOT_PATH'].'/'.$IMPLACE_JOB['IMAGE_GENERATED_URL'];
	$IMPLACE_JOB['IMAGE_GENERATED_URL'] = $IMPLACE_CONFIG['ROOT_URL'].'/'.$IMPLACE_JOB['IMAGE_GENERATED_URL'];

//see if generated image has already been created and cached
	if (!$IMPLACE_CONFIG['DISABLE_CACHE'] && is_file($IMPLACE_JOB['IMAGE_GENERATED_PATH'])) {
		if (filemtime($IMPLACE_JOB['IMAGE_GENERATED_PATH']) > filemtime($IMPLACE_JOB['IMAGE_PATH'])) {
			$IMPLACE_JOB['LOADED_FROM_CACHE'] = true;
			IMPLACE_DISPLAY();
		}
	}

//only run generation parts if not loaded from cache
if (!$IMPLACE_JOB['LOADED_FROM_CACHE']) {
	//create image object with which we will work
	$IMPLACE_JOB['IMAGE'] = @imagecreatefromstring(file_get_contents($IMPLACE_JOB['IMAGE_PATH']));
	if (!$IMPLACE_JOB['IMAGE']) {
		IMPLACE_ERROR(500);
	}

	//apply requested actions to the image
		foreach ($IMPLACE_JOB['ACTIONS'] as $action => $settings) {
			$actionfunction = 'implace_action_'.$action;
			if (!function_exists($actionfunction)) {
				//action handler didn't load properly
				IMPLACE_ERROR(500);
			}
			//execute action handler
			$actionfunction($settings);
		}

	//save the result
		//make sure all directories exist
		$directories = dirname($IMPLACE_JOB['IMAGE_GENERATED_PATH']);
		$directories = split('/',$directories);
		$currentdirectory = '/';
		foreach ($directories as $directory) {
			if ($directory != '') {
				$currentdirectory .= $directory.'/';
				if (!is_dir($currentdirectory)) {
					mkdir($currentdirectory);
				}
			}
		}
		//create the file
		touch($IMPLACE_JOB['IMAGE_GENERATED_PATH']);
		//output from gd
		switch (strtolower($IMPLACE_JOB['IMAGE_EXTENSION'])) {
			case 'gif':
				imagegif($IMPLACE_JOB['IMAGE'],$IMPLACE_JOB['IMAGE_GENERATED_PATH']);
				break;
			case 'jpeg':
				imagejpeg($IMPLACE_JOB['IMAGE'],$IMPLACE_JOB['IMAGE_GENERATED_PATH'],$IMPLACE_CONFIG['JPEG_QUALITY']);
				break;
			case 'jpg':
				imagejpeg($IMPLACE_JOB['IMAGE'],$IMPLACE_JOB['IMAGE_GENERATED_PATH'],$IMPLACE_CONFIG['JPEG_QUALITY']);
				break;
			case 'png':
				imagepng($IMPLACE_JOB['IMAGE'],$IMPLACE_JOB['IMAGE_GENERATED_PATH'],$IMPLACE_CONFIG['PNG_COMPRESSION']);
				break;
			case 'wbmp':
				imagewbmp($IMPLACE_JOB['IMAGE'],$IMPLACE_JOB['IMAGE_GENERATED_PATH']);
				break;
			case 'webp':
				imagewebp($IMPLACE_JOB['IMAGE'],$IMPLACE_JOB['IMAGE_GENERATED_PATH']);
				break;
			default:
				IMPLACE_ERROR(500);
		}

	//redirect to it
	IMPLACE_DISPLAY();
}