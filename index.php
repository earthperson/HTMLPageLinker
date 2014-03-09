<?php
set_time_limit(0);
require_once 'HtmlPageLinker.php';
$linker = new HtmlPageLinker('resources/');
$linker->process('page.html');
