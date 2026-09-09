<?php
class Share
{
    public static function db()
    {
        $path = Storage::webfiles() . '/_shares.sqlite';
        $db = Sqlite::open($path);
        $db->exec(
            'CREATE TABLE IF NOT EXISTS shares (
                hash TEXT PRIMARY KEY,
                empresa TEXT NOT NULL,
                cliente TEXT NOT NULL,
                proyecto TEXT NOT NULL,
                archivo TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS json_shares (
                hash TEXT PRIMARY KEY,
                empresa TEXT NOT NULL,
                cliente TEXT NOT NULL,
                proyecto TEXT NOT NULL,
                created_at TEXT NOT NULL,
                UNIQUE (empresa, cliente, proyecto)
            )'
        );
        return $db;
    }

    public static function register($hash, $empresa, $cliente, $proyecto, $archivo)
    {
        if (!preg_match('/^[a-z0-9]{40}$/', $hash)) {
            throw new InvalidArgumentException('hash_invalido');
        }
        $db = self::db();
        $st = $db->prepare(
            'INSERT INTO shares (hash, empresa, cliente, proyecto, archivo, created_at)
             VALUES (:h, :e, :c, :p, :a, :t)'
        );
        $st->bindValue(':h', $hash, PDO::PARAM_STR);
        $st->bindValue(':e', $empresa, PDO::PARAM_STR);
        $st->bindValue(':c', $cliente, PDO::PARAM_STR);
        $st->bindValue(':p', $proyecto, PDO::PARAM_STR);
        $st->bindValue(':a', $archivo, PDO::PARAM_STR);
        $st->bindValue(':t', gmdate('c'), PDO::PARAM_STR);
        $st->execute();
        $db = null;
    }

    public static function find($hash)
    {
        if (!preg_match('/^[a-z0-9]{40}$/', $hash)) {
            return null;
        }
        $db = self::db();
        $st = $db->prepare('SELECT * FROM shares WHERE hash = :h');
        $st->bindValue(':h', $hash, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function remove($hash)
    {
        if (!preg_match('/^[a-z0-9]{40}$/', $hash)) {
            return;
        }
        $db = self::db();
        $st = $db->prepare('DELETE FROM shares WHERE hash = :h');
        $st->bindValue(':h', $hash, PDO::PARAM_STR);
        $st->execute();
        $db = null;
    }

    public static function findJson($hash)
    {
        if (!preg_match('/^[a-z0-9]{40}$/', $hash)) {
            return null;
        }
        $db = self::db();
        $st = $db->prepare('SELECT * FROM json_shares WHERE hash = :h');
        $st->bindValue(':h', $hash, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function findJsonByProyecto($empresa, $cliente, $proyecto)
    {
        Storage::assertCode($empresa);
        Storage::assertCode($cliente);
        Storage::assertCode($proyecto);
        $db = self::db();
        $st = $db->prepare(
            'SELECT * FROM json_shares
             WHERE empresa = :e AND cliente = :c AND proyecto = :p'
        );
        $st->bindValue(':e', $empresa, PDO::PARAM_STR);
        $st->bindValue(':c', $cliente, PDO::PARAM_STR);
        $st->bindValue(':p', $proyecto, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function ensureProjectJson($empresa, $cliente, $proyecto)
    {
        $existing = self::findJsonByProyecto($empresa, $cliente, $proyecto);
        if ($existing !== null) {
            return $existing['hash'];
        }

        $hash = Storage::generateHash(40);
        $db = self::db();
        $st = $db->prepare(
            'INSERT INTO json_shares (hash, empresa, cliente, proyecto, created_at)
             VALUES (:h, :e, :c, :p, :t)'
        );
        $st->bindValue(':h', $hash, PDO::PARAM_STR);
        $st->bindValue(':e', $empresa, PDO::PARAM_STR);
        $st->bindValue(':c', $cliente, PDO::PARAM_STR);
        $st->bindValue(':p', $proyecto, PDO::PARAM_STR);
        $st->bindValue(':t', gmdate('c'), PDO::PARAM_STR);
        try {
            $st->execute();
        } catch (PDOException $e) {
            $db = null;
            $again = self::findJsonByProyecto($empresa, $cliente, $proyecto);
            if ($again !== null) {
                return $again['hash'];
            }
            throw $e;
        }
        $db = null;
        return $hash;
    }

    public static function removeJsonByCliente($empresa, $cliente)
    {
        Storage::assertCode($empresa);
        Storage::assertCode($cliente);
        $db = self::db();
        $st = $db->prepare('DELETE FROM json_shares WHERE empresa = :e AND cliente = :c');
        $st->bindValue(':e', $empresa, PDO::PARAM_STR);
        $st->bindValue(':c', $cliente, PDO::PARAM_STR);
        $st->execute();
        $db = null;
    }
}
