<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(__FILE__))));
define('APP_PATH', ROOT_PATH . '/app');
define('APP_NAME', 'AgentDo');
define('APP_VERSION', '1.0');
define('LOGIN_FAIL_MAX', 10);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = rtrim($scriptDir, '/');
if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
    $scriptDir = '';
}
$GLOBALS['app_base'] = $scriptDir;

spl_autoload_register(function ($class) {
    $map = array(
        'Request' => APP_PATH . '/core/request.php',
        'Router' => APP_PATH . '/core/router.php',
        'Controller' => APP_PATH . '/core/controller.php',
        'View' => APP_PATH . '/core/view.php',
        'Sqlite' => APP_PATH . '/core/sqlite.php',
        'Lock' => APP_PATH . '/core/lock.php',
        'Csrf' => APP_PATH . '/core/csrf.php',
        'Auth' => APP_PATH . '/core/auth.php',
        'Passhash' => APP_PATH . '/services/passhash.php',
        'Storage' => APP_PATH . '/services/storage.php',
        'Empresa' => APP_PATH . '/models/empresa.php',
        'Operador' => APP_PATH . '/models/operador.php',
        'Cliente' => APP_PATH . '/models/cliente.php',
        'Proyecto' => APP_PATH . '/models/proyecto.php',
        'Margen' => APP_PATH . '/models/margen.php',
        'Share' => APP_PATH . '/models/share.php',
        'HomeController' => APP_PATH . '/controllers/home.php',
        'AuthController' => APP_PATH . '/controllers/auth.php',
        'PanelController' => APP_PATH . '/controllers/panel.php',
        'ClienteController' => APP_PATH . '/controllers/cliente.php',
        'ProyectoController' => APP_PATH . '/controllers/proyecto.php',
        'ShareController' => APP_PATH . '/controllers/share.php',
        'EmpresaController' => APP_PATH . '/controllers/empresa.php',
    );
    if (isset($map[$class])) {
        require $map[$class];
    }
});

function url($path = '')
{
    $base = $GLOBALS['app_base'];
    $path = ltrim((string) $path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return $base . '/' . $path;
}

function asset($path)
{
    return url('assets/' . ltrim((string) $path, '/'));
}

function e($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function format_dt($iso)
{
    try {
        $dt = new DateTime($iso);
        $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $dt->format('d M Y H:i');
    } catch (Exception $e) {
        return (string) $iso;
    }
}

function share_url($hash, $archivo = '')
{
    $hash = (string) $hash;
    $ext = strtolower(pathinfo((string) $archivo, PATHINFO_EXTENSION));
    if ($ext !== '' && preg_match('/^[a-z0-9]+$/', $ext)) {
        return url('a/' . $hash . '.' . $ext);
    }
    return url('a/' . $hash);
}

function json_share_url($hash)
{
    return url('j/' . $hash . '.json');
}

function absolute_url($path)
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    if (strpos($path, '/') === 0) {
        return $scheme . '://' . $host . $path;
    }
    return $scheme . '://' . $host . url($path);
}
