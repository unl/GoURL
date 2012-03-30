<?php
require_once __DIR__ . '/../config.inc.php'; // <- site-specific settings

require_once 'UNL/Auth.php';


$cas_client = UNL_Auth::factory('SimpleCAS');
if (isset($_GET['login'])) {
    $cas_client->login();
}

if (isset($_GET['logout'])) {
    $cas_client->logout();
}

$login_link = '<a href="?login">Login</a>';
$template = 'Document';
if ($cas_client->isLoggedIn()) {
    $template = 'Fixed';
    $login_link = '<a href="?logout">Logout</a>';
}

require_once 'UNL/Templates.php';
require_once 'UNL/Templates/CachingService/Null.php';
UNL_Templates::setCachingService(new UNL_Templates_CachingService_Null());
UNL_Templates::$options['version'] = 3.1;
$page = UNL_Templates::factory('Fixed');
$page->titlegraphic = "Go URL";
$page->pagetitle = '';
$page->doctitle = '<title>Go URL, a short URL service | University of Nebraska-Lincoln</title>';
$page->addStylesheet('/wdn/templates_3.1/css/content/zenform.css');
$page->addStylesheet('/wdn/templates_3.1/css/content/notice.css');
$page->addStylesheet('sharedcode/css/identity/serviceIndicator.css');


$page->breadcrumbs = '<ul>
                        <li><a href="http://www.unl.edu/">UNL</a></li>
                        <li>Go URL</li>
                      </ul>';

require_once __DIR__ . '/../src/lilURL.php'; // <- lilURL class file
$lilurl = new lilURL();
$lilurl->setAllowedProtocols($allowed_protocols);

ob_start();
if ($cas_client->isLoggedIn()
    && isset($_GET['manage'])) {
    // Show the url management screen
    include __DIR__ . '/templates/manage.php';
} else {
    // Show the submission interface
    include __DIR__ . '/templates/index.php';
}
$page->maincontentarea = ob_get_clean();
$page->loadSharedcodeFiles();
if ($cas_client->isLoggedIn()) {
    $page->navlinks = str_replace('<!-- sublinks -->',
                                  '<ul>
                                    <li><a href="./?manage">Manage URLs</a></li>
                                    <li><a href="./?logout">Logout</a></li>
                                  </ul>',
                                  $page->navlinks);
    
}

echo $page;
