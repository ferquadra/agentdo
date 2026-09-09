<?php

class EmpresaController extends Controller

{

    public function index(Request $request, $params = array())

    {

        $user = Auth::requireLogin();

        $empresa = $user['empresa'];



        if (Auth::isEmpresaAdminUnlocked($empresa)) {

            if ($this->hasPendingPasshash($empresa)) {

                $this->redirect('panel/empresa/passhash');

            }

            $this->admin($request, $params);

            return;

        }



        $error = '';

        if ($request->isPost()) {

            if (!$this->requireCsrf($request)) {

                $error = 'La sesión del formulario expiró. Probá de nuevo.';

            } else {

                $result = Auth::unlockEmpresaAdmin($empresa, $request->input('passhash'));

                if ($result['ok']) {

                    $this->redirect('panel/empresa');

                }

                $error = $this->unlockError($result['error']);

            }

        }



        $info = Empresa::meta($empresa);

        $this->view('panel/empresa_desbloquear', array(

            'title' => 'Administrar empresa · ' . APP_NAME,

            'page' => 'panel',

            'user' => $user,

            'empresa' => $info,

            'error' => $error,

        ));

    }



    public function passhash(Request $request, $params = array())

    {

        $user = Auth::requireEmpresaAdmin($empresa = Auth::empresa());

        $info = Empresa::meta($empresa);



        if (!$this->hasPendingPasshash($empresa)) {

            $this->redirect('panel/empresa');

        }



        $rotate = $_SESSION['rotate_flash'];

        $error = '';



        if ($request->isPost()) {

            if (!$this->requireCsrf($request)) {

                $error = 'La sesión del formulario expiró. Probá de nuevo.';

            } elseif (!isset($_POST['guardado']) || $_POST['guardado'] !== '1') {

                $error = 'Confirmá que ya guardaste el passhash.';

            } else {

                unset($_SESSION['rotate_flash']);

                $this->redirect('panel/empresa');

            }

        }



        $this->view('panel/empresa_passhash', array(

            'title' => 'Guardar passhash · ' . APP_NAME,

            'page' => 'panel',

            'user' => $user,

            'empresa' => $info,

            'rotate' => $rotate,

            'error' => $error,

        ));

    }



    public function cerrar(Request $request, $params = array())

    {

        $user = Auth::requireLogin();

        if (!$this->requireCsrf($request)) {

            $this->redirect('panel/empresa');

        }

        if ($this->hasPendingPasshash($user['empresa'])) {

            $_SESSION['flash_error'] = 'Guardá el passhash nuevo antes de cerrar la administración.';

            $this->redirect('panel/empresa/passhash');

        }

        Auth::lockEmpresaAdmin($user['empresa']);

        $this->redirect('panel');

    }



    public function rotarAdmin(Request $request, $params = array())

    {

        $this->rotarPasshash($request, 'admin');

    }



    public function rotarOperador(Request $request, $params = array())

    {

        $usuario = isset($params['usuario']) ? $params['usuario'] : '';

        $this->rotarPasshash($request, $usuario);

    }



    public function nuevoOperador(Request $request, $params = array())
    {
        $user = Auth::requireEmpresaAdmin($empresa = Auth::empresa());

        if ($this->hasPendingPasshash($empresa)) {
            $this->redirect('panel/empresa/passhash');
        }

        $nombre = '';
        $usuario = '';
        $email = '';
        $telefono = '';
        $permiso = 'rw';
        $error = '';

        if ($request->isPost()) {
            if (!$this->requireCsrf($request)) {
                $error = 'La sesión del formulario expiró. Probá de nuevo.';
            } else {
                $nombre = trim($request->input('nombre'));
                $usuario = strtolower(trim($request->input('usuario')));
                $email = trim($request->input('email'));
                $telefono = trim($request->input('telefono'));
                $permiso = $request->input('permiso') === 'ro' ? 'ro' : 'rw';

                $error = $this->validarOperadorAlta($nombre, $usuario, $email, $telefono);
                if ($error === '') {
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
                        $_SESSION['rotate_flash'] = array(
                            'usuario' => $usuario,
                            'passhash' => $plain,
                            'empresa' => $empresa,
                        );
                        $this->redirect('panel/empresa/passhash');
                    } catch (RuntimeException $e) {
                        if ($e->getMessage() === 'usuario_existe') {
                            $error = 'Ese usuario ya existe en la empresa.';
                        } else {
                            $error = 'No se pudo crear el operador.';
                        }
                    } catch (Exception $e) {
                        $error = 'No se pudo crear el operador.';
                    }
                }
            }
        }

        $this->view('panel/empresa_operador_nuevo', array(
            'title' => 'Añadir operador · ' . APP_NAME,
            'page' => 'panel',
            'user' => $user,
            'empresa' => Empresa::meta($empresa),
            'nombre' => $nombre,
            'usuario' => $usuario,
            'email' => $email,
            'telefono' => $telefono,
            'permiso' => $permiso,
            'error' => $error,
        ));
    }

    public function editarOperador(Request $request, $params = array())

    {

        $user = Auth::requireEmpresaAdmin($empresa = Auth::empresa());

        $usuario = isset($params['usuario']) ? $params['usuario'] : '';



        if (!Storage::isCode($usuario)) {

            $this->redirect('panel/empresa');

        }



        if ($this->hasPendingPasshash($empresa)) {

            $this->redirect('panel/empresa/passhash');

        }



        $operador = Operador::find($empresa, $usuario);

        if ($operador === null) {

            $this->redirect('panel/empresa');

        }



        $nombre = $operador['nombre'];

        $email = $operador['email'];

        $telefono = $operador['telefono'] !== null ? $operador['telefono'] : '';

        $permiso = $operador['permiso'];

        $error = '';



        if ($request->isPost()) {

            if (!$this->requireCsrf($request)) {

                $error = 'La sesión del formulario expiró. Probá de nuevo.';

            } else {

                $nombre = trim($request->input('nombre'));

                $email = trim($request->input('email'));

                $telefono = trim($request->input('telefono'));

                $permiso = $usuario === 'admin' ? 'rw' : ($request->input('permiso') === 'ro' ? 'ro' : 'rw');



                $error = $this->validarOperadorDatos($nombre, $email, $telefono);

                if ($error === '') {

                    try {

                        Lock::run($empresa, function () use ($empresa, $usuario, $nombre, $email, $telefono, $permiso) {

                            Operador::update($empresa, $usuario, array(

                                'nombre' => $nombre,

                                'email' => $email,

                                'telefono' => $telefono,

                                'permiso' => $permiso,

                            ));

                        });

                        if ($user['usuario'] === $usuario) {

                            $_SESSION['auth']['nombre'] = $nombre;

                            $_SESSION['auth']['permiso'] = $permiso;

                        }

                        $this->redirect('panel/empresa');

                    } catch (Exception $e) {

                        $error = 'No se pudo guardar el operador.';

                    }

                }

            }

        }



        $this->view('panel/empresa_operador_editar', array(

            'title' => 'Editar operador · ' . APP_NAME,

            'page' => 'panel',

            'user' => $user,

            'empresa' => Empresa::meta($empresa),

            'operador' => $operador,

            'nombre' => $nombre,

            'email' => $email,

            'telefono' => $telefono,

            'permiso' => $permiso,

            'error' => $error,

        ));

    }



    public function borrarOperador(Request $request, $params = array())

    {

        $user = Auth::requireEmpresaAdmin($userEmpresa = Auth::empresa());

        $usuario = isset($params['usuario']) ? $params['usuario'] : '';



        if (!$this->requireCsrf($request) || !Storage::isCode($usuario)) {

            $this->redirect('panel/empresa');

        }



        if ($this->hasPendingPasshash($userEmpresa)) {

            $_SESSION['flash_error'] = 'Guardá el passhash nuevo antes de otras acciones.';

            $this->redirect('panel/empresa/passhash');

        }



        if ($usuario === $user['usuario']) {

            $_SESSION['flash_error'] = 'No podés borrarte a vos mismo mientras administrás.';

            $this->redirect('panel/empresa');

        }



        try {

            Lock::run($userEmpresa, function () use ($userEmpresa, $usuario) {

                Operador::delete($userEmpresa, $usuario);

            });

        } catch (InvalidArgumentException $e) {

            $_SESSION['flash_error'] = 'No se puede borrar al usuario admin.';

        } catch (Exception $e) {

            $_SESSION['flash_error'] = 'No se pudo borrar el operador.';

        }

        $this->redirect('panel/empresa');

    }



    public function kit(Request $request, $params = array())

    {

        Auth::requireEmpresaAdmin($empresa = Auth::empresa());

        $usuario = $request->input('usuario');

        if (!Storage::isCode($usuario)) {

            http_response_code(404);

            echo 'kit no disponible';

            return;

        }



        $flash = isset($_SESSION['rotate_flash']) ? $_SESSION['rotate_flash'] : null;

        if ($flash === null || $flash['usuario'] !== $usuario || $flash['empresa'] !== $empresa) {

            http_response_code(404);

            echo 'kit no disponible';

            return;

        }



        $body = "agentdo\r\n";

        $body .= 'empresa: ' . $empresa . "\r\n";

        $body .= 'usuario: ' . $usuario . "\r\n";

        $body .= 'passhash: ' . $flash['passhash'] . "\r\n";

        $body .= 'ingresar: ' . $this->absoluteUrl('ingresar') . "\r\n";



        $filename = $usuario === 'admin' ? 'empresa.txt' : 'operador.txt';

        header('Content-Type: text/plain; charset=utf-8');

        header('Content-Disposition: attachment; filename="' . $filename . '"');

        header('Cache-Control: no-store');

        echo $body;

    }



    private function admin(Request $request, $params = array())

    {

        $user = Auth::requireEmpresaAdmin($empresa = Auth::empresa());

        $info = Empresa::meta($empresa);

        $operadores = Operador::listAll($empresa);



        $flash = '';

        if (!empty($_SESSION['flash_error'])) {

            $flash = $_SESSION['flash_error'];

            unset($_SESSION['flash_error']);

        }



        $this->view('panel/empresa_admin', array(

            'title' => $empresa . ' · Administrar · ' . APP_NAME,

            'page' => 'panel',

            'user' => $user,

            'empresa' => $info,

            'operadores' => $operadores,

            'flash' => $flash,

        ));

    }



    private function rotarPasshash(Request $request, $usuario)

    {

        Auth::requireEmpresaAdmin($userEmpresa = Auth::empresa());



        if (!$this->requireCsrf($request) || !Storage::isCode($usuario)) {

            $this->redirect('panel/empresa');

        }



        try {

            $plain = Lock::run($userEmpresa, function () use ($userEmpresa, $usuario) {

                return Operador::rotatePasshash($userEmpresa, $usuario);

            });

            if ($plain === false) {

                $_SESSION['flash_error'] = 'No se pudo rotar el passhash.';

                $this->redirect('panel/empresa');

            }

            $_SESSION['rotate_flash'] = array(

                'usuario' => $usuario,

                'passhash' => $plain,

                'empresa' => $userEmpresa,

            );

        } catch (Exception $e) {

            $_SESSION['flash_error'] = 'No se pudo rotar el passhash.';

            $this->redirect('panel/empresa');

        }

        $this->redirect('panel/empresa/passhash');

    }



    private function hasPendingPasshash($empresa)

    {

        return !empty($_SESSION['rotate_flash'])

            && is_array($_SESSION['rotate_flash'])

            && isset($_SESSION['rotate_flash']['empresa'])

            && $_SESSION['rotate_flash']['empresa'] === $empresa

            && !empty($_SESSION['rotate_flash']['passhash']);

    }



    private function validarOperadorDatos($nombre, $email, $telefono)
    {
        if (strlen($nombre) < 2 || strlen($nombre) > 80) {
            return 'El nombre tiene que tener entre 2 y 80 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email inválido.';
        }
        if ($telefono !== '' && strlen($telefono) > 40) {
            return 'Teléfono demasiado largo.';
        }
        return '';
    }

    private function validarOperadorAlta($nombre, $usuario, $email, $telefono)
    {
        $error = $this->validarOperadorDatos($nombre, $email, $telefono);
        if ($error !== '') {
            return $error;
        }
        if (!Storage::isCode($usuario)) {
            return 'Usuario inválido. Solo a-z y 0-9, entre 3 y 32.';
        }
        if ($usuario === 'admin') {
            return 'El usuario admin está reservado.';
        }
        return '';
    }



    private function unlockError($code)

    {

        if ($code === 'empresa_locked') {

            return 'Empresa bloqueada: demasiados intentos fallidos.';

        }

        return 'Passhash de empresa inválido. Es el del usuario admin.';

    }



    private function absoluteUrl($path)

    {

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        $scheme = $https ? 'https' : 'http';

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        return $scheme . '://' . $host . url($path);

    }

}

