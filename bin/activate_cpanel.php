#!/usr/bin/env php
<?php

require_once __DIR__.'/../../../../include/functions.inc.php';
\MyAdmin\App::session()->create(160308, 'services');
\MyAdmin\App::session()->verify();

activate_cpanel('66.45.228.100', 401);
deactivate_cpanel('66.45.228.100');

\MyAdmin\App::session()->destroy();
