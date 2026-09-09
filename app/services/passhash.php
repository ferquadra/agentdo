<?php
class Passhash
{
    const LENGTH = 40;

    public static function generate()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $out .= $chars[random_int(0, $max)];
        }
        return $out;
    }

    public static function store($plain)
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function verify($plain, $stored)
    {
        return is_string($plain) && is_string($stored) && password_verify($plain, $stored);
    }
}
