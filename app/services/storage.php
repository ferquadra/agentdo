<?php
class Storage
{
    public static function webfiles()
    {
        return ROOT_PATH . '/webfiles';
    }

    public static function lockDir()
    {
        return ROOT_PATH . '/lock';
    }

    public static function assertCode($code)
    {
        if (!is_string($code) || !preg_match('/^[a-z0-9]{3,32}$/', $code)) {
            throw new InvalidArgumentException('codigo_invalido');
        }
    }

    public static function isCode($code)
    {
        return is_string($code) && preg_match('/^[a-z0-9]{3,32}$/', $code);
    }

    public static function assertLowercasePath($path)
    {
        $norm = str_replace('\\', '/', (string) $path);
        $root = str_replace('\\', '/', ROOT_PATH);
        if (strpos($norm, $root) === 0) {
            $rel = substr($norm, strlen($root));
        } else {
            $rel = $norm;
            if (preg_match('/^[a-zA-Z]:/', $rel)) {
                $rel = substr($rel, 2);
            }
        }
        if ($rel !== strtolower($rel)) {
            throw new InvalidArgumentException('path_mayusculas');
        }
    }

    public static function empresaDir($codigo)
    {
        self::assertCode($codigo);
        $dir = self::webfiles() . '/' . $codigo;
        self::assertLowercasePath($dir);
        return $dir;
    }

    public static function ensureDir($dir)
    {
        self::assertLowercasePath($dir);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function proyectoDir($empresa, $cliente, $proyecto)
    {
        self::assertCode($empresa);
        self::assertCode($cliente);
        self::assertCode($proyecto);
        $dir = self::empresaDir($empresa) . '/' . $cliente . '/' . $proyecto;
        self::assertLowercasePath($dir);
        return $dir;
    }

    public static function diarioPath($empresa, $cliente, $proyecto)
    {
        return self::proyectoDir($empresa, $cliente, $proyecto) . '/diario.txt';
    }

    public static function margenDir($empresa, $cliente, $proyecto, $hash)
    {
        if (!preg_match('/^[a-z0-9]{40}$/', $hash)) {
            throw new InvalidArgumentException('hash_invalido');
        }
        $dir = self::proyectoDir($empresa, $cliente, $proyecto) . '/margen/' . $hash;
        self::assertLowercasePath($dir);
        return $dir;
    }

    public static function generateHash($length = 40)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $max)];
        }
        return $out;
    }

    public static function normalizeFilename($name)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9._-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-.');
        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'archivo';
        }
        return $name;
    }

    public static function allowedExtensions()
    {
        return array('zip', 'pdf', 'xlsx', 'docx', 'jpg', 'png', 'webp', 'mp3', 'mp4', 'wmv');
    }

    public static function maxUploadBytes()
    {
        return 25 * 1024 * 1024;
    }
}
