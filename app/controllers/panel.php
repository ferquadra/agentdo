<?php
class PanelController extends Controller
{
    public function index(Request $request, $params = array())
    {
        $user = Auth::requireLogin();
        $empresa = $user['empresa'];
        Empresa::open($empresa);

        $clientes = Cliente::listAll($empresa, 'nombre');
        $clientesCount = count($clientes);
        $filtroCodigo = strtolower($request->input('cliente'));
        $filtroCliente = null;
        if (Storage::isCode($filtroCodigo)) {
            $filtroCliente = Cliente::find($empresa, $filtroCodigo);
        }
        $mostrarCerrados = $request->input('cerrados') === '1';
        $filtroCodigoSafe = $filtroCliente ? $filtroCliente['codigo'] : '';
        $proyectos = Proyecto::listAll($empresa, $filtroCodigoSafe, $mostrarCerrados);
        $counts = Proyecto::counts($empresa, $filtroCodigoSafe);
        $totalProyectos = 0;
        $totalAbiertos = 0;
        foreach ($clientes as $c) {
            $totalProyectos += (int) $c['proyectos'];
            $totalAbiertos += (int) $c['proyectos_abiertos'];
        }

        $this->view('panel/index', array(
            'title' => $empresa . ' · ' . APP_NAME,
            'page' => 'panel',
            'user' => $user,
            'proyectos' => $proyectos,
            'clientes' => $clientes,
            'clientesCount' => $clientesCount,
            'totalProyectos' => $totalProyectos,
            'totalAbiertos' => $totalAbiertos,
            'counts' => $counts,
            'mostrarCerrados' => $mostrarCerrados,
            'filtroCliente' => $filtroCliente,
            'canWrite' => Auth::canWrite(),
        ));
    }

    public function workspace(Request $request, $params = array())
    {
        $user = Auth::requireLogin();
        $empresa = $user['empresa'];
        $cliente = isset($params['cliente']) ? $params['cliente'] : '';
        $proyecto = isset($params['proyecto']) ? $params['proyecto'] : '';

        if (!Storage::isCode($cliente) || !Storage::isCode($proyecto)) {
            http_response_code(404);
            $this->view('home/404', array('title' => 'No encontrado', 'page' => '404'));
            return;
        }

        $cli = Cliente::find($empresa, $cliente);
        $pro = Proyecto::find($empresa, $cliente, $proyecto);
        if ($cli === null || $pro === null) {
            http_response_code(404);
            $this->view('home/404', array('title' => 'No encontrado', 'page' => '404'));
            return;
        }

        $diario = Proyecto::readDiario($empresa, $cliente, $proyecto);
        $margen = Margen::listByProyecto($empresa, $cliente, $proyecto);
        $canWrite = Auth::canWrite();
        $jsonHash = Lock::run($empresa, function () use ($empresa, $cliente, $proyecto) {
            return Share::ensureProjectJson($empresa, $cliente, $proyecto);
        });

        $this->view('panel/workspace', array(
            'title' => $pro['titulo'] . ' · ' . APP_NAME,
            'page' => 'workspace',
            'user' => $user,
            'cliente' => $cli,
            'proyecto' => $pro,
            'diario' => $diario,
            'margen' => $margen,
            'canWrite' => $canWrite,
            'shareBase' => url('a'),
            'jsonUrl' => json_share_url($jsonHash),
        ));
    }

