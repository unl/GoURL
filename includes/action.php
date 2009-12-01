<?php  //this is the actions used to start the URL building.

require_once 'includes/lilurl.php'; // <- lilURL class file


$lilurl = new lilURL();
$lilurl->setAllowedProtocols($allowed_protocols);
$lilurl->setAllowedDomains($allowed_domains);

$msg = '';

if (isset($_POST['theURL'])) {
    $user = $alias = null;
    if ($cas_client->isLoggedIn()) {
        $user = $cas_client->getUser();
        if (!empty($_POST['theAlias'])) { //if the user is CAS authenticated, then he/she can use the $alias
            $alias = $_POST['theAlias'];
        }
    }
    try {
        $url = $lilurl->handlePOST($alias, $user);
        $msg = '<p class="success">Your Go URL is: <a href="'.$url.'">'.$url.'</a></p>';
    } catch (Exception $e) {
        switch ($e->getCode()) {
            case lilurl::ERR_INVALID_PROTOCOL:
                $msg = 'Your URL must begin with <code>http://</code>, <code>https://</code> or <code>mailto:</code>.';
                break;
            case lilurl::ERR_INVALID_DOMAIN:
                $msg = ' You must sign in to create a URL for this domain: '.parse_url($_POST['theURL'], PHP_URL_HOST);
                break;
            default:
                $msg = 'There was an error submitting your url. Please try again later.';
        }
        $msg = '<p class="error">'.$msg.'</p>';
    }
} else {
    // if the form hasn't been submitted, look for an id to redirect to
    $explodo = explode('/', $_SERVER['REQUEST_URI']);
    $id = $explodo[count($explodo)-1];
    if (!empty($id) && $id != '?login') {
        if (!$lilurl->handleRedirect($id)) {
            $msg = '<p class="error">'.htmlentities($id).' - Sorry, but that Go URL isn\'t in our database.</p>';
        }
    }
}
?>