<?php /* api_create.php ( accept GET and spit out a goURL ) */

// Specify domains from which requests are allowed
header('Access-Control-Allow-Origin: *');

//Send header to allow for the XDomainRequest from IE8
header('XDomainRequestAllowed: 1');

// Specify which request methods are allowed
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Additional headers which may be sent along with the CORS request
// The X-Requested-With header allows jQuery requests to go through
header('Access-Control-Allow-Headers: X-Requested-With');

// Exit early so the page isnÕt fully loaded for options requests
if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
exit();
}
/*
echo 'Hello CORS, this is '
. $_SERVER['SERVER_NAME'] . PHP_EOL
.'You sent a '.$_SERVER['REQUEST_METHOD'] . ' request.' . PHP_EOL;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
echo 'Your name is ' . htmlentities($_POST['name']);
}
*/
require_once 'includes/conf.php'; // <- bring in the config
require_once 'UNL/Auth.php';

$cas_client = UNL_Auth::factory('SimpleCAS');

require_once 'includes/action.php'; // <- start the URL building file

if (!isset($_POST['theURL'])) {
	echo "You need a URL!";
}
if (isset($url)){
	echo $url;
} else {
	echo "There was an error. ";
}