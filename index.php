<?php
require_once 'UNL/Templates.php';
$page = UNL_Templates::factory('Fixed');
$page->titlegraphic = "<h1>Go URL</h1><h2>The shorter the better</h2>";
$page->doctitle = '<title>UNL | Go URL, a short URL service</title>';
$page->leftRandomPromo = '';
$page->head ="<script type=\"text/javascript\" src=\"http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js\"></script> <script type=\"text/javascript\"> var navl2Links = 0; //Default navline2 links to display (zero based counting) </script><link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"sharedcode/css/forms/maincontent.css\" />
";
ob_start();
include 'UNL/views/index.php';
$page->maincontentarea = ob_get_clean();
$page->loadSharedcodeFiles();
echo $page;
