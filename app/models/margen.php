<?php
class Margen
{
    public static function listByProyecto($empresa, $cliente, $proyecto)
    {
        Storage::assertCode($cliente);
        Storage::assertCode($proyecto);
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'SELECT id, cliente, proyecto, tipo, cuerpo, archivo, operador, created_at
             FROM margen
             WHERE cliente = :cl AND proyecto = :pr
             ORDER BY created_at DESC'
        );
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':pr', $proyecto, PDO::PARAM_STR);
        $st->execute();
        $rows = $st->fetchAll();
        $db = null;
        return $rows;
    }

    public static function find($empresa, $id)
    {
        if (!preg_match('/^[a-z0-9]{40}$/', $id)) {
            return null;
        }
        $db = Empresa::open($empresa);
        $st = $db->prepare('SELECT * FROM margen WHERE id = :i');
        $st->bindValue(':i', $id, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function addNota($empresa, $cliente, $proyecto, $texto, $operador = '')
    {
        $texto = trim($texto);
        if ($texto === '' || strlen($texto) > 4000) {
            throw new InvalidArgumentException('nota_invalida');
        }
        return self::insert($empresa, $cliente, $proyecto, 'nota', $texto, '', $operador);
    }

    public static function addEnlace($empresa, $cliente, $proyecto, $url, $operador = '')
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2000) {
            throw new InvalidArgumentException('enlace_invalido');
        }
        if (!preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException('enlace_invalido');
        }
        return self::insert($empresa, $cliente, $proyecto, 'enlace', $url, '', $operador);
    }

    public static function addArchivo($empresa, $cliente, $proyecto, $tmpPath, $originalName, $operador = '')
    {
        $filename = Storage::normalizeFilename($originalName);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
            $filename = preg_replace('/\.jpeg$/i', '.jpg', $filename);
        }
        if (!in_array($ext, Storage::allowedExtensions(), true)) {
            throw new InvalidArgumentException('extension_invalida');
        }
        if (in_array($ext, Storage::imageExtensions(), true)) {
            $filename = self::nextImageFilename($empresa, $cliente, $proyecto, $ext);
        }
        if (!is_file($tmpPath)) {
            throw new InvalidArgumentException('upload_invalido');
        }
        $size = filesize($tmpPath);
        if ($size === false || $size > Storage::maxUploadBytes()) {
            throw new InvalidArgumentException('archivo_grande');
        }
        Storage::assertCanStore($size);

        $hash = Storage::generateHash(40);
        $dir = Storage::margenDir($empresa, $cliente, $proyecto, $hash);
        Storage::ensureDir($dir);
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $dest) && !rename($tmpPath, $dest)) {
            throw new RuntimeException('upload_move');
        }

        $op = Proyecto::normalizeOperador($operador);
        $meta = array(
            'id' => $hash,
            'tipo' => 'archivo',
            'archivo' => $filename,
            'operador' => $op,
            'created_at' => gmdate('c'),
        );
        file_put_contents($dir . '/meta.json', json_encode($meta));

        self::insertRow($empresa, $hash, $cliente, $proyecto, 'archivo', $filename, $filename, $op);
        Share::register($hash, $empresa, $cliente, $proyecto, $filename);
        Proyecto::touch($empresa, $cliente, $proyecto);
        return $hash;
    }

    public static function delete($empresa, $id)
    {
        $item = self::find($empresa, $id);
        if ($item === null) {
            return false;
        }
        $db = Empresa::open($empresa);
        $st = $db->prepare('DELETE FROM margen WHERE id = :i');
        $st->bindValue(':i', $id, PDO::PARAM_STR);
        $st->execute();
        $db = null;

        if ($item['tipo'] === 'archivo') {
            Share::remove($id);
            $dir = Storage::margenDir($empresa, $item['cliente'], $item['proyecto'], $id);
            self::rmTree($dir);
        }
        Proyecto::touch($empresa, $item['cliente'], $item['proyecto']);
        return true;
    }

    private static function nextImageFilename($empresa, $cliente, $proyecto, $ext)
    {
        $max = 0;
        $rows = self::listByProyecto($empresa, $cliente, $proyecto);
        foreach ($rows as $row) {
            if (empty($row['archivo'])) {
                continue;
            }
            if (preg_match('/^image([1-9][0-9]*)\.(png|jpe?g|webp)$/', strtolower($row['archivo']), $m)) {
                $n = (int) $m[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }
        return 'image' . ($max + 1) . '.' . $ext;
    }

    private static function insert($empresa, $cliente, $proyecto, $tipo, $cuerpo, $archivo, $operador = '')
    {
        $hash = Storage::generateHash(40);
        self::insertRow($empresa, $hash, $cliente, $proyecto, $tipo, $cuerpo, $archivo, $operador);
        Proyecto::touch($empresa, $cliente, $proyecto);
        return $hash;
    }

    private static function insertRow($empresa, $id, $cliente, $proyecto, $tipo, $cuerpo, $archivo, $operador = '')
    {
        $operador = Proyecto::normalizeOperador($operador);
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'INSERT INTO margen (id, cliente, proyecto, tipo, cuerpo, archivo, operador, created_at)
             VALUES (:i, :cl, :pr, :t, :cu, :a, :op, :c)'
        );
        $st->bindValue(':i', $id, PDO::PARAM_STR);
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':pr', $proyecto, PDO::PARAM_STR);
        $st->bindValue(':t', $tipo, PDO::PARAM_STR);
        $st->bindValue(':cu', $cuerpo, PDO::PARAM_STR);
        $st->bindValue(':a', $archivo, PDO::PARAM_STR);
        if ($operador === null) {
            $st->bindValue(':op', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':op', $operador, PDO::PARAM_STR);
        }
        $st->bindValue(':c', gmdate('c'), PDO::PARAM_STR);
        $st->execute();
        $db = null;
    }

    private static function rmTree($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::rmTree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
