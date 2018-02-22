<?php
# step11.php
# vim:set ts=4 sw=4 noai syntax=off:

$DEBUG_LOG = 0;
#$DEBUG_LOG = 1;
#$DEBUG_LOG = 2;

$DEBUG_DB = 0;
#$DEBUG_DB = 1;
#$DEBUG_DB = 2;

function read_log($log_fname, $dbh) {
	if ($GLOBALS["DEBUG_LOG"] >= 1) {
		printf("%s\n", $log_fname);
	}
	$fp = fopen($log_fname, "r");
	while ($line = fgets($fp)) {
		if ($GLOBALS["DEBUG_LOG"] >= 2) {
			printf("==========================================\n");
			printf("%s", $line);
		}
		if ( ! preg_match("#^(.+?) (.+?) (.+?) \[(.+?)\] \"(.+?)\" (.+?) (.+?) \"(.+?)\" \"(.+?)\"#", $line, $m)) {
			if ($GLOBALS["DEBUG_LOG"] >= 1) {
				printf("NO MATCH ERROR IN %s\n", $line);
			}
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

		if ($GLOBALS["DEBUG_LOG"] >= 2) {
			printf("remote_hostname = %s\n", $remote_hostname);
			printf("remote_logname  = %s\n", $remote_logname);
			printf("remote_user     = %s\n", $remote_user);
			printf("request_time    = %s\n", $request_time);
			printf("first_line      = %s\n", $first_line);
			printf("status          = %s\n", $status);
			printf("bytes           = %s\n", $bytes);
			printf("referer         = %s\n", $referer);
			printf("user_agent      = %s\n", $user_agent);
		}

		if ( ! preg_match("#([^:]+):(.+) (.+)#", $request_time, $m_time)) {
			if ($GLOBALS["DEBUG_LOG"] >= 1) {
				printf("TIME ERROR IN %s\n", $line);
			}
			continue;
		}

		$unix_time = strtotime($request_time);
		if ($GLOBALS["DEBUG_LOG"] >= 2) {
			printf("unix_time = %d\n", $unix_time);
		}
		$tm = localtime($unix_time + 9*60*60, TRUE);	# UTC->JST

		$jst_year  = sprintf("%04d", $tm["tm_year"] + 1900);
		$jst_month = sprintf("%02d", $tm["tm_mon"] + 1);
		$jst_day   = sprintf("%02d", $tm["tm_mday"]);
		$jst_hour  = sprintf("%02d", $tm["tm_hour"]);
		$jst_min   = sprintf("%02d", $tm["tm_min"]);
		$jst_sec   = sprintf("%02d", $tm["tm_sec"]);
		if ($GLOBALS["DEBUG_LOG"] >= 2) {
			printf("jst_year  = %s\n", $jst_year);
			printf("jst_month = %s\n", $jst_month);
			printf("jst_day   = %s\n", $jst_day);
			printf("jst_hour  = %s\n", $jst_hour);
			printf("jst_min   = %s\n", $jst_min);
			printf("jst_sec   = %s\n", $jst_sec);
		}

		$sql = <<<EOT
		insert into apache_log (
			remote_hostname,
			remote_logname,
			remote_user,
			request_time,
			first_line,
			status,
			bytes,
			referer,
			user_agent,
			jst_year,
			jst_month,
			jst_day,
			jst_hour,
			jst_min,
			jst_sec
		)
		values (
			"$remote_hostname",
			"$remote_logname",
			"$remote_user",
			"$request_time",
			"$first_line",
			"$status",
			"$bytes",
			"$referer",
			"$user_agent",
			"$jst_year",
			"$jst_month",
			"$jst_day",
			"$jst_hour",
			"$jst_min",
			"$jst_sec"
		);
EOT;
		if ($GLOBALS["DEBUG_DB"] >= 2) {
			printf("QUERY : %s\n", $sql);
		}
		try {
			$dbh->query($sql);
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
			exit;
		}

	}

	fclose($fp);
}

function create_db() {
	try {
		$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
		$dbh = new PDO('sqlite:log.db','' ,'' ,$options);
	} catch (PDOException $e) {
		echo 'Connection failed: ' . $e->getMessage();
		exit;
	}

	$sql = <<<EOT
	drop table if exists apache_log;
EOT;
	if ($GLOBALS["DEBUG_DB"] >= 2) {
		printf("QUERY : %s\n", $sql);
	}
	$dbh->query($sql);

	$sql = <<<EOT
	create table apache_log (
		remote_hostname varchar(256),
		remote_logname  varchar(256),
		remote_user     varchar(256),
		request_time    varcgar(64),
		first_line      varchar(256),
		status          varchar(8),
		bytes           varcgar(32),
		referer         varchar(256),
		user_agent      varchar(256),
		jst_year		char(4),
		jst_month		char(2),
		jst_day			char(2),
		jst_hour		char(2),
		jst_min			char(2),
		jst_sec			char(2)
	);
EOT;
	if ($GLOBALS["DEBUG_DB"] >= 2) {
		printf("QUERY : %s\n", $sql);
	}
	try {
		$dbh->query($sql);
	} catch (PDOException $e) {
		echo 'Connection failed: ' . $e->getMessage();
		exit;
	}

	return $dbh;
}

function select_db($dbh, $sql) {
	if ($GLOBALS["DEBUG_DB"] >= 2) {
		printf("QUERY : %s\n", $sql);
	}
	try {
		$stmt = $dbh->query($sql);
	} catch (PDOException $e) {
		echo 'Connection failed: ' . $e->getMessage();
		exit;
	}
	$n = 0;
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($n++ == 0) {
			$delim = "";
			foreach ($row as $key => $val) {
				printf("%s%s", $delim, $key);
				$delim = "\t";
			}
			printf("\n");
		}
		$delim = "";
		foreach ($row as $key => $val) {
			printf("%s%s", $delim, $val);
			$delim = "\t";
		}
		printf("\n");
	}
}

# MAIN

$shortopts = "";
$shortopts .= "c";		# CreateDB
$shortopts .= "s:";		# Time start
$shortopts .= "e:";		# Time end
$shortopts .= "a";		# Analyze

$opts = getopt($shortopts);

$sw_create = FALSE;
if (array_key_exists("c", $opts)) {
	$sw_create = TRUE;
}

$sw_analyze = FALSE;
if (array_key_exists("a", $opts)) {
	$sw_analyze = TRUE;
}

if ($sw_create) {
	$dbh = create_db();
}

read_log("access_log/access_log-20180128", $dbh);
#read_log("apache_log.txt", $dbh);

if ($sw_analyze) {
	select_db($dbh, <<<EOT
select
	remote_hostname,
	jst_hour,
	count(remote_hostname) as cnt
from
	apache_log
group by
	remote_hostname,
	jst_hour
order by
	cnt
limit
	100
;
EOT
	);
}

?>
