<?php
use UNL\Templates\Templates;
use Endroid\QrCode\QrCode;
use Ramsey\Uuid\Uuid;

require_once __DIR__ . '/../config.inc.php';

$lilurl = new lilURL(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
$lilurl->setAllowedProtocols($allowed_protocols);
$lilurl->setAllowedDomains($allowed_domains);
if (defined('GA_ACCOUNT')) {
    $lilurl->setGaAccount(GA_ACCOUNT);
}

session_name('gourl');
$route = '';
$pathInfo = $lilurl->getRequestPath();
phpCAS::client(CAS_VERSION_2_0, 'shib.unl.edu', 443, '/idp/profile/cas');
phpCAS::setCasServerCACert(CAS_CA_FILE);
phpCAS::handleLogoutRequests();

function sendCORSHeaders() {
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: X-Requested-With');
    }
}

// do predispatch actions

if (isset($_GET['login']) || 'a/login' === $pathInfo) {
    phpCAS::forceAuthentication();
    header('Location: ' . $lilurl->getBaseUrl('a/links'));
    exit;
}

if (isset($_GET['logout']) || 'a/logout' === $pathInfo) {
    phpCAS::logout();
    header('Location: ' . $lilurl->getBaseUrl());
    exit;
}

if (isset($_GET['manage']) || in_array($pathInfo, array('a/', 'a/links'))) {
    $route = 'manage';

    if (!phpCAS::isAuthenticated()) {
        header('Location: ' . $lilurl->getBaseUrl('a/login'));
        exit;
    }
}

if ('api_create.php' === $pathInfo) {
    header('Location: ' . $lilurl->getBaseUrl('api/'), true, 307);
    sendCORSHeaders();
    exit;
}

if (!isset($_SESSION['clientId'])) {
    $_SESSION['clientId'] = (string) Uuid::uuid4();
}
$lilurl->setGaClientId($_SESSION['clientId']);

// route

if ('api/' === $pathInfo) {
    $route = 'api';
} elseif (preg_match('#^([^/]+)\.qr$#', $pathInfo, $matches)) {
    $route = 'qr';
    $id = $matches[1];
} elseif (preg_match('#^([^/]+)\/reset$#', $pathInfo, $matches)) {
    $route = 'reset';
    $id = $matches[1];
} elseif (preg_match('#^([^/]+)\\+$#', $pathInfo, $matches)) {
    $route = 'linkinfo';
    $id = $matches[1];
}


if (!$route && $pathInfo !== '') {
    $route = 'redirect';
}

// dispatch

$viewTemplate = 'index.php';
$viewParams = [];

if (!$route || 'api' === $route) {
    if (isset($_GET['url']) && $_GET['url'] === 'referer' && isset($_SERVER['HTTP_REFERER'])) {
        $_POST['theURL'] = urldecode($_SERVER['HTTP_REFERER']);
    }

    if (isset($_POST['theURL'])) {
        $user = $alias = null;

        if (phpCAS::isAuthenticated()) {
            $user = phpCAS::getUser();

            if (!empty($_POST['theAlias'])) {
                $alias = $_POST['theAlias'];
            }
        }

        try {
            $url = $lilurl->handlePOST($alias, $user);
            $_SESSION['gourlFlashBag'] = array(
                'msg' => '<p class="title">You have a Go URL!</p><input type="text" onclick="this.select(); return false;" value="'.$url.'" />',
                'type' => 'success',
                'url' => $url,
            );
        } catch (Exception $e) {
            switch ($e->getCode()) {
                case lilurl::ERR_INVALID_PROTOCOL:
                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">Whoops, Something Broke</p><p>Your URL must begin with <code>http://</code>, <code>https://</code>.</p>',
                    );
                    break;
                case lilurl::ERR_INVALID_DOMAIN:
                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">Whoops, Something Broke</p><p>You must sign in to create a URL for this domain: '.parse_url($_POST['theURL'], PHP_URL_HOST).'</p>',
                    );
                    break;
                case lilurl::ERR_INVALID_ALIAS:
                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">Whoops, Something Broke</p><p>The custom Alias you provided should only contain letters, numbers, underscores (_), and dashes (-).</p>',
                    );
                    break;
                case lilurl::ERR_USED:
                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">Whoops, this alias/URL pair already exists.</p><p>The existing Go URL for this pair is: </p>',
                    );
                    break;
                case lilurl::ERR_ALIAS_EXISTS:
                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">Whoops, This alias is already in use.</p><p>Please use a different alias.</p>',
                    );
                    break;
                default:
                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">Whoops, Something Broke</p><p>There was an error submitting your url. Check your steps.</p>',
                    );
            }

            $_SESSION['gourlFlashBag']['type'] = 'error';
        }

        if ('api' === $route) {
            sendCORSHeaders();
            unset($_SESSION['gourlFlashBag']);

            if (!empty($url)) {
                echo $url;
                exit;
            }

            header('HTTP/1.1 404 Not Found');
            echo 'There was an error. ';
            exit;
        }

        header('Location: ' . $lilurl->getBaseUrl(), true, 303);
        exit;
    } elseif ('api' === $route) {
        sendCORSHeaders();
        header('HTTP/1.1 404 Not Found');
        echo 'You need a URL!';
        exit;
    }
} elseif ('redirect' === $route) {
    $id = $pathInfo;

    if (!$lilurl->handleRedirect($id)) {
        header('HTTP/1.1 404 Not Found');
        include __DIR__ . '/templates/404.php';
        exit;
    }
} elseif ('manage' === $route) {
    $viewTemplate = 'manage.php';

    if (isset($_POST, $_POST['urlID'])) {
        $lilurl->deleteURL($_POST['urlID'], phpCAS::getUser());
        $_SESSION['gourlFlashBag'] = array(
            'msg' => '<p class="title">Delete Successful</p><p>Your Go URL has been deleted</p>',
            'type' => 'success',
        );
        header('Location: ' . $lilurl->getBaseUrl('a/links'));
        exit;
    }
} elseif ('reset' === $route) {
    if (!phpCAS::checkAuthentication() || !$creator = $lilurl->getCreator($id)) {
        header('HTTP/1.1 404 Not Found');
        include __DIR__ . '/templates/404.php';
        exit;
    }
    $user = phpCAS::getUser();
    if ($creator == phpCAS::getUser()) {
        $lilurl->resetRedirectCount($id);
        $_SESSION['gourlFlashBag'] = array(
            'msg' => '<p class="title">Reset Successful</p><p>Your Go URL redirect count has been reset.</p>',
            'type' => 'success',
        );
    } else {
        $error = true;
        $_SESSION['gourlFlashBag'] = array(
            'msg' => '<p class="title">Not Authorized</p><p>You are not the owner of the Go URL.</p>',
            'type' => 'error',
        );
    }

    header('Location: ' . $lilurl->getBaseUrl() . 'a/links', true, 303);
    exit;
} elseif ('qr' === $route) {
    if (!$lilurl->getURL($id)) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    $shortURL = $lilurl->getShortURL($id);
    $pngPrefix = __DIR__ . '/../data/qr/';
    $qrCache = $pngPrefix . 'cache/' . sha1($shortURL) . '.png';

    if (!file_exists($qrCache)) {
        $qrCode = new QrCode();
        $qrCode->setText($shortURL)
            ->setSize(1080)
            ->setPadding(36)
            ->save($qrCache);
    }

    $out = imagecreatefrompng($qrCache);
    $n = imagecreatefrompng($pngPrefix . 'unl_qr_235.png');

    imagecopy($out, $n, 422, 428, 0, 0, 235, 235);
    imagedestroy($n);
    header('Content-Type: image/png');
    imagepng($out);
    imagedestroy($out);
    exit;
} elseif ('linkinfo' === $route) {
    $viewTemplate = 'linkinfo.php';

    if (!$link = $lilurl->getLinkRow($id)) {
        header('HTTP/1.1 404 Not Found');
        include __DIR__ . '/templates/404.php';
        exit;
    }

    $viewParams['link'] = $link;
}

