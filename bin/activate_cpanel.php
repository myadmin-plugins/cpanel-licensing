#!/usr/bin/php
<?php

require_once __DIR__.'/../../../../include/functions.inc.php';
$GLOBALS['tf']->session->create(160308, 'services');
$GLOBALS['tf']->session->verify();

activate_cpanel('66.45.228.100', 401);
deactivate_cpanel('66.45.228.100');

$GLOBALS['tf']->session->destroy();
