<?php

namespace app\core;

class TgHelper
{
    /**
     * @param $var
     * @return void
     */
    public static function console($var)
    {
        if (is_object($var)) {
            $var = get_class($var);
        }

        if (is_array($var)) {
            $var = json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        echo $var . PHP_EOL;
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public static function get($array, $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;
        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }
        return $value;
    }

}