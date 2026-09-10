<?php
class Empresa
{
    public static function registry()
    {
        $db = Sqlite::open(Storage::webfiles() . '/_registry.sqlite');
        $db->exec(
            'CREATE TABLE IF NOT EXISTS empresas (
                codigo TEXT PRIMARY KEY,
                created_at TEXT NOT NULL
            )'
        );
        return $db;
    }

    public static function exists($codigo)
    {
        Storage::assertCode($codigo);
        $db = self::registry();
        $st = $db->prepare('SELECT codigo FROM empresas WHERE codigo = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false;
    }

    public static function open($codigo)
    {
        Storage::assertCode($codigo);
        $path = Storage::empresaDir($codigo) . '/empresa.sqlite';
        $db = Sqlite::open($path);
        $db->exec(
            'CREATE TABLE IF NOT EXISTS empresa (
                codigo TEXT PRIMARY KEY,
                locked INTEGER NOT NULL DEFAULT 0,
                login_fail_count INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL
            )'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS operadores (
                usuario TEXT PRIMARY KEY,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL,
                telefono TEXT,
                permiso TEXT NOT NULL,
                pass_hash TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS clientes (
                codigo TEXT PRIMARY KEY,
                nombre TEXT NOT NULL,
                operador TEXT,
                created_at TEXT NOT NULL
            )'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS proyectos (
                cliente TEXT NOT NULL,
                codigo TEXT NOT NULL,
                titulo TEXT NOT NULL,
                estado TEXT NOT NULL DEFAULT \'abierto\',
                fecha_limite TEXT,
                aprobacion TEXT NOT NULL DEFAULT \'aprobado\',
                operador TEXT,
                updated_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (cliente, codigo)
            )'
        );
        $db->exec(
            'CREATE TABLE IF NOT EXISTS margen (
                id TEXT PRIMARY KEY,
                cliente TEXT NOT NULL,
                proyecto TEXT NOT NULL,
                tipo TEXT NOT NULL,
                cuerpo TEXT,
                archivo TEXT,
                operador TEXT,
                created_at TEXT NOT NULL
            )'
        );
        self::migrateTenant($db);
        return $db;
    }

    private static function tableColumns($db, $table)
    {
        $cols = array();
        $st = $db->query('PRAGMA table_info(' . $table . ')');
        $rows = $st->fetchAll();
        foreach ($rows as $row) {
            $cols[$row['name']] = true;
        }
        return $cols;
    }

    private static function migrateTenant($db)
    {
        $proyectos = self::tableColumns($db, 'proyectos');
        if (empty($proyectos['estado'])) {
            $db->exec("ALTER TABLE proyectos ADD COLUMN estado TEXT NOT NULL DEFAULT 'abierto'");
        }
        if (empty($proyectos['fecha_limite'])) {
            $db->exec('ALTER TABLE proyectos ADD COLUMN fecha_limite TEXT');
        }
        if (empty($proyectos['aprobacion'])) {
            $db->exec("ALTER TABLE proyectos ADD COLUMN aprobacion TEXT NOT NULL DEFAULT 'aprobado'");
        }
        if (empty($proyectos['operador'])) {
            $db->exec('ALTER TABLE proyectos ADD COLUMN operador TEXT');
        }

        $clientes = self::tableColumns($db, 'clientes');
        if (empty($clientes['operador'])) {
            $db->exec('ALTER TABLE clientes ADD COLUMN operador TEXT');
        }

        $margen = self::tableColumns($db, 'margen');
        if (empty($margen['operador'])) {
            $db->exec('ALTER TABLE margen ADD COLUMN operador TEXT');
        }
    }

    public static function create($codigo)
    {
        Storage::assertCode($codigo);
        if (self::exists($codigo)) {
            return false;
        }

        $now = gmdate('c');
        $reg = self::registry();
        $st = $reg->prepare('INSERT INTO empresas (codigo, created_at) VALUES (:c, :t)');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->bindValue(':t', $now, PDO::PARAM_STR);
        $st->execute();
        $reg = null;

        Storage::ensureDir(Storage::empresaDir($codigo));
        $db = self::open($codigo);
        $st = $db->prepare(
            'INSERT INTO empresa (codigo, locked, login_fail_count, created_at)
             VALUES (:c, 0, 0, :t)'
        );
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->bindValue(':t', $now, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        return true;
    }

    public static function meta($codigo)
    {
        $db = self::open($codigo);
        $st = $db->prepare('SELECT codigo, locked, login_fail_count, created_at FROM empresa WHERE codigo = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function recordFail($codigo)
    {
        $db = self::open($codigo);
        $st = $db->prepare('SELECT login_fail_count, locked FROM empresa WHERE codigo = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        if ($row === false) {
            $db = null;
            return array('locked' => 0, 'login_fail_count' => 0);
        }

        $count = ((int) $row['login_fail_count']) + 1;
        $locked = $count >= LOGIN_FAIL_MAX ? 1 : (int) $row['locked'];
        $up = $db->prepare(
            'UPDATE empresa SET login_fail_count = :n, locked = :l WHERE codigo = :c'
        );
        $up->bindValue(':n', $count, PDO::PARAM_INT);
        $up->bindValue(':l', $locked, PDO::PARAM_INT);
        $up->bindValue(':c', $codigo, PDO::PARAM_STR);
        $up->execute();
        $db = null;

        return array('locked' => $locked, 'login_fail_count' => $count);
    }
}
