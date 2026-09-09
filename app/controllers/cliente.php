<?php
class ClienteController extends Controller
{
    public function index(Request $request, $params = array())
    {
        $user = Auth::requireLogin();
        $empresa = $user['empresa'];
        $clientes = Cliente::listAll($empresa, 'created_at');

        $this->view('panel/clientes_index', array(
            'title' => 'Clientes · ' . APP_NAME,
            'page' => 'panel',
            'user' => $user,
            'clientes' => $clientes,
            'canWrite' => Auth::canWrite(),
        ));
    }

    public function nuevo(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        $error = '';
        $codigo = '';
        $nombre = '';

        if ($request->isPost()) {
            if (!$this->requireCsrf($request)) {
                $error = 'La sesión del formulario expiró. Probá de nuevo.';
            } else {
                $codigo = strtolower($request->input('codigo'));
                $nombre = $request->input('nombre');
                if (!Storage::isCode($codigo)) {
                    $error = 'Código inválido. Solo a-z y 0-9, entre 3 y 32.';
                } elseif (trim($nombre) === '' || strlen(trim($nombre)) > 120) {
                    $error = 'Nombre inválido (1–120 caracteres).';
                } else {
                    try {
                        $ok = Lock::run($user['empresa'], function () use ($user, $codigo, $nombre) {
                            return Cliente::create($user['empresa'], $codigo, $nombre);
                        });
                        if (!$ok) {
                            $error = 'Ese código de cliente ya existe.';
                        } else {
                            $this->redirect('panel/clientes');
                        }
                    } catch (Exception $e) {
                        $error = 'No se pudo crear el cliente.';
                    }
                }
            }
        }

        $this->view('panel/cliente_nuevo', array(
            'title' => 'Crear cliente · ' . APP_NAME,
            'page' => 'panel',
            'user' => $user,
            'error' => $error,
            'codigo' => $codigo,
            'nombre' => $nombre,
        ));
    }

    public function editar(Request $request, $params = array())
    {
        $user = Auth::requireLogin();
        $empresa = $user['empresa'];
        $codigo = isset($params['codigo']) ? $params['codigo'] : '';
        $canWrite = Auth::canWrite();

        if (!Storage::isCode($codigo)) {
            $this->redirect('panel/clientes');
        }

        $cliente = Cliente::find($empresa, $codigo);
        if ($cliente === null) {
            $this->redirect('panel/clientes');
        }

        $error = '';
        $nombre = $cliente['nombre'];

        if ($request->isPost()) {
            if (!$canWrite) {
                http_response_code(403);
                $this->redirect('panel/clientes');
            }
            if (!$this->requireCsrf($request)) {
                $error = 'La sesión del formulario expiró. Probá de nuevo.';
            } else {
                $nombre = $request->input('nombre');
                try {
                    $ok = Lock::run($empresa, function () use ($empresa, $codigo, $nombre) {
                        return Cliente::update($empresa, $codigo, $nombre);
                    });
                    if (!$ok) {
                        $error = 'No se pudo guardar.';
                    } else {
                        $this->redirect('panel/clientes');
                    }
                } catch (InvalidArgumentException $e) {
                    $error = 'Nombre inválido (1–120 caracteres).';
                } catch (Exception $e) {
                    $error = 'No se pudo guardar el cliente.';
                }
            }
        }

        $this->view('panel/cliente_editar', array(
            'title' => 'Editar cliente · ' . APP_NAME,
            'page' => 'panel',
            'user' => $user,
            'cliente' => $cliente,
            'nombre' => $nombre,
            'error' => $error,
            'canWrite' => $canWrite,
        ));
    }

    public function borrar(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        $empresa = $user['empresa'];
        $codigo = isset($params['codigo']) ? $params['codigo'] : '';

        if (!$this->requireCsrf($request)) {
            $this->redirect('panel/clientes');
        }

        if (!Storage::isCode($codigo)) {
            $this->redirect('panel/clientes');
        }

        try {
            Lock::run($empresa, function () use ($empresa, $codigo) {
                Cliente::delete($empresa, $codigo);
            });
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'No se pudo borrar el cliente.';
        }

        $this->redirect('panel/clientes');
    }
}
