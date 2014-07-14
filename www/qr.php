<?php
require_once __DIR__ . '/../config.inc.php'; // <- site-specific settings

require_once __DIR__ . '/../src/lilURL.php'; // <- lilURL class file
$lilurl = new lilURL();
$lilurl->setAllowedProtocols($allowed_protocols);

$id = $_GET['id'];
if ($lilurl->getURL($id)) {
    $shortURL = $lilurl->getShortURL($id);

    $pngPrefix = dirname(__FILE__) . '/../data/qr/';
    
    
    $qrCache = $pngPrefix . 'cache/' . md5($shortURL) . '.png';
    if (!file_exists($qrCache)) {
        $apiUrl = "http://chart.apis.google.com/chart?cht=qr&chs=540&chld=M|1&chl=" . urlencode($shortURL);
        file_put_contents($qrCache, file_get_contents($apiUrl));
    }
    

    $im = imagecreatefrompng($qrCache);
    $n  = imagecreatefrompng($pngPrefix . 'unl_qr_235.png');
    
    $out = imagecreatetruecolor(1080, 1080);
    
    imagecopyresampled($out, $im, 0, 0, 0, 0, 1080, 1080, 540, 540);
    imagedestroy($im);
    
    imagecopy($out, $n, 422, 428, 0, 0, 235, 225);
    imagedestroy($n);
    
    header('Content-Type: image/png');
    imagepng($out);

    imagedestroy($out);

} else {
    header('HTTP/1.1 404 Not Found');
}
