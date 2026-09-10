<?php
class Proyecto
{
    public static function listAll($empresa, $cliente = '', $includeCerrados = false)
    {
        $db = Empresa::open($empresa);
        $sql = 'SELECT p.cliente, p.codigo, p.titulo, p.estado, p.fecha_limite, p.aprobacion,
                    p.operador, p.updated_at, p.created_at, c.nombre AS cliente_nombre
             FROM proyectos p
             LEFT JOIN clientes c ON c.codigo = p.cliente';
        $where = array();
        if ($cliente !== '') {
            Storage::assertCode($cliente);
            $where[] = 'p.cliente = :cl';
        }
        if (!$includeCerrados) {
            $where[] = "IFNULL(p.estado, 'abierto') = 'abierto'";
        }
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY CASE WHEN IFNULL(p.estado, 'abierto') = 'cerrado' THEN 1 ELSE 0 END,
                  CASE WHEN p.fecha_limite IS NULL OR p.fecha_limite = '' THEN 1 ELSE 0 END,
                  p.fecha_limite ASC,
                  p.updated_at DESC";
        if ($cliente !== '') {
            $st = $db->prepare($sql);
            $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
            $st->execute();
        } else {
            $st = $db->query($sql);
        }
        $rows = $st->fetchAll();
        $db = null;
        return $rows;
    }