    public function saveDiario(Request $request, $params = array())
    {
        $user = Auth::requireLogin();
        if (!Auth::canWrite()) {
            $this->json(array('ok' => false, 'error' => 'readonly'), 403);
        }
        if (!$this->requireCsrf($request)) {
            $this->json(array('ok' => false, 'error' => 'csrf'), 400);
        }

        $empresa = $user['empresa'];
        $cliente = isset($params['cliente']) ? $params['cliente'] : '';
        $proyecto = isset($params['proyecto']) ? $params['proyecto'] : '';
        if (!Storage::isCode($cliente) || !Storage::isCode($proyecto)) {
            $this->json(array('ok' => false, 'error' => 'not_found'), 404);
        }
        if (Proyecto::find($empresa, $cliente, $proyecto) === null) {
            $this->json(array('ok' => false, 'error' => 'not_found'), 404);
        }

        $texto = isset($_POST['texto']) ? $_POST['texto'] : '';
        if (!is_string($texto)) {
            $texto = '';
        }
        if (strlen($texto) > 2 * 1024 * 1024) {
            $this->json(array('ok' => false, 'error' => 'too_large'), 400);
        }

        try {
            $savedAt = Lock::run($empresa, function () use ($empresa, $cliente, $proyecto, $texto) {
                return Proyecto::writeDiario($empresa, $cliente, $proyecto, $texto);
            });
        } catch (Exception $e) {
            $this->json(array('ok' => false, 'error' => 'save_failed'), 500);
        }

        $this->json(array(
            'ok' => true,
            'saved_at' => $savedAt,
            'saved_label' => self::formatLocal($savedAt),
        ));
    }

    public function saveProps(Request $request, $params = array())
    {
        $user = Auth::requireLogin();
        if (!Auth::canWrite()) {
            $this->json(array('ok' => false, 'error' => 'readonly'), 403);
        }
        if (!$this->requireCsrf($request)) {
            $this->json(array('ok' => false, 'error' => 'csrf'), 400);
        }

        $empresa = $user['empresa'];
        $cliente = isset($params['cliente']) ? $params['cliente'] : '';
        $proyecto = isset($params['proyecto']) ? $params['proyecto'] : '';
        if (!Storage::isCode($cliente) || !Storage::isCode($proyecto)) {
            $this->json(array('ok' => false, 'error' => 'not_found'), 404);
        }
        if (Proyecto::find($empresa, $cliente, $proyecto) === null) {
            $this->json(array('ok' => false, 'error' => 'not_found'), 404);
        }

        $estado = $request->input('estado');
        $aprobacion = $request->input('aprobacion');
        $fechaLimite = $request->input('fecha_limite');

        try {
            $meta = Lock::run($empresa, function () use ($empresa, $cliente, $proyecto, $estado, $fechaLimite, $aprobacion) {
                return Proyecto::updateMeta($empresa, $cliente, $proyecto, $estado, $fechaLimite, $aprobacion);
            });
        } catch (InvalidArgumentException $e) {
            $this->json(array('ok' => false, 'error' => $e->getMessage()), 400);
        } catch (Exception $e) {
            $this->json(array('ok' => false, 'error' => 'save_failed'), 500);
        }

        if ($meta === null) {
            $this->json(array('ok' => false, 'error' => 'not_found'), 404);
        }

        $this->json(array(
            'ok' => true,
            'estado' => $meta['estado'],
            'fecha_limite' => $meta['fecha_limite'],
            'aprobacion' => $meta['aprobacion'],
            'fecha_label' => $meta['fecha_limite'] ? format_date($meta['fecha_limite']) : '',
            'fecha_vencida' => date_is_past($meta['fecha_limite']),
            'saved_at' => $meta['updated_at'],
            'saved_label' => self::formatLocal($meta['updated_at']),
        ));
    }

    public function addMargen(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        if (!$this->requireCsrf($request)) {
            $this->redirect('panel');
        }

        $empresa = $user['empresa'];
        $cliente = isset($params['cliente']) ? $params['cliente'] : '';
        $proyecto = isset($params['proyecto']) ? $params['proyecto'] : '';
        $back = 'panel/' . $cliente . '/' . $proyecto;

        if (!Storage::isCode($cliente) || !Storage::isCode($proyecto)
            || Proyecto::find($empresa, $cliente, $proyecto) === null) {
            $this->redirect('panel');
        }

        $tipo = $request->input('tipo');
        try {
            Lock::run($empresa, function () use ($empresa, $cliente, $proyecto, $tipo, $request, $user) {
                if ($tipo === 'nota') {
                    Margen::addNota($empresa, $cliente, $proyecto, $request->input('cuerpo'), $user['usuario']);
                } elseif ($tipo === 'enlace') {
                    Margen::addEnlace($empresa, $cliente, $proyecto, $request->input('cuerpo'), $user['usuario']);
                } else {
                    throw new InvalidArgumentException('tipo_invalido');
                }
            });
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'No se pudo guardar el margen.';
        }
        $this->redirect($back);
    }

