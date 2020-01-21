<?php

require __DIR__ . '/vendor/autoload.php';

// Path to system trusted certificates
define('CAS_CA_FILE', '/etc/pki/tls/cert.pem');

// MySQL connection info
define('MYSQL_USER', 'myuser');
define('MYSQL_PASS', 'mypass');
define('MYSQL_DB',   'goURL');
define('MYSQL_HOST', 'localhost');

// allow urls that begin with these strings
$allowed_protocols = array('http://', 'https://');

//Use the https protocol for short URLS. If false or undefined, http:// will be used instead.
define('HTTPS_SHORT_URLS', true);

// allow urls from these domains for non-authenticated users.
$allowed_domains = array(
    'unl.edu',
    'nebraska.edu',
    'huskers.com',
    'huskeralum.org',
    'buros.org',
);

// uncomment the line below to skip the protocol check
// $allowed_procotols = array();

// uncomment the line below to skip the domain check
// $allowed_domains = array();
