<?php
require_once 'includes/conf.php'; // <- site-specific settings

require_once 'includes/lilurl.php'; // <- lilURL class file
$lilurl = new lilURL();
$lilurl->setAllowedProtocols($allowed_protocols);

$id = $_GET['id'];
if ($url = $lilurl->getURL($id)) {
    $shortURL = $lilurl->getShortURL($id);
    
    $pngPrefix = dirname(__FILE__) . '/includes/qr/';
    if (strlen($shortURL) > 31) {
        $params = array(
            'size' => 93,
            'x'    => 39,
            'y'    => 39,
            'w'    => 18,
            'h'    => 18,
            'icon' => 'unl_qr_18.png'
        );
    } else {
        $params = array(
            'size' => 108,
            'x'    => 44,
            'y'    => 44,
            'w'    => 20,
            'h'    => 20,
            'icon' => 'unl_qr_20.png'
        );
    }
    $apiUrl = "http://chart.apis.google.com/chart?cht=qr&chs={$params['size']}&chld=L|1&chl=" . urlencode($shortURL);
    
    $im = imagecreatefrompng($apiUrl);
    $n  = imagecreatefrompng($pngPrefix . $params['icon']);
    
    imagecopy($im, $n, $params['x'], $params['y'], 0, 0, $params['w'], $params['h']);
    header('Content-Type: image/png');
    imagepng($im);
    
    imagedestroy($im);
    imagedestroy($n);
    
} else {
    header('HTTP/1.1 404 Not Found');
}