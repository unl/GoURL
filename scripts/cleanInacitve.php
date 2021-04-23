<?php
require_once dirname(__DIR__) . '/config.inc.php';

$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);

$result = $mysqli->query("DELETE FROM tblURLs WHERE (submitDate <= DATE_SUB(NOW(),INTERVAL 1 YEAR) AND lastRedirect IS NULL) OR lastRedirect <= DATE_SUB(NOW(),INTERVAL 1 YEAR)");
if (!$result) {
	echo "query error"; exit();
}
$total_to_delete = $mysqli->affected_rows;
echo "Inactive URLs deleted: " . $total_to_delete . PHP_EOL;