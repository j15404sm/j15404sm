<?php
$filename = "apache_log.txt";

$count = count( file($filename));

$array = file($filename);
for ($i=0; $i < count($array) ; $i++) {
  echo ($array[$i]."<br />\n");
}

print("<hr>\n");
print("アクセス件数：${count}件");
?>
