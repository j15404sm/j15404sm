<?php
# step5.php
# vim:set ts=4 sw=4 noai syntax=off:

function analyze_log($log_fname) {
	print($log_fname . "\n");
	$fp = fopen($log_fname, "r");
	while ($line = fgets($fp)) {
		print("==========================================\n");
		print($line);
		if ( ! preg_match("#^(.+?) (.+?) (.+?) \[(.+?)\] \"(.+?)\" (.+?) (.+?) \"(.+?)\" \"(.+?)\"#", $line, $m)) {
			print("NO MATCH " . "\n");
			continue;
		}
		$remote_hostname	= $m[1];	#%h	Remote hostname 
		$remote_logname		= $m[2];	#%l	Remote logname
		$remote_user		= $m[3];	#%u	Remote user
		$request_time		= $m[4];	#%t	Time the request was received
		$first_line			= $m[5];	#%r	First line of request
		$status				= $m[6];	#%s	Status
		$bytes				= $m[7];	#%b	Size of response in bytes
		$referer			= $m[8];	#%{Referer}i
		$user_agent			= $m[9];	#%{User-Agent}i

		printf("remote_hostname = %s\n", $remote_hostname);
		printf("remote_logname  = %s\n", $remote_logname);
		printf("remote_user     = %s\n", $remote_user);
		printf("request_time    = %s\n", $request_time);
		printf("first_line      = %s\n", $first_line);
		printf("status          = %s\n", $status);
		printf("bytes           = %s\n", $bytes);
		printf("referer         = %s\n", $referer);
		printf("user_agent      = %s\n", $user_agent);

		$unix_time = strtotime($request_time);
		printf("unix_time = %d\n", $unix_time);
		$tm = localtime($unix_time + 9*60*60, TRUE);	# UTC->JST

		printf("year  = %04d\n", $tm["tm_year"] + 1900);
		printf("month = %02d\n", $tm["tm_mon"] + 1);
		printf("day   = %02d\n", $tm["tm_mday"]);
		printf("hour  = %02d\n", $tm["tm_hour"]);
		printf("min   = %02d\n", $tm["tm_min"]);
		printf("sec   = %02d\n", $tm["tm_sec"]);
	}
	fclose($fp);
}

# MAIN

analyze_log("apache_log.txt");

?>
