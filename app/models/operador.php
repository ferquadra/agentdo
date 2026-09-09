<?php
class Operador
{
    public static function find($codigo, $usuario)
    {
        Storage::assertCode($codigo);
        Storage::assertCode($usuario);
        $db = Empresa::open($codigo);
        $st = $db->prepare('SELECT * FROM operadores WHERE usuario = :u');
        $st->bindValue(':u', $usuario, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function listAll($codigo)
    {
        Storage::assertCode($codigo);
        $db = Empresa::open($codigo);
        $st = $db->query(
            'SELECT usuario, nombre, email, telefono, permiso, created_at
             FROM operadores
             ORDER BY created_at ASC'
        );
        $rows = $st->fetchAll();
        $db = null;
        return $rows;
    }

    public static function create($codigo, $data)
    {
        Storage::assertCode($codigo);
        Storage::assertCode($data['usuario']);
        $now = gmdate('c');
        $db = Empresa::open($codigo);
        $st = $db->prepare(
            'INSERT INTO operadores (usuario, nombre, email, telefono, permiso, pass_hash, created_at)
             VALUES (:u, :n, :e, :t, :p, :h, :c)'
        );
        $st->bindValue(':u', $data['usuario'], PDO::PARAM_STR);
        $st->bindValue(':n', $data['nombre'], PDO::PARAM_STR);
        $st->bindValue(':e', $data['email'], PDO::PARAM_STR);
        $st->bindValue(':t', $data['telefono'], PDO::PARAM_STR);
        $st->bindValue(':p', $data['permiso'], PDO::PARAM_STR);
        $st->bindValue(':h', $data['pass_hash'], PDO::PARAM_STR);
        $st->bindValue(':c', $now, PDO::PARAM_STR);
        $ok = $st->execute();
        $db = null;
        return $ok;
    }

    public static function update($codigo, $usuario, $data)
    {
        Storage::assertCode($codigo);
        Storage::assertCode($usuario);
        if (self::find($codigo, $usuario) === null) {
            return false;
        }
        $db = Empresa::open($codigo);
        $st = $db->prepare(
            'UPDATE operadores SET nombre = :n, email = :e, telefono = :t, permiso = :p WHERE usuario = :u'
        );
        $st->bindValue(':n', $data['nombre'], PDO::PARAM_STR);
        $st->bindValue(':e', $data['email'], PDO::PARAM_STR);
        $st->bindValue(':t', $data['telefono'], PDO::PARAM_STR);
        $st->bindValue(':p', $data['permiso'], PDO::PARAM_STR);
        $st->bindValue(':u', $usuario, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        return true;
    }

    public static function rotatePasshash($codigo, $usuario)
    {
        Storage::assertCode($codigo);
        Storage::assertCode($usuario);
        if (self::find($codigo, $usuario) === null) {
            return false;
        }
        $plain = Passhash::generate();
        $db = Empresa::open($codigo);
        $st = $db->prepare('UPDATE operadores SET pass_hash = :h WHERE usuario = :u');
        $st->bindValue(':h', Passhash::store($plain), PDO::PARAM_STR);
        $st->bindValue(':u', $usuario, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        return $plain;
    }

    public static function delete($codigo, $usuario)
    {
        Storage::assertCode($codigo);
        Storage::assertCode($usuario);
        if ($usuario === 'admin') {
            throw new InvalidArgumentException('admin_reservado');
        }
        if (self::find($codigo, $usuario) === null) {
            return false;
        }
        $db = Empresa::open($codigo);
        $st = $db->prepare('DELETE FROM operadores WHERE usuario = :u');
        $st->bindValue(':u', $usuario, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        return true;
    }

    public static function count($codigo)
    {
        $db = Empresa::open($codigo);
        $st = $db->query('SELECT COUNT(*) AS n FROM operadores');
        $row = $st->fetch();
        $db = null;
        return $row ? (int) $row['n'] : 0;
    }
}
