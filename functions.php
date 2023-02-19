<?php

function vdd($var, bool $append = false): void
{
    $file = 'logs.txt';
    if ($append) {
        $file = 'logs_append.txt';
    }
    file_put_contents(
        $file,
        json_encode($var, true) . PHP_EOL . PHP_EOL,
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