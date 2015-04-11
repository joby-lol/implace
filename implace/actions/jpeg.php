<?php
if (_IMPLACE!='TRUE') {die("improperly calling implace");}

function implace_action_jpeg_settings_cleanup ($settings) {
	global $IMPLACE_CONFIG;
	global $IMPLACE_JOB;
	//parse settings (room for flags after the size, separated by a -)
	$settings = intval($settings);
	if ($settings < 0) {
		$settings = 0;
	}elseif ($settings > 100) {
		$settings = 100;
	}
	$IMPLACE_CONFIG['JPEG_QUALITY'] = $settings;
	$IMPLACE_JOB['OUTPUT_FORMAT'] = 'jpeg';
	return $settings;
}

function implace_action_jpeg ($settings) {
	//global $IMPLACE_CONFIG;
	//global $IMPLACE_JOB;
	//doesn't actually need to do anything
}