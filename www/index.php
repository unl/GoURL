<?php
use UNL\Templates\Templates;

session_name('gourl');
require_once __DIR__ . '/../config.inc.php';

$lilurl = new lilURL(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
$lilurl->setAllowedProtocols($allowed_protocols);
$lilurl->setAllowedDomains($allowed_domains);
if (defined('GA_ACCOUNT')) {
    $lilurl->setGaAccount(GA_ACCOUNT);
}

$controller = new GoController($lilurl, $auth);

// do predispatch actions
$controller->preDispatch();

// route
$controller->route();

// dispatch
$controller->dispatch();

// no actions to be done, time to render a UNL page
$auth->isAuthenticated();

$page = Templates::factory('AppLocal', Templates::VERSION_5_1);
if (file_exists(__DIR__ . '/wdn/templates_5.1')) {
    $page->setLocalIncludePath(__DIR__);
}

$page->affiliation = '';
$page->titlegraphic = '<a href="/" class="dcf-txt-h5">Go URL</a>';
$page->doctitle = 'Go URL, a short URL service | University of Nebraska-Lincoln';

$page->addScript($lilurl->getBaseUrl('js/jquery-3.5.1.min.js'), NULL, TRUE);
$page->addStyleDeclaration(file_get_contents(__DIR__ . '/css/go.css'));
$page->addHeadLink($lilurl->getBaseUrl(), 'home');

$page->addScriptDeclaration("$(function() {
    var \$out = $('.go_notice input');
    \$out.attr('id', 'gourl_out');
    \$out.attr('class', 'dcf-input-text dcf-w-100%');
    \$out.attr('title', 'Your Go URL');
    \$out.attr('style', 'color: #000');
});");

if (isset($useWDNLogin) && $useWDNLogin === TRUE) {
    $page->addScriptDeclaration(sprintf(<<<EOD
    require(['wdn'], function(WDN) {
        WDN.setPluginParam('idm', 'login', '%s');
        WDN.setPluginParam('idm', 'logout', '%s');
    });
EOD
    , $lilurl->getBaseUrl('a/login'), $lilurl->getBaseUrl('a/logout')));
}

$page->appcontrols = $controller->renderTemplate('static/navigation.php', array($allowed_domains));
$page->maincontentarea = $controller->renderTemplate('flashBag.php', $controller->getFlashBagParams());
$page->maincontentarea .= $controller->renderTemplate($controller->getViewTemplate(), $controller->getViewParams());
$page->contactinfo = $controller->renderTemplate('static/local-footer.php');
$page->doctitle = sprintf('<title>%s</title>', $page->doctitle);

echo $page;
