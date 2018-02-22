<?php
# step4.php
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
	  	print($m[1] . "\n");
	  	print($m[2] . "\n");
	  	print($m[3] . "\n");
	  	print($m[4] . "\n");
	  	print($m[5] . "\n");
	  	print($m[6] . "\n");
	  	print($m[7] . "\n");
	  	print($m[8] . "\n");
	  	print($m[9] . "\n");
	}
	fclose($fp);
}

#10.2.3.4 - - [18/Apr/2005:00:10:47 +0900] "GET / HTTP/1.1" 200 854 "-" "Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)"
#%h	Remote hostname. 
#%l	Remote logname
#%u	Remote user
#%t	Time the request was received
#%r	First line of request.
#%s	Status.
#%b	Size of response in bytes,
#%{Referer}i
#%{User-Agent}i



# MAIN

analyze_log("apache_log.txt");

?>
