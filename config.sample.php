<?php
set_include_path(get_include_path() . PATH_SEPARATOR .  __DIR__ . '/src');
require __DIR__ . '/vendor/autoload.php';

// MySQL connection info
define('MYSQL_USER', 'myuser');
define('MYSQL_PASS', 'mypass');
define('MYSQL_DB',   'goURL');
define('MYSQL_HOST', 'localhost');

$useTheme = 'unl';
if ($useTheme === 'dcf') {
    // dcf
    GoController::$appName = 'ShortURL';
    GoController::$institution = 'University of DCF';
    GoController::$themePath = __DIR__ . '/src/Themes/dcf';
    GoController::$template = UNL\Templates\Theme::TYPE_APP;
    GoController::$customThemeTemplate = 'app.tpl.php';
    GoController::$templateVersion = UNL\Templates\Theme::CUSTOM_VERSION;
} else {
    // UNL
    GoController::$appName = 'GoURL';
    GoController::$institution = 'University of Nebraska&ndash;Lincoln';
    GoController::$themePath = __DIR__ . '/src/Themes/unl';
    GoController::$template = UNL\Templates\Theme::TYPE_APP_LOCAL;
    GoController::$templateVersion = UNL\Templates\Templates::VERSION_5_3;
}

// Define Auth
// Path to system trusted certificates
define('CAS_CA_FILE', '/etc/pki/tls/cert.pem');
$auth = new \UNL\Templates\Auth\AuthCAS('2.0', 'shib.unl.edu', 443, '/idp/profile/cas', CAS_CA_FILE);

// Set QR icon for center of QR code (expects 235x235 png), defaults to blank icon
$qrIconPng = __DIR__ . '/data/qr/icons/unl_qr_235.png';

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
