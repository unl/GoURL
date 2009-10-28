<?php /* api_create.php ( accept GET and spit out a goURL ) */

require_once 'includes/conf.php'; // <- bring in the config
require_once 'UNL/Auth.php';

$cas_client = UNL_Auth::factory('SimpleCAS');

require_once 'includes/action.php'; // <- start the URL building file

if (isset($url)){
	echo $url;
} else {
	echo "There was an error.";
}