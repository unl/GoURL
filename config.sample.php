<?php
set_include_path(get_include_path() . PATH_SEPARATOR .  __DIR__ . '/src');
require __DIR__ . '/vendor/autoload.php';

// MySQL connection info
define('MYSQL_USER', 'myuser');
define('MYSQL_PASS', 'mypass');
define('MYSQL_DB',   'goURL');
define('MYSQL_HOST', 'localhost');

// Set allowed domains for CORS
GoRouter::$corsAllowedDomains = array('unl.edu');

$useTheme = 'unl';
if ($useTheme === 'dcf') {
    // dcf
    GoController::$appName = 'Short URL';
    GoController::$institution = 'University of DCF';
    GoController::$themePath = __DIR__ . '/src/Themes/dcf';
    GoController::$template = UNL\Templates\Theme::TYPE_APP;
    GoController::$customThemeTemplate = 'app.tpl.php';
    GoController::$templateVersion = UNL\Templates\Theme::CUSTOM_VERSION;
} else {
    // UNL
    GoController::$appName = 'Go URL';
    GoController::$institution = 'University of Nebraska&ndash;Lincoln';
    GoController::$themePath = __DIR__ . '/src/Themes/unl';
    GoController::$template = UNL\Templates\Theme::TYPE_APP_LOCAL;
    GoController::$templateVersion = UNL\Templates\Templates::VERSION_5_3;
}

// Define Auth
// Path to system trusted certificates
define('CAS_CA_FILE', '/etc/pki/tls/cert.pem');
$auth = new \UNL\Templates\Auth\AuthCAS('2.0', 'shib.unl.edu', 443, '/idp/profile/cas', CAS_CA_FILE);

// Set QR icon for center of QR code,
// Square icons are placed in center of QR code at specified size
// QR codes are 1080 x 1080 with 36 margin
// If an icon is not provided we will default to a empty QR code
$qrIconPng  = __DIR__ . '/data/qr/icons/UNL.png';
$qrIconSvg  = __DIR__ . '/data/qr/icons/UNL.svg';
$qrIconSize = 500;

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

// block from counting redirect if client's users agent that contain any of these strings
$bot_user_agents = array(
    'Googlebot',
    'BingPreview',
    'bingbot',
    'SemrushBot',
    'Slurp',
    'DuckDuckBot',
    'Baiduspider',
    'YandexBot',
    'Spider',
    'Exabot',
    'Konqueror',
    'facebookexternalhit',
    'facebot',
    'ia_archiver',
    'Google Web Preview',
    'MsnBot',
    'Twitterbot'
);

// uncomment the line below to skip the protocol check
// $allowed_procotols = array();

// uncomment the line below to skip the domain check
// $allowed_domains = array();

// Site Notice
$siteNotice = new stdClass();
$siteNotice->display = false;
$siteNotice->noticePath = 'dcf-notice';
$siteNotice->containerID = 'dcf-main';
$siteNotice->type = 'dcf-notice-info';
$siteNotice->title = 'Maintenance Notice';
$siteNotice->message = 'We will be performing site maintenance on February 4th from 4:30 to 5:00 pm CST.  This site may not be available during this time.';
