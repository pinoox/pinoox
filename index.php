<?php

error_reporting(-1); // reports all errors
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);

/**
 * This file doesn't do anything but load essential files.
 */
#boot pinoox
include_once __DIR__ . DIRECTORY_SEPARATOR . 'pincore' . DIRECTORY_SEPARATOR . 'bootstrap.php';
