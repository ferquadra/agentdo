<?php
class Cliente
{
    public static function listAll($empresa, $orderBy = 'created_at')
    {
        $db = Empresa::open($empresa);
        $st = $db->query(
            "SELECT c.codigo, c.nombre, c.operador, c.created_at,
                    (SELECT COUNT(*) FROM proyectos p WHERE p.cliente = c.codigo) AS proyectos,
                    (SELECT COUNT(*) FROM proyectos p WHERE p.cliente = c.codigo AND IFNULL(p.estado, 'abierto') = 'abierto') AS proyectos_abiertos
             FROM clientes c
             ORDER BY " . ($orderBy === 'nombre' ? 'c.nombre ASC' : 'c.created_at DESC')
        );
        $rows = $st->fetchAll();
        $db = null;
        return $rows;
    }

    public static function find($empresa, $codigo)
    {
        Storage::assertCode($codigo);
        $db = Empresa::open($empresa);
        $st = $db->prepare('SELECT codigo, nombre, operador, created_at FROM clientes WHERE codigo = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function create($empresa, $codigo, $nombre, $operador = '')
    {
        Storage::assertCode($codigo);
        $nombre = trim($nombre);
        if ($nombre === '' || strlen($nombre) > 120) {
            throw new InvalidArgumentException('nombre_invalido');
        }
        if (self::find($empresa, $codigo) !== null) {
            return false;
        }
        $operador = strtolower(trim((string) $operador));
        if ($operador === '' || !Storage::isCode($operador)) {
            $operador = null;
        }
        $now = gmdate('c');
        $db = Empresa::open($empresa);
        $st = $db->prepare('INSERT INTO clientes (codigo, nombre, operador, created_at) VALUES (:c, :n, :op, :t)');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->bindValue(':n', $nombre, PDO::PARAM_STR);
        if ($operador === null) {
            $st->bindValue(':op', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':op', $operador, PDO::PARAM_STR);
        }
        $st->bindValue(':t', $now, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        Storage::ensureDir(Storage::empresaDir($empresa) . '/' . $codigo);
        return true;
    }

    public static function update($empresa, $codigo, $nombre)
    {
        Storage::assertCode($codigo);
        $nombre = trim($nombre);
        if ($nombre === '' || strlen($nombre) > 120) {
            throw new InvalidArgumentException('nombre_invalido');
        }
        if (self::find($empresa, $codigo) === null) {
            return false;
        }
        $db = Empresa::open($empresa);
        $st = $db->prepare('UPDATE clientes SET nombre = :n WHERE codigo = :c');
        $st->bindValue(':n', $nombre, PDO::PARAM_STR);
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        return true;
    }

    public static function delete($empresa, $codigo)
    {
        Storage::assertCode($codigo);
        if (self::find($empresa, $codigo) === null) {
            return false;
        }

        $db = Empresa::open($empresa);

        $st = $db->prepare('SELECT id FROM margen WHERE cliente = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $margenIds = $st->fetchAll(PDO::FETCH_COLUMN);
        foreach ($margenIds as $id) {
            Share::remove($id);
        }
        Share::removeJsonByCliente($empresa, $codigo);

        $st = $db->prepare('DELETE FROM margen WHERE cliente = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();

        $st = $db->prepare('DELETE FROM proyectos WHERE cliente = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();

        $st = $db->prepare('DELETE FROM clientes WHERE codigo = :c');
        $st->bindValue(':c', $codigo, PDO::PARAM_STR);
        $st->execute();
        $db = null;

        return true;
    }

    public static function count($empresa)
    {
        $db = Empresa::open($empresa);
        $n = (int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
        $db = null;
        return $n;
    }
}
