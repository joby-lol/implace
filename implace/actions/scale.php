<?php
if (_IMPLACE!='TRUE') {die("improperly calling implace");}

function implace_action_scale_settings_cleanup ($settings) {
	//parse settings (room for flags after the size, separated by a -)
	$settings = strtolower($settings);
	$settings = split('-',$settings);
	$settings[0] = split('x',$settings[0]);
	if (!isset($settings[0][1])) {$settings[0][1] = $settings[0][0];}
	if (!isset($settings[1])) {$settings[1] = '';}

	if ($settings[0][0] > 2000) {
		$settings[0][0] = 2000;
	}
	if ($settings[0][0] < 1) {
		$settings[0][0] = 1;
	}

	if ($settings[0][1] > 2000) {
		$settings[0][1] = 2000;
	}
	if ($settings[0][1] < 1) {
		$settings[0][1] = 1;
	}

	$settings_out = join('x',$settings[0]);
	if ($settings[1] != '') {$settings_out .= '-'.$settings[1];}
	return $settings_out;
}

function implace_action_scale ($settings) {
	global $IMPLACE_CONFIG;
	global $IMPLACE_JOB;

	//parse settings (room for flags after the size, separated by a -)
	$settings = strtolower($settings);
	$settings = split('-',$settings);
	$settings[0] = split('x',$settings[0]);
	if (!isset($settings[1])) {$settings[1] = '';}

	//figure out new height and width
	$getimagesize = getimagesize($IMPLACE_JOB['IMAGE_PATH']);
	$original_width = $getimagesize[0];
	$original_height = $getimagesize[1];
	$original_ratio = $original_width/$original_height;

	$desired_width = intval($settings[0][0]);
	$desired_height = intval($settings[0][1]);
	$desired_ratio = $desired_width/$desired_height;

	//remember: ratio > 1 = wide, ratio < 1 = tall
	if ($original_ratio >= $desired_ratio) {
		//wider than requested
		$final_width = $desired_width;
		$final_height = round($final_width/$original_ratio);
	}else {
		//narrower than requested
		$final_height = $desired_height;
		$final_width = round($final_height*$original_ratio);
	}

	//echo "originally $original_width by $original_height\n";
	//echo "scaling to $final_width by $final_height\n";

	//set up new image
	$new = imagecreatetruecolor($final_width, $final_height);

	//copy old image into it
	imagecopyresampled($new,$IMPLACE_JOB['IMAGE'],
		0,0,//dest x,y
		0,0,//src x,y
		$final_width,$final_height,//dest w,h
		$original_width,$original_height//src w,h
		);

	//update implace job
	imagedestroy($IMPLACE_JOB['IMAGE']);
	$IMPLACE_JOB['IMAGE'] = $new;
}