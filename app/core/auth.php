<?php
class Auth
{
    public static function user()
    {
        if (empty($_SESSION['auth']) || !is_array($_SESSION['auth'])) {
            return null;
        }
        return $_SESSION['auth'];
    }

    public static function check()
    {
        return self::user() !== null;
    }

    public static function login($empresa, $operador)
    {
        $_SESSION['auth'] = array(
            'empresa' => $empresa,
            'usuario' => $operador['usuario'],
            'nombre' => $operador['nombre'],
            'permiso' => $operador['permiso'],
        );
    }

    public static function logout()
    {
        unset($_SESSION['auth']);
        unset($_SESSION['empresa_admin']);
    }

    public static function isEmpresaAdminUnlocked($empresa)
    {
        return isset($_SESSION['empresa_admin'])
            && is_array($_SESSION['empresa_admin'])
            && !empty($_SESSION['empresa_admin'][$empresa]);
    }

    public static function lockEmpresaAdmin($empresa)
    {
        if (isset($_SESSION['empresa_admin'][$empresa])) {
            unset($_SESSION['empresa_admin'][$empresa]);
        }
    }

    public static function unlockEmpresaAdmin($empresa, $passhash)
    {
        if (!Storage::isCode($empresa) || !is_string($passhash) || $passhash === '') {
            return array('ok' => false, 'error' => 'auth_invalid');
        }

        return Lock::run($empresa, function () use ($empresa, $passhash) {
            $meta = Empresa::meta($empresa);
            if ($meta === null) {
                return array('ok' => false, 'error' => 'auth_invalid');
            }
            if ((int) $meta['locked'] === 1) {
                return array('ok' => false, 'error' => 'empresa_locked');
            }

            $admin = Operador::find($empresa, 'admin');
            if ($admin === null || !Passhash::verify($passhash, $admin['pass_hash'])) {
                $after = Empresa::recordFail($empresa);
                if ((int) $after['locked'] === 1) {
                    return array('ok' => false, 'error' => 'empresa_locked');
                }
                return array('ok' => false, 'error' => 'auth_invalid');
            }

            if (!isset($_SESSION['empresa_admin']) || !is_array($_SESSION['empresa_admin'])) {
                $_SESSION['empresa_admin'] = array();
            }
            $_SESSION['empresa_admin'][$empresa] = time();
            return array('ok' => true, 'error' => '');
        });
    }

    public static function requireEmpresaAdmin($empresa)
    {
        $user = self::requireLogin();
        if ($user['empresa'] !== $empresa) {
            header('Location: ' . url('panel'));
            exit;
        }
        if (!self::isEmpresaAdminUnlocked($empresa)) {
            header('Location: ' . url('panel/empresa'));
            exit;
        }
        return $user;
    }

    public static function requireLogin()
    {
        if (!self::check()) {
            header('Location: ' . url('ingresar'));
            exit;
        }
        return self::user();
    }

    public static function canWrite()
    {
        $user = self::user();
        return $user !== null && isset($user['permiso']) && $user['permiso'] === 'rw';
    }

    public static function requireWrite()
    {
        $user = self::requireLogin();
        if (!self::canWrite()) {
            http_response_code(403);
            View::render('home/404', array(
                'title' => 'Sin permiso',
                'page' => '403',
            ));
            exit;
        }
        return $user;
    }

    public static function empresa()
    {
        $user = self::user();
        return $user ? $user['empresa'] : null;
    }

    public static function attempt($empresa, $usuario, $passhash)
    {
        if (!Storage::isCode($empresa) || !Storage::isCode($usuario) || !is_string($passhash) || $passhash === '') {
            return array('ok' => false, 'error' => 'auth_invalid');
        }

        if (!Empresa::exists($empresa)) {
            return array('ok' => false, 'error' => 'auth_invalid');
        }

        return Lock::run($empresa, function () use ($empresa, $usuario, $passhash) {
            $meta = Empresa::meta($empresa);
            if ($meta === null) {
                return array('ok' => false, 'error' => 'auth_invalid');
            }
            if ((int) $meta['locked'] === 1) {
                return array('ok' => false, 'error' => 'empresa_locked');
            }

            $operador = Operador::find($empresa, $usuario);
            if ($operador === null || !Passhash::verify($passhash, $operador['pass_hash'])) {
                $after = Empresa::recordFail($empresa);
                if ((int) $after['locked'] === 1) {
                    return array('ok' => false, 'error' => 'empresa_locked');
                }
                return array('ok' => false, 'error' => 'auth_invalid');
            }

            self::login($empresa, $operador);
            return array('ok' => true, 'error' => '', 'user' => $operador);
        });
    }
}
