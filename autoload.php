<?php

require 'vendor/autoload.php';
require 'functions.php';
require 'config.php';

spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $path = __DIR__ . '/' . $class . '.php';
    if (file_exists($path)) {
        require $path;
    }
});