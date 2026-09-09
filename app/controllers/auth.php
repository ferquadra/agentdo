<?php
class AuthController extends Controller
{
    public function ingresar(Request $request)
    {
        if (Auth::check()) {
            $this->redirect('panel');
        }

        $error = '';
        $empresa = $request->input('empresa');
        $usuario = $request->input('usuario');

        if ($request->isPost()) {
            if (!Csrf::check($request->input('csrf'))) {
                $error = 'La sesión del formulario expiró. Probá de nuevo.';
            } else {
                $result = Auth::attempt(
                    strtolower($request->input('empresa')),
                    strtolower($request->input('usuario')),
                    $request->input('passhash')
                );
                if ($result['ok']) {
                    unset($_SESSION['wizard']);
                    $this->redirect('panel');
                }
                $error = $this->authError($result['error']);
                $empresa = strtolower($request->input('empresa'));
                $usuario = strtolower($request->input('usuario'));
            }
        }

        $this->view('auth/ingresar', array(
            'title' => 'Ingresar · ' . APP_NAME,
            'page' => 'ingresar',
            'error' => $error,
            'empresa' => $empresa,
            'usuario' => $usuario,
        ));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $this->redirect('');
    }

    public function crearEmpresa(Request $request)
    {
        if (Auth::check() && empty($_SESSION['wizard'])) {
            $this->redirect('panel');
        }

        if ($request->isPost()) {
            if (!Csrf::check($request->input('csrf'))) {
                $this->renderCrear('La sesión del formulario expiró. Probá de nuevo.');
                return;
            }
            $action = $request->input('action');
            if ($action === 'codigo') {
                $this->altaCodigo($request);
                return;
            }
            if ($action === 'guardar_admin') {
                $this->confirmarAdmin($request);
                return;
            }
            if ($action === 'operador') {
                $this->altaOperador($request);
                return;
            }
            if ($action === 'entrar') {
                $this->entrarAdmin();
                return;
            }
        }

        $this->renderCrear('');
    }

    public function kit(Request $request)
    {
        if (empty($_SESSION['wizard']) || !is_array($_SESSION['wizard'])) {
            http_response_code(404);
            echo 'kit no disponible';
            return;
        }

        $kind = $request->input('kind');
        $wiz = $_SESSION['wizard'];
        $empresa = isset($wiz['empresa']) ? $wiz['empresa'] : '';
        $usuario = 'admin';
        $pass = '';
        $filename = 'empresa.txt';

        if ($kind === 'operador') {
            if (empty($wiz['op_pass']) || empty($wiz['op_usuario'])) {
                http_response_code(404);
                echo 'kit no disponible';
                return;
            }
            $usuario = $wiz['op_usuario'];
            $pass = $wiz['op_pass'];
            $filename = 'operador.txt';
        } else {
            if (empty($wiz['admin_pass'])) {
                http_response_code(404);
                echo 'kit no disponible';
                return;
            }
            $pass = $wiz['admin_pass'];
        }

        $body = "agentdo\r\n";
        $body .= 'empresa: ' . $empresa . "\r\n";
        $body .= 'usuario: ' . $usuario . "\r\n";
        $body .= 'passhash: ' . $pass . "\r\n";
        $body .= 'ingresar: ' . $this->absoluteUrl('ingresar') . "\r\n";

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $body;
    }

    private function altaCodigo(Request $request)
    {
        $codigo = strtolower($request->input('codigo'));
        if (!Storage::isCode($codigo)) {
            $this->renderCrear('Código inválido. Solo a-z y 0-9, entre 3 y 32 caracteres.', $codigo);
            return;
        }

        try {
            $created = Lock::run($codigo, function () use ($codigo) {
                if (Empresa::exists($codigo)) {
                    return false;
                }
                Empresa::create($codigo);
                $plain = Passhash::generate();
                Operador::create($codigo, array(
                    'usuario' => 'admin',
                    'nombre' => 'Administrador',
                    'email' => 'admin@' . $codigo . '.local',
                    'telefono' => '',
                    'permiso' => 'rw',
                    'pass_hash' => Passhash::store($plain),
                ));
                $_SESSION['wizard'] = array(
                    'step' => 2,
                    'empresa' => $codigo,
                    'admin_pass' => $plain,
                    'admin_saved' => false,
                    'op_pass' => '',
                    'op_usuario' => '',
                );
                return true;
            });
        } catch (Exception $e) {
            $this->renderCrear('No se pudo crear la empresa. Reintentá.', $codigo);
            return;
        }

        if (!$created) {
            $this->renderCrear('Esa empresa ya existe. Elegí otro código.', $codigo);
            return;
        }

        $this->redirect('crear-empresa');
    }

    private function confirmarAdmin(Request $request)
    {
        if (empty($_SESSION['wizard']) || (int) $_SESSION['wizard']['step'] !== 2) {
            $this->redirect('crear-empresa');
        }
        if ($request->input('guardado') !== '1') {
            $this->renderCrear('Confirmá que ya guardaste el passhash de admin.');
            return;
        }
        $_SESSION['wizard']['step'] = 3;
        $_SESSION['wizard']['admin_saved'] = true;
        $this->redirect('crear-empresa');
    }

