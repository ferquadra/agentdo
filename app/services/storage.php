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
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('mkdir_failed:' . $dir);
            }
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('dir_not_writable:' . $dir);
        }
    }

    public static function ensureRuntimeDirs()
    {
        self::ensureDir(self::webfiles());
        self::ensureDir(self::lockDir());
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
        return array('zip', 'pdf', 'xlsx', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'mp3', 'mp4', 'wmv');
    }

    public static function imageExtensions()
    {
        return array('jpg', 'jpeg', 'png', 'webp');
    }

    public static function isImageFilename($name)
    {
        $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
        return in_array($ext, self::imageExtensions(), true);
    }

    public static function maxUploadBytes()
    {
        return 20 * 1024 * 1024;
    }

    public static function maxTotalBytes()
    {
        return 3 * 1024 * 1024 * 1024;
    }

    public static function usedBytes()
    {
        return self::dirBytes(self::webfiles());
    }

    public static function assertCanStore($extraBytes)
    {
        $extra = (int) $extraBytes;
        if ($extra < 0) {
            $extra = 0;
        }
        if (self::usedBytes() + $extra > self::maxTotalBytes()) {
            throw new InvalidArgumentException('cuota_total');
        }
    }

    private static function dirBytes($dir)
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $total = 0;
        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $info) {
                if ($info->isFile()) {
                    $size = $info->getSize();
                    if ($size !== false && $size > 0) {
                        $total += $size;
                    }
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException('cuota_medir');
        }
        return $total;
    }
}
