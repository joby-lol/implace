<?php
//if (_IMPLACE!='TRUE') {die("improperly calling implace");}

$IMPLACE_CONFIG = [
	,"CACHE_DIR"			=> "implace/cache"	//relative to ROOT_PATH, needs to be public if PASSTHOUGH_IMAGES is false
	,"DISABLE_CACHE"		=> false 			//always regenerate files, always turn this off in production
	,"PASSTHROUGH_IMAGES"	=> true 			//instead of redirecting to images, pass them through (harder on server, marginally faster for user)
	,"DEBUG_OUTPUT"			=> false 			//output text results instead of finishing

	,"JPEG_QUALITY"			=> 80
	,"PNG_COMPRESSION"		=> 9

	,"POOR_MANS_CRON"		=> 60*60*12			//how frequently in seconds to run cron as part of a normal request
	,"POOR_MANS_CRON_MAX"	=> 5				//how many seconds poor man's cron can run
	,"CRON_KEY"				=> false 			//text key that must be added to manual cron requests
	,"CRON_MAX"				=> 15				//how many seconds regular cron can run
	,"CACHE_MAX_AGE"		=> 60*60*12			//maximum age of a cached file in seconds

	//the following let implace know where it is in the file system
	//if the implace directory is in the same place as the .htaccess
	//file with the rewrite rules you shouldn't have to change any of this
	,"ROOT_URL"				=> dirname(dirname($_SERVER['SCRIPT_NAME']))	//rewrite root
	,"ROOT_PATH"			=> realpath('../')								//rewrite root os path
	,"IMPLACE_URL"			=> dirname($_SERVER['SCRIPT_NAME'])				//implace folder url
	,"IMPLACE_PATH"			=> realpath('.')								//implace folder os path
];