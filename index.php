<?php
require_once 'SimpleCAS/Autoload.php';
require_once 'HTTP/Request2.php';
require_once 'UNL/Templates.php';
$page = UNL_Templates::factory('Fixed');
$page->titlegraphic = "<h1>Go URL</h1><h2>The shorter the better</h2>";
$page->doctitle = '<title>UNL | Go URL, a short URL service</title>';
$page->collegenavigationlist = '<ul><li>Login</li><li>Logout</li></ul>';
$page->leftRandomPromo = '';
$page->addScript('http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js');
$page->addStylesheet('/ucomm/templatedependents/templatecss/components/forms.css');
$page->addStylesheet('sharedcode/css/forms/maincontent.css');


$cas_options = array('hostname' => 'login.unl.edu',
                 'port'     => 443,
                 'uri'      => 'cas');
$protocol = new SimpleCAS_Protocol_Version2($cas_options);

$cas_client = SimpleCAS::client($protocol);


ob_start();
include 'UNL/views/index.php';
$page->maincontentarea = ob_get_clean();
$page->loadSharedcodeFiles();
echo $page;