    private function altaOperador(Request $request)
    {
        if (empty($_SESSION['wizard']) || (int) $_SESSION['wizard']['step'] !== 3) {
            $this->redirect('crear-empresa');
        }

        $empresa = $_SESSION['wizard']['empresa'];
        $nombre = $request->input('nombre');
        $usuario = strtolower($request->input('usuario'));
        $email = $request->input('email');
        $telefono = $request->input('telefono');
        $permiso = $request->input('permiso') === 'ro' ? 'ro' : 'rw';

        $error = $this->validarOperador($nombre, $usuario, $email, $telefono);
        if ($error !== '') {
            $this->renderCrear($error, '', array(
                'nombre' => $nombre,
                'usuario' => $usuario,
                'email' => $email,
                'telefono' => $telefono,
                'permiso' => $permiso,
            ));
            return;
        }

        $plain = Passhash::generate();
        try {
            Lock::run($empresa, function () use ($empresa, $nombre, $usuario, $email, $telefono, $permiso, $plain) {
                if (Operador::find($empresa, $usuario) !== null) {
                    throw new RuntimeException('usuario_existe');
                }
                Operador::create($empresa, array(
                    'usuario' => $usuario,
                    'nombre' => $nombre,
                    'email' => $email,
                    'telefono' => $telefono,
                    'permiso' => $permiso,
                    'pass_hash' => Passhash::store($plain),
                ));
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'usuario_existe') {
                $this->renderCrear('Ese usuario ya existe en la empresa.', '', array(
                    'nombre' => $nombre,
                    'usuario' => $usuario,
                    'email' => $email,
                    'telefono' => $telefono,
                    'permiso' => $permiso,
                ));
                return;
            }
            $this->renderCrear('No se pudo crear el operador. Reintentá.');
            return;
        }

        $_SESSION['wizard']['step'] = 4;
        $_SESSION['wizard']['op_pass'] = $plain;
        $_SESSION['wizard']['op_usuario'] = $usuario;
        $this->redirect('crear-empresa');
    }

    private function entrarAdmin()
    {
        if (empty($_SESSION['wizard']) || (int) $_SESSION['wizard']['step'] !== 4) {
            $this->redirect('crear-empresa');
        }
        if (empty($_SESSION['wizard']['op_guardado'])) {
            if (!isset($_POST['guardado']) || $_POST['guardado'] !== '1') {
                $this->renderCrear('Confirmá que ya guardaste el passhash del operador.');
                return;
            }
        }
        $empresa = $_SESSION['wizard']['empresa'];
        $admin = Operador::find($empresa, 'admin');
        if ($admin === null) {
            $this->renderCrear('No se encontró el admin. Reintentá el alta.');
            return;
        }
        Auth::login($empresa, $admin);
        unset($_SESSION['wizard']);
        $this->redirect('panel');
    }

    private function validarOperador($nombre, $usuario, $email, $telefono)
    {
        if (strlen($nombre) < 2 || strlen($nombre) > 80) {
            return 'El nombre tiene que tener entre 2 y 80 caracteres.';
        }
        if (!Storage::isCode($usuario)) {
            return 'Usuario inválido. Solo a-z y 0-9, entre 3 y 32.';
        }
        if ($usuario === 'admin') {
            return 'El usuario admin está reservado.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email inválido.';
        }
        if ($telefono !== '' && strlen($telefono) > 40) {
            return 'Teléfono demasiado largo.';
        }
        return '';
    }

    private function renderCrear($error, $codigo = '', $op = array())
    {
        $step = 1;
        $wiz = isset($_SESSION['wizard']) && is_array($_SESSION['wizard']) ? $_SESSION['wizard'] : null;
        if ($wiz) {
            $step = (int) $wiz['step'];
        }
        if ($codigo === '' && $wiz) {
            $codigo = $wiz['empresa'];
        }

        $defaults = array(
            'nombre' => '',
            'usuario' => '',
            'email' => '',
            'telefono' => '',
            'permiso' => 'rw',
        );
        $op = array_merge($defaults, $op);

        $template = 'auth/crear_empresa';
        if ($step === 2) {
            $template = 'auth/crear_admin';
        } elseif ($step === 3) {
            $template = 'auth/crear_operador';
        } elseif ($step === 4) {
            $template = 'auth/crear_operador_hash';
        }

        $this->view($template, array(
            'title' => 'Crear empresa · ' . APP_NAME,
            'page' => 'crear-empresa',
            'error' => $error,
            'codigo' => $codigo,
            'wizard' => $wiz,
            'op' => $op,
        ));
    }

    private function authError($code)
    {
        if ($code === 'empresa_locked') {
            return 'Esta empresa está bloqueada: se alcanzaron ' . LOGIN_FAIL_MAX . ' intentos fallidos.';
        }
        return 'Empresa, usuario o passhash inválidos.';
    }

    private function absoluteUrl($path)
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        return $scheme . '://' . $host . url($path);
    }
}
