<?php
# step9.php
# vim:set ts=4 sw=4 noai syntax=off:

$DEBUG_LOG = 0;
#$DEBUG_LOG = 1;
#$DEBUG_LOG = 2;

#$DEBUG_DB = 0;
$DEBUG_DB = 1;
#$DEBUG_DB = 2;

function analyze_log($log_fname, $dbh) {
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

		$jst = $m_time[1] . " " . $m_time[2];
		$tz  = $m_time[3];

		if ($GLOBALS["DEBUG_LOG"] >= 2) {
			printf("jst             = %s\n", $jst);
			printf("tz              = %s\n", $tz);
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
			jst,
			tz
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
			"$jst",
			"$tz"
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
		jst             varchar(32),
		tz              varchar(8)
	);
EOT;
#		index(remote_hostname, jst)
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

$dbh = create_db();
analyze_log("apache_log.txt", $dbh);
select_db($dbh, <<<EOT
select
	remote_hostname,
	count(remote_hostname) as cnt
from
	apache_log
group by
	remote_hostname
order by
	cnt
limit
	100
;
EOT
);

?>
