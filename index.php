<?php
require dirname(__FILE__) . '/app/bootstrap.php';

$request = new Request();
$router = new Router();

$router->get('/', 'HomeController@index');
$router->get('/ingresar', 'AuthController@ingresar');
$router->post('/ingresar', 'AuthController@ingresar');
$router->get('/crear-empresa', 'AuthController@crearEmpresa');
$router->post('/crear-empresa', 'AuthController@crearEmpresa');
$router->get('/crear-empresa/kit', 'AuthController@kit');
$router->get('/salir', 'AuthController@salir');

$router->get('/panel', 'PanelController@index');
$router->get('/panel/clientes', 'ClienteController@index');
$router->get('/panel/clientes/nuevo', 'ClienteController@nuevo');
$router->post('/panel/clientes/nuevo', 'ClienteController@nuevo');
$router->get('/panel/clientes/{codigo}/editar', 'ClienteController@editar');
$router->post('/panel/clientes/{codigo}/editar', 'ClienteController@editar');
$router->post('/panel/clientes/{codigo}/borrar', 'ClienteController@borrar');
$router->get('/panel/proyectos/nuevo', 'ProyectoController@nuevo');
$router->post('/panel/proyectos/nuevo', 'ProyectoController@nuevo');

$router->get('/panel/empresa', 'EmpresaController@index');
$router->post('/panel/empresa', 'EmpresaController@index');
$router->post('/panel/empresa/cerrar', 'EmpresaController@cerrar');
$router->get('/panel/empresa/passhash', 'EmpresaController@passhash');
$router->post('/panel/empresa/passhash', 'EmpresaController@passhash');
$router->post('/panel/empresa/admin/rotar', 'EmpresaController@rotarAdmin');
$router->get('/panel/empresa/operadores/nuevo', 'EmpresaController@nuevoOperador');
$router->post('/panel/empresa/operadores/nuevo', 'EmpresaController@nuevoOperador');
$router->get('/panel/empresa/operadores/{usuario}/editar', 'EmpresaController@editarOperador');
$router->post('/panel/empresa/operadores/{usuario}/editar', 'EmpresaController@editarOperador');
$router->post('/panel/empresa/operadores/{usuario}/rotar', 'EmpresaController@rotarOperador');
$router->post('/panel/empresa/operadores/{usuario}/borrar', 'EmpresaController@borrarOperador');
$router->get('/panel/empresa/kit', 'EmpresaController@kit');

$router->post('/panel/{cliente}/{proyecto}/diario', 'PanelController@saveDiario');
$router->post('/panel/{cliente}/{proyecto}/margen/archivo', 'PanelController@addArchivo');
$router->post('/panel/{cliente}/{proyecto}/margen/{id}/borrar', 'PanelController@deleteMargen');
$router->post('/panel/{cliente}/{proyecto}/margen', 'PanelController@addMargen');
$router->get('/panel/{cliente}/{proyecto}', 'PanelController@workspace');

$router->get('/j/{hash}.json', 'ShareController@showJson');
$router->get('/a/{hash}.{ext}', 'ShareController@show');
$router->get('/a/{hash}', 'ShareController@show');

$router->dispatch($request);