// no actions to be done, time to render a UNL page

phpCAS::checkAuthentication();
$error = false;
$msg = '';
$url = '';

if (isset($_SESSION['gourlFlashBag'])) {
    $msg = $_SESSION['gourlFlashBag']['msg'];

    if ('error' === $_SESSION['gourlFlashBag']['type']) {
        $error = true;
    }

    if (isset($_SESSION['gourlFlashBag']['url'])) {
        $url = $_SESSION['gourlFlashBag']['url'];
    }

    unset($_SESSION['gourlFlashBag']);
}

function renderTemplate($file, $params = [])
{
    global $lilurl, $page;
    extract($params);
    unset($params);
    $escape = function($value) {
        return htmlentities($value, ENT_COMPAT|ENT_HTML5);
    };
    ob_start();
    include __DIR__ .'/templates/' . $file;
    return ob_get_clean();
}

$page = Templates::factory('App', Templates::VERSION_5);

if (file_exists(__DIR__ . '/wdn/templates_5.0')) {
    $page->setLocalIncludePath(__DIR__);
}

$page->affiliation = '';
$page->titlegraphic = '<a href="/" class="dcf-txt-h5">Go URL</a>';
$page->doctitle = 'Go URL, a short URL service | University of Nebraska-Lincoln';

// Add WDN Deprecated Styles
$page->head .= '<link rel="preload" href="https://unlcms.unl.edu/wdn/templates_5.0/css/deprecated.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"> <noscript><link rel="stylesheet" href="https://unlcms.unl.edu/wdn/templates_5.0/css/deprecated.css"></noscript>';

$page->addStyleDeclaration(file_get_contents(__DIR__ . '/css/go.css'));
$page->addHeadLink($lilurl->getBaseUrl(), 'home');

$page->addScriptDeclaration("
require(['jquery'], function($) {
    $(function() {
        $('.moreOptions').hide();
        $('#moreOptions').click(function() {
            var self = this;
            $('.moreOptions').slideDown('fast', function() {
                $(self).remove();
            });
            return false;
        });
        var \$out = $('.wdn_notice input');
        \$out.attr('id', 'gourl_out');
        \$out.attr('title', 'Your Go URL');
    });
});
");

$page->addScriptDeclaration(sprintf(<<<EOD
require(['wdn'], function(WDN) {
    WDN.setPluginParam('idm', 'login', '%s');
    WDN.setPluginParam('idm', 'logout', '%s');
});
EOD
, $lilurl->getBaseUrl('a/login'), $lilurl->getBaseUrl('a/logout')));


$page->appcontrols = renderTemplate('static/navigation.php');
$page->maincontentarea = renderTemplate('flashBag.php', [
    'msg' => $msg,
    'url' => $url,
    'error' => $error,
]);
$page->maincontentarea .= renderTemplate($viewTemplate, $viewParams);

$page->contactinfo = renderTemplate('static/local-footer.php');
$page->doctitle = sprintf('<title>%s</title>', $page->doctitle);

echo $page;
