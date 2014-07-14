<?php
require_once __DIR__ . '/../config.inc.php'; // <- site-specific settings
require_once __DIR__ . '/../src/lilURL.php';
require_once 'UNL/Auth.php';
require_once 'UNL/Templates.php';
require_once 'UNL/Templates/CachingService/Null.php';

$cas_client = UNL_Auth::factory('SimpleCAS');
if (isset($_GET['login'])) {
    $cas_client->login();
}

if (isset($_GET['logout'])) {
    $cas_client->logout();
}

UNL_Templates::setCachingService(new UNL_Templates_CachingService_Null());
UNL_Templates::$options['version'] = 4;
$page = UNL_Templates::factory('Local');
$page->__params['class']['value'] = 'terminal';
$page->titlegraphic = "Go URL";
$page->pagetitle = '';
$page->doctitle = '<title>Go URL, a short URL service | University of Nebraska-Lincoln</title>';
$page->addStylesheet('sharedcode/css/identity/serviceIndicator.css');
$page->addHeadLink('./', 'home');
$page->footercontent = '© ' . date('Y') . ' University of Nebraska-Lincoln · Lincoln, NE 68588';
$page->addScriptDeclaration(<<<EOD
WDN.setPluginParam('idm', 'login', './?login');
WDN.setPluginParam('idm', 'logout', './?logout');
EOD
);

ob_start();
if ($cas_client->isLoggedIn() && isset($_GET['manage'])) {
    $lilurl = new lilURL();
    $didDelete = false;
    if (isset($_POST, $_POST['urlID'])) {
        $lilurl->deleteURL($_POST['urlID'], $cas_client->getUser());
        $didDelete = true;
    }
    // Show the url management screen
    include __DIR__ . '/templates/manage.php';
} else {
    // Show the submission interface
    include  __DIR__ . '/../src/action.php';
    include __DIR__ . '/templates/index.php';
}
$page->maincontentarea = ob_get_clean();

echo $page;
