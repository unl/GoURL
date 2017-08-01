<?php
/**
 * This script will update URLs (ONLY admissions.unl.edu URLs right now) based on their 301 redirects.
 * 
 * This is a work-around for not being able to edit URLs in this system, and is a temporary measure until we can implement such functionality.
 * 
 * Note that this script is only scoped to admissions.unl.edu for two reasons:
 * 1: they requested it
 * 2: There is a chance the other systems might use a 301 wrongly (for example, using a 301 to redirect to authentication)
 * 
 */

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
    $redirectURL = curl_getinfo($curl, CURLINFO_REDIRECT_URL);

    curl_close($curl);

    return array('http_code' => $httpStatus,
        'curl_code' => $curlErrorNo,
        'effective_url' => $effectiveURL,
        'redirect_url' => $redirectURL,
    );
}

$result = $mysqli->query("SELECT DISTINCT longURL FROM tblURLs WHERE longURL like '%admissions.unl.edu%'");
if (!$result) {
    echo "query error"; exit();
}

$total_to_delete = 0;
$results = array();


// Cycle through results
while ($row = $result->fetch_assoc()) {
    $details = getHTTPInfo($row['longURL']);
    
    if (!isset($results[$details['http_code']])) {
        $results[$details['http_code']] = array();
    }

    $results[$details['http_code']][$row['longURL']] = array();
    
    if ($details['http_code'] == 301) {
        //Update the URL
        $results[$details['http_code']][$row['longURL']]['target'] = $details['redirect_url'];
        $mysqli->query("UPDATE tblURLs SET longURL = '".$mysqli->escape_string($details['redirect_url'])."' WHERE longURL = '".$mysqli->escape_string($row['longURL'])."'");
        echo 'updated: ' . $row['longURL'] . PHP_EOL;
        echo "\t => " . $details['redirect_url'] . PHP_EOL;
    }
    
    //Sleep for a quarter of a second so we don't overwhelm the server
    usleep(250000);
}
