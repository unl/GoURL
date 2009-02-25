<?php
require_once 'UNL/Templates.php';
$page = UNL_Templates::factory('Fixed');
$page->titlegraphic = "<h1>Go URL</h1><h2>Size doesn't matter</h2>";
$page->doctitle = '<title>UNL | Go URL, a short URL service</title>';
$page->head ="<script type=\"text/javascript\" src=\"http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js\"></script>";
$page->maincontentarea = 'Hello world!';
$page->loadSharedcodeFiles();
echo $page;
