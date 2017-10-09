<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/..'));

$application = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini");
// var_dump($application->getConfig());
$application->bootstrap()->run();
?>
