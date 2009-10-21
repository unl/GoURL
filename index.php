<?php
require_once 'includes/conf.php'; // <- site-specific settings

require_once 'UNL/Auth.php';
require_once 'UNL/Templates.php';
UNL_Templates::$options['version'] = 3;
$page = UNL_Templates::factory('Document');
$page->titlegraphic = "<h1>Go URL</h1>";
$page->doctitle = '<title>UNL | Go URL, a short URL service</title>';
$page->addStylesheet('/wdn/templates_3.0/css/content/forms.css');
$page->addStylesheet('sharedcode/css/identity/serviceIndicator.css');

$cas_client = UNL_Auth::factory('SimpleCAS');
if (isset($_GET['login'])) {
    $cas_client->login();
}

if (isset($_GET['logout'])) {
    $cas_client->logout();
}

$login_link = '<a href="?login">Login</a>';
if ($cas_client->isLoggedIn()) {
    $login_link = '<a href="?logout">Logout</a>';
}

$page->collegenavigationlist = '<ul><li>'.$login_link.'</li></ul>';
$page->breadcrumbs = '<ul>
                        <li><a href="http://www.unl.edu/">UNL</a></li>
                        <li>Go URL</li>
                      </ul>';

require_once 'includes/lilurl.php'; // <- lilURL class file
$lilurl = new lilURL();
$lilurl->setAllowedProtocols($allowed_protocols);

ob_start();
if ($cas_client->isLoggedIn()
    && isset($_GET['manage'])) {
    // Show the url management screen
    include 'UNL/views/manage.php';
} else {
    // Show the submission interface
    include 'UNL/views/index.php';
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
