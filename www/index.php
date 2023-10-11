<?php
use UNL\Templates\Templates;

session_name('gourl');
require_once __DIR__ . '/../config.inc.php';

$lilurl = new lilURL(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
$lilurl->setAllowedProtocols($allowed_protocols);
$lilurl->setAllowedDomains($allowed_domains);
$lilurl->setBotUserAgents($bot_user_agents);
if ($checkForMaliciousURLs === true) {
    $lilurl->setVirusTotalValues($virusTotalAPIURL ?? "", $virusTotalAPIKey ?? "");
}
if (defined('GA_ACCOUNT')) {
    $lilurl->setGaAccount(GA_ACCOUNT);
}

// Use QR Code Icon PNG set, otherwise null it
if (!isset($qrIconPng) || empty($qrIconPng)) {
    $qrIconPng = null;
}
// Use QR Code Icon PNG set, otherwise null it
if (!isset($qrIconSvg) || empty($qrIconSvg)) {
    $qrIconSvg = null;
}
// Use QR Code Icon PNG set, otherwise null it
if (!isset($qrIconSize) || empty($qrIconSize)) {
    $qrIconSize = 300;
}

$flashBag = new GoFlashBag();
$controller = new GoController($lilurl, $auth, $flashBag, $qrIconPng, $qrIconSvg, $qrIconSize);

// do predispatch actions
$controller->preDispatch();

// route
$controller->route();

// dispatch
$controller->dispatch();

// no actions to be done, time to render a UNL page
$auth->isAuthenticated();

$savvy = new Savvy();
$savvy->setTemplatePath(__DIR__ . '/templates');
$theme = new UNL\Templates\Theme($savvy, GoController::$themePath, GoController::$template, GoController::$templateVersion, GoController::$customThemeTemplate);

$page = $theme->getPage();
$savvy->addGlobal('theme', $theme);
$savvy->addGlobal('page', $page);
$savvy->addGlobal('lilurl', $lilurl);
$savvy->addGlobal('auth', $auth);
$savvy->addGlobal('flashBagParams', $flashBag->getParams());
$savvy->addGlobal('viewParams', $controller->getViewParams());
$theme->addGlobal('page', $page);

$appName = !empty(goController::$appName) ? goController::$appName : 'Go URL';
$institution = !empty(goController::$institution) ? goController::$institution : '';

// Theme Based Items
if ($theme->isCustomTheme()) {
    // Custom Theme
    $page->optionalfooter = '<div class="dcf-bleed dcf-wrapper">
    <h3 class="dcf-txt-md dcf-bold dcf-uppercase dcf-lh-3">About ' . $appName . '</h3>
    <p>This application is a product of the <a href="https://dxg.unl.edu/">Digital Experience Group at Nebraska</a>. DXG is a partnership of <a href="https://ucomm.unl.edu/">University Communication</a> and <a href="https://its.unl.edu/">Information Technology Services</a> at the University of Nebraska.</p>
</div>';
} else {
    // UNL Theme
    $theme->setWDNIncludePath(__DIR__);
    if (file_exists($theme->getWDNIncludePath() . '/wdn/templates_5.3')) {
        $page->setLocalIncludePath($theme->getWDNIncludePath());
    }

    $page->contactinfo = $theme->renderThemeTemplate(null, 'localfooter.tpl.php');

    $page->addScriptDeclaration(sprintf(<<<EOD
    require(['wdn'], function(WDN) {
        WDN.initializePlugin('notice');
        WDN.setPluginParam('idm', 'login', '%s');
        WDN.setPluginParam('idm', 'logout', '%s');
    });
EOD
        , $lilurl->getBaseUrl('a/login'), $lilurl->getBaseUrl('a/logout')));
}

// Shared Items
$appPart = !empty($appName) ?  $appName . ', a ': 'A ';
$institutionPart = !empty($institution) ? ' | ' . $institution : '';
$page->doctitle = 'Go URL, a short URL service ' . $institutionPart;
$page->titlegraphic = '<a href="/" class="dcf-txt-h5">' . $appName . '</a>';

$page->addStyleDeclaration(file_get_contents(__DIR__ . '/css/go.css'));
$page->addHeadLink($lilurl->getBaseUrl(), 'home');

$page->addScriptDeclaration("require(['jquery'], function(jq) {
    jq(function($){
        var \$out = $('.dcf-notice input');
        \$out.attr('id', 'gourl_out');
        \$out.attr('class', 'dcf-input-text dcf-w-100%');
        \$out.attr('title', 'Your Go URL');
    });
});");

$page->appcontrols = $savvy->render(null,'navigation.php');
$page->maincontentarea = $savvy->render(null,'flashBag.php');
$page->maincontentarea .= $savvy->render(null, $controller->getViewTemplate());
$page->doctitle = sprintf('<title>%s</title>', $page->doctitle);

if (isset($siteNotice) && $siteNotice->display) {
    $page->displayDCFNoticeMessage($siteNotice->title, $siteNotice->message, $siteNotice->type, $siteNotice->noticePath, $siteNotice->containerID);
}

echo $page;