    public static function counts($empresa, $cliente = '')
    {
        $db = Empresa::open($empresa);
        $sql = "SELECT
                SUM(CASE WHEN IFNULL(estado, 'abierto') = 'abierto' THEN 1 ELSE 0 END) AS abiertos,
                SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) AS cerrados
             FROM proyectos";
        if ($cliente !== '') {
            Storage::assertCode($cliente);
            $sql .= ' WHERE cliente = :cl';
            $st = $db->prepare($sql);
            $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
            $st->execute();
        } else {
            $st = $db->query($sql);
        }
        $row = $st->fetch();
        $db = null;
        return array(
            'abiertos' => $row && $row['abiertos'] !== null ? (int) $row['abiertos'] : 0,
            'cerrados' => $row && $row['cerrados'] !== null ? (int) $row['cerrados'] : 0,
        );
    }

    public static function find($empresa, $cliente, $codigo)
    {
        Storage::assertCode($cliente);
        Storage::assertCode($codigo);
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'SELECT cliente, codigo, titulo, estado, fecha_limite, aprobacion, operador, updated_at, created_at
             FROM proyectos WHERE cliente = :cl AND codigo = :co'
        );
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':co', $codigo, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        $db = null;
        return $row !== false ? $row : null;
    }

    public static function normalizeEstado($valor)
    {
        return $valor === 'cerrado' ? 'cerrado' : 'abierto';
    }

    public static function normalizeAprobacion($valor)
    {
        return $valor === 'requiere' ? 'requiere' : 'aprobado';
    }

    public static function validDate($ymd)
    {
        if (!is_string($ymd) || $ymd === '') {
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return false;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $ymd);
        return $dt !== false && $dt->format('Y-m-d') === $ymd;
    }

    public static function codigoFromTitulo($titulo)
    {
        $s = trim((string) $titulo);
        if (function_exists('mb_strtolower')) {
            $s = mb_strtolower($s, 'UTF-8');
        } else {
            $s = strtolower($s);
        }
        $map = array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        );
        $s = strtr($s, $map);
        $s = preg_replace('/[^a-z0-9]/', '', $s);
        if (strlen($s) > 32) {
            $s = substr($s, 0, 32);
        }
        return $s;
    }

    public static function codesByCliente($empresa)
    {
        $db = Empresa::open($empresa);
        $st = $db->query('SELECT cliente, codigo FROM proyectos');
        $map = array();
        foreach ($st->fetchAll() as $row) {
            $cl = $row['cliente'];
            if (!isset($map[$cl])) {
                $map[$cl] = array();
            }
            $map[$cl][] = $row['codigo'];
        }
        $db = null;
        return $map;
    }

    public static function normalizeOperador($usuario)
    {
        $usuario = strtolower(trim((string) $usuario));
        if ($usuario === '' || !Storage::isCode($usuario)) {
            return null;
        }
        return $usuario;
    }

    public static function create($empresa, $cliente, $codigo, $titulo, $extra = array())
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

        $estado = 'abierto';
        $aprobacion = self::normalizeAprobacion(isset($extra['aprobacion']) ? $extra['aprobacion'] : 'aprobado');
        $operador = self::normalizeOperador(isset($extra['operador']) ? $extra['operador'] : '');
        $fechaLimite = isset($extra['fecha_limite']) ? trim((string) $extra['fecha_limite']) : '';
        if ($fechaLimite === '') {
            $fechaLimite = null;
        } elseif (!self::validDate($fechaLimite)) {
            throw new InvalidArgumentException('fecha_invalida');
        }

        $now = gmdate('c');
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'INSERT INTO proyectos (cliente, codigo, titulo, estado, fecha_limite, aprobacion, operador, updated_at, created_at)
             VALUES (:cl, :co, :ti, :es, :fl, :ap, :op, :u, :c)'
        );
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':co', $codigo, PDO::PARAM_STR);
        $st->bindValue(':ti', $titulo, PDO::PARAM_STR);
        $st->bindValue(':es', $estado, PDO::PARAM_STR);
        if ($fechaLimite === null) {
            $st->bindValue(':fl', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':fl', $fechaLimite, PDO::PARAM_STR);
        }
        $st->bindValue(':ap', $aprobacion, PDO::PARAM_STR);
        if ($operador === null) {
            $st->bindValue(':op', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':op', $operador, PDO::PARAM_STR);
        }
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

    public static function updateMeta($empresa, $cliente, $codigo, $estado, $fechaLimite, $aprobacion)
    {
        Storage::assertCode($cliente);
        Storage::assertCode($codigo);
        if (self::find($empresa, $cliente, $codigo) === null) {
            return null;
        }

        $estado = self::normalizeEstado($estado);
        $aprobacion = self::normalizeAprobacion($aprobacion);
        $fechaLimite = $fechaLimite === null ? '' : trim((string) $fechaLimite);
        if ($fechaLimite === '') {
            $fechaLimite = null;
        } elseif (!self::validDate($fechaLimite)) {
            throw new InvalidArgumentException('fecha_invalida');
        }

        $now = gmdate('c');
        $db = Empresa::open($empresa);
        $st = $db->prepare(
            'UPDATE proyectos
             SET estado = :e, fecha_limite = :f, aprobacion = :a, updated_at = :u
             WHERE cliente = :cl AND codigo = :co'
        );
        $st->bindValue(':e', $estado, PDO::PARAM_STR);
        if ($fechaLimite === null) {
            $st->bindValue(':f', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':f', $fechaLimite, PDO::PARAM_STR);
        }
        $st->bindValue(':a', $aprobacion, PDO::PARAM_STR);
        $st->bindValue(':u', $now, PDO::PARAM_STR);
        $st->bindValue(':cl', $cliente, PDO::PARAM_STR);
        $st->bindValue(':co', $codigo, PDO::PARAM_STR);
        $st->execute();
        $db = null;

        return array(
            'estado' => $estado,
            'fecha_limite' => $fechaLimite,
            'aprobacion' => $aprobacion,
            'updated_at' => $now,
        );
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
                'operador' => !empty($item['operador']) ? $item['operador'] : null,
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
                'operador' => !empty($cli['operador']) ? $cli['operador'] : null,
                'created_at' => $cli['created_at'],
            ),
            'proyecto' => array(
                'codigo' => $pro['codigo'],
                'titulo' => $pro['titulo'],
                'estado' => self::normalizeEstado(isset($pro['estado']) ? $pro['estado'] : 'abierto'),
                'fecha_limite' => !empty($pro['fecha_limite']) ? $pro['fecha_limite'] : null,
                'aprobacion' => self::normalizeAprobacion(isset($pro['aprobacion']) ? $pro['aprobacion'] : 'aprobado'),
                'operador' => !empty($pro['operador']) ? $pro['operador'] : null,
                'created_at' => $pro['created_at'],
                'updated_at' => $pro['updated_at'],
            ),
            'diario' => self::readDiario($empresa, $cliente, $codigo),
            'margen' => $items,
        );
    }
}
