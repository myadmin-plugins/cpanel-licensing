<?php
/************************************************************************************\
* Trouble Free CPanel/VPS Services                                                   *
* (c)2012 Interserver                                                                *
\************************************************************************************/

$webpage = (isset($_SERVER['HTTP_HOST']) ? TRUE : FALSE);
//$GLOBALS['webpage'] = FALSE;
require_once realpath(__DIR__).'/../../../include/functions.inc.php';
define('VERBOSE_MODE', FALSE);
if ($webpage == TRUE)
	add_output('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Unbilled CPanel Licenses</title>
<link rel="stylesheet" href="https://my.interserver.net/jquery/jquery-ui/themes/start/jquery-ui.css" type="text/css" media="all" />
<link rel="stylesheet" href="https://my.interserver.net/templates/my/style.css" type="text/css" media="all" />
<!-- <script src="/lib/jquery-3.x/dist/jquery.min.js"></script> -->
<!-- <script src="/lib/jquery-ui-1.12.x/jquery-ui.min.js"></script> -->
</head>
<body>
');

function_requirements('unbilled_cpanel');
$GLOBALS['tf']->ima = 'admin';
unbilled_cpanel();
if ($webpage == TRUE)
	add_output('</body></html>');
echo $output;
