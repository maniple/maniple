<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

set_include_path(
    realpath(__DIR__ . '/../library')
    . PATH_SEPARATOR
    . get_include_path()
);
