<?php

function vdd($var, bool $append = false): void
{
    $file = 'logs.txt';
    file_put_contents(
        $file,
        functions . phpprint_r($var, true) . PHP_EOL . PHP_EOL,
        FILE_APPEND
    );
}

function vd($var): void
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

function get($array, $key)
{
    if (is_null($key)) {
        return null;
    }

    $keys = explode('.', $key);
    $value = $array;
    foreach ($keys as $key) {
        if (isset($value[$key])) {
            $value = $value[$key];
        } else {
            return null;
        }
    }
    return $value;
}


function console($var)
{
    // classes
    if (is_object($var)) {
        $var = get_class($var);
    }

    if (is_array($var)) {
        $var = json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $var . PHP_EOL;
}


function cors() {

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}