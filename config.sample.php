<?php /* conf.inc.php ( config file ) */

// Set the include path
set_include_path(__DIR__ . '/vendor/php');

// page title
define('PAGE_TITLE', 'GO URL Generator');

// MySQL connection info
define('MYSQL_USER', 'myuser');
define('MYSQL_PASS', 'mypass');
define('MYSQL_DB',   'goURL');
define('MYSQL_HOST', 'localhost');

// MySQL tables
define('URL_TABLE', 'tblURLs');

// use mod_rewrite?
define('REWRITE', true);

// allow urls that begin with these strings
$allowed_protocols = array('http://', 'https://', 'mailto:');

// allow urls from these domains for non-authenticated users.
$allowed_domains = array(
    'unl.edu',
    'nebraska.edu',
    'huskers.com',
    'huskeralum.org',
    'www.farrp.org',
    'ceen.unomaha.edu',
    'throughtheeyes.org',
    'buros.org',
);

// uncomment the line below to skip the protocol check
// $allowed_procotols = array();

// uncomment the line below to skip the domain check
$allowed_domains = array();
