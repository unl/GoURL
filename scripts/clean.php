<?php

require_once dirname(__DIR__) . '/config.inc.php';

$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);


function getHTTPInfo($url, $followLocation = false)
{
  $curl = curl_init($url);

  curl_setopt($curl, CURLOPT_NOBODY, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
  curl_setopt($curl, CURLOPT_TIMEOUT, 5);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $followLocation);
  curl_setopt($curl, CURLOPT_USERAGENT, 'TEST_MAINTENANCE/1.0');

  curl_exec($curl);

  $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  $effectiveURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
  $curlErrorNo = curl_errno($curl);

  curl_close($curl);

  return array('http_code' => $httpStatus,
               'curl_code' => $curlErrorNo,
               'effective_url' => $effectiveURL);
}

$result = $mysqli->query("SELECT * FROM tblURLs");
if (!$result) {
  echo "query error"; exit();
}

$total_to_delete = 0;

// Cycle through results
while ($row = $result->fetch_assoc()) {
  $details = getHTTPInfo($row['longURL']);
  if ($details['http_code'] == 404) {
    $mysqli->query("DELETE FROM tblURLs WHERE urlID = '" . (int)$row['urlID']  . "' LIMIT 1");
    $total_to_delete++;
    echo "(" . $total_to_delete . ") deleted: " . $row['longURL'] . PHP_EOL;
  }
  //Implement deletion of any short URLS which have not been visited in two years
  $mysqli->query("DELETE FROM tblURLs WHERE DATE_ADD(lastRedirectDate, INTERVAL 2 YEAR) < CURRENT_TIMESTAMP()");
  
}

echo "total deleted: " . $total_to_delete . PHP_EOL;