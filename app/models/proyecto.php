<?php
class Proyecto
{
    public static function listAll($empresa)
    {
        $db = Empresa::open($empresa);
        $st = $db->query(
            'SELECT p.cliente, p.codigo, p.titulo, p.updated_at, p.created_at, c.nombre AS cliente_nombre
             FROM proyectos p
             LEFT JOIN clientes c ON c.codigo = p.cliente
             ORDER BY p.updated_at DESC'
        );
        $rows = $st->fetchAll();
        $db = null;
        return $rows;
    }

    public static function find($empresa, $cliente, $codigo)
    {
        Storage::assertCode($cliente);
        Storage::assertCode($codigo);
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'SELECT cliente, codigo, titulo, updated_at, created_at
             FROM proyectos WHERE cliente = :cl AND codigo = :co'
        );
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':co', $codigo, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function create($empresa, $cliente, $codigo, $titulo)
    {
        Storage::assertCode($cliente);
        Storage::assertCode($codigo);
        $titulo = trim($titulo);
        if ($titulo === '' || strlen($titulo) > 160) {
            throw new InvalidArgumentException('titulo_invalido');
        }
        if (Cliente::find($empresa, $cliente) === null) {
            throw new InvalidArgumentException('cliente_inexistente');
        }
        if (self::find($empresa, $cliente, $codigo) !== null) {
            return false;
        }

        $now = gmdate('c');
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'INSERT INTO proyectos (cliente, codigo, titulo, updated_at, created_at)
             VALUES (:cl, :co, :ti, :u, :c)'
        );
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':co', $codigo, PDO::PARAM_STR);
        $st->bindValue(':ti', $titulo, PDO::PARAM_STR);
        $st->bindValue(':u', $now, PDO::PARAM_STR);
        $st->bindValue(':c', $now, PDO::PARAM_STR);
        $st->execute();
        $db = null;

        $dir = Storage::proyectoDir($empresa, $cliente, $codigo);
        Storage::ensureDir($dir);
        Storage::ensureDir($dir . '/margen');
        $diario = Storage::diarioPath($empresa, $cliente, $codigo);
        if (!is_file($diario)) {
            file_put_contents($diario, '');
        }
        return true;
    }

    public static function touch($empresa, $cliente, $codigo)
    {
        $now = gmdate('c');
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'UPDATE proyectos SET updated_at = :u WHERE cliente = :cl AND codigo = :co'
        );
        $st->bindValue(':u', $now, PDO::PARAM_STR);
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':co', $codigo, PDO::PARAM_STR);
        $st->execute();
        $db = null;
        return $now;
    }

    public static function readDiario($empresa, $cliente, $codigo)
    {
        $path = Storage::diarioPath($empresa, $cliente, $codigo);
        if (!is_file($path)) {
            return '';
        }
        $data = file_get_contents($path);
        return $data === false ? '' : $data;
    }

    public static function writeDiario($empresa, $cliente, $codigo, $texto)
    {
        if (!is_string($texto)) {
            $texto = '';
        }
        $path = Storage::diarioPath($empresa, $cliente, $codigo);
        Storage::ensureDir(dirname($path));
        if (file_put_contents($path, $texto) === false) {
            throw new RuntimeException('diario_write');
        }
        return self::touch($empresa, $cliente, $codigo);
    }

    public static function publicExport($empresa, $cliente, $codigo, $jsonHash)
    {
        $meta = Empresa::meta($empresa);
        $cli = Cliente::find($empresa, $cliente);
        $pro = self::find($empresa, $cliente, $codigo);
        if ($meta === null || $cli === null || $pro === null) {
            return null;
        }

        $margen = Margen::listByProyecto($empresa, $cliente, $codigo);
        $items = array();
        foreach ($margen as $item) {
            $row = array(
                'tipo' => $item['tipo'],
                'created_at' => $item['created_at'],
            );
            if ($item['tipo'] === 'archivo') {
                $row['archivo'] = $item['archivo'];
                $row['url'] = absolute_url(share_url($item['id'], $item['archivo']));
            } else {
                $row['cuerpo'] = $item['cuerpo'];
            }
            $items[] = $row;
        }

        return array(
            'ok' => true,
            'agentdo' => APP_VERSION,
            'exported_at' => gmdate('c'),
            'url' => absolute_url(json_share_url($jsonHash)),
            'empresa' => array(
                'codigo' => $meta['codigo'],
                'created_at' => $meta['created_at'],
            ),
            'cliente' => array(
                'codigo' => $cli['codigo'],
                'nombre' => $cli['nombre'],
                'created_at' => $cli['created_at'],
            ),
            'proyecto' => array(
                'codigo' => $pro['codigo'],
                'titulo' => $pro['titulo'],
                'created_at' => $pro['created_at'],
                'updated_at' => $pro['updated_at'],
            ),
            'diario' => self::readDiario($empresa, $cliente, $codigo),
            'margen' => $items,
        );
    }
}
