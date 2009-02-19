<?php /* conf.php ( config file ) */

// page title
define('PAGE_TITLE', 'lil&#180; URL Generator');

// MySQL connection info
define('MYSQL_USER', 'dabney');
define('MYSQL_PASS', 'coleman');
define('MYSQL_DB', 'gourl');
define('MYSQL_HOST', 'localhost');

// MySQL tables
define('URL_TABLE', 'go_url');

// use mod_rewrite?
define('REWRITE', true);

// allow urls that begin with these strings
$allowed_protocols = array('http:', 'https:', 'mailto:');

// uncomment the line below to skip the protocol check
// $allowed_procotols = array();

?>
