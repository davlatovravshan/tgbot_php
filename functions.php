<?php

function vdd($var, bool $append = false): void
{
    file_put_contents(
        'logs.txt',
        print_r($var, true) . PHP_EOL . PHP_EOL,
        $append ? FILE_APPEND : null
    );
}

function vd($var): void
{
    file_put_contents(
        'logs.txt',
        json_encode($var, true) . PHP_EOL . PHP_EOL
    );
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