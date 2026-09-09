<?php
class Csrf
{
    public static function token()
    {
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function field()
    {
        return '<input type="hidden" name="csrf" value="' . e(self::token()) . '">';
    }

    public static function check($token)
    {
        return is_string($token)
            && isset($_SESSION['csrf'])
            && is_string($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }
}