    public function addArchivo(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        $empresa = $user['empresa'];
        $cliente = isset($params['cliente']) ? $params['cliente'] : '';
        $proyecto = isset($params['proyecto']) ? $params['proyecto'] : '';
        $back = 'panel/' . $cliente . '/' . $proyecto;
        $wantsJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!$this->requireCsrf($request)) {
            if ($wantsJson) {
                $this->json(array('ok' => false, 'error' => 'csrf'), 400);
            }
            $this->redirect($back);
        }

        if (!Storage::isCode($cliente) || !Storage::isCode($proyecto)
            || Proyecto::find($empresa, $cliente, $proyecto) === null) {
            if ($wantsJson) {
                $this->json(array('ok' => false, 'error' => 'not_found'), 404);
            }
            $this->redirect('panel');
        }

        if (empty($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
            if ($wantsJson) {
                $this->json(array('ok' => false, 'error' => 'no_file'), 400);
            }
            $_SESSION['flash_error'] = 'No se recibió el archivo.';
            $this->redirect($back);
        }

        $file = $_FILES['archivo'];
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            if ($wantsJson) {
                $this->json(array('ok' => false, 'error' => 'upload_error'), 400);
            }
            $_SESSION['flash_error'] = 'Error al subir el archivo.';
            $this->redirect($back);
        }

        try {
            $hash = Lock::run($empresa, function () use ($empresa, $cliente, $proyecto, $file, $user) {
                return Margen::addArchivo(
                    $empresa,
                    $cliente,
                    $proyecto,
                    $file['tmp_name'],
                    $file['name'],
                    $user['usuario']
                );
            });
        } catch (InvalidArgumentException $e) {
            $msg = $this->uploadError($e->getMessage());
            if ($wantsJson) {
                $this->json(array('ok' => false, 'error' => $e->getMessage()), 400);
            }
            $_SESSION['flash_error'] = $msg;
            $this->redirect($back);
        } catch (Exception $e) {
            if ($wantsJson) {
                $this->json(array('ok' => false, 'error' => 'save_failed'), 500);
            }
            $_SESSION['flash_error'] = 'No se pudo guardar el archivo.';
            $this->redirect($back);
        }

        if ($wantsJson) {
            $share = Share::find($hash);
            $archivo = $share ? $share['archivo'] : '';
            $this->json(array(
                'ok' => true,
                'hash' => $hash,
                'share' => share_url($hash, $archivo),
            ));
        }
        $this->redirect($back);
    }

    public function deleteMargen(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        if (!$this->requireCsrf($request)) {
            $this->redirect('panel');
        }

        $empresa = $user['empresa'];
        $cliente = isset($params['cliente']) ? $params['cliente'] : '';
        $proyecto = isset($params['proyecto']) ? $params['proyecto'] : '';
        $id = isset($params['id']) ? $params['id'] : '';
        $back = 'panel/' . $cliente . '/' . $proyecto;

        try {
            Lock::run($empresa, function () use ($empresa, $id) {
                Margen::delete($empresa, $id);
            });
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'No se pudo borrar.';
        }
        $this->redirect($back);
    }

    public static function formatLocal($iso)
    {
        try {
            $dt = new DateTime($iso);
            $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
            return $dt->format('d M Y H:i');
        } catch (Exception $e) {
            return $iso;
        }
    }

    private function uploadError($code)
    {
        $map = array(
            'extension_invalida' => 'Extensión no permitida.',
            'archivo_grande' => 'El archivo supera 25 MB.',
            'upload_invalido' => 'Archivo inválido.',
        );
        return isset($map[$code]) ? $map[$code] : 'No se pudo subir el archivo.';
    }
}
