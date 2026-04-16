<?php

namespace Synthora\Gem;

class Mz
{
    public static function e1($data, $key, $iv)
    {
        return openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function d1($data, $key, $iv)
    {
        return openssl_decrypt($data, 'AES-256-CBC', $key, 0, $iv);
    }
}