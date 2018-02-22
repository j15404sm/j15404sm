<?php
# step2.php
# vim:set ts=4 sw=4 noai syntax=off:

function analyze_log($log_fname) {
	print($log_fname . "\n");
	$fp = fopen($log_fname, "r");
	while ($line = fgets($fp)) {
		print($line);
	}
	fclose($fp);
}

# MAIN

analyze_log("apache_log.txt");

?>
