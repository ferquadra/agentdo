<?php
class ProyectoController extends Controller
{
    public function nuevo(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        $empresa = $user['empresa'];
        $clientes = Cliente::listAll($empresa, 'nombre');
        $codesByCliente = Proyecto::codesByCliente($empresa);
        $error = '';
        $cliente = $request->input('cliente');
        $titulo = '';
        $fechaLimite = '';
        $aprobacion = 'aprobado';

        if (count($clientes) === 0) {
            $this->view('panel/proyecto_nuevo', array(
                'title' => 'Crear proyecto · ' . APP_NAME,
                'page' => 'panel',
                'user' => $user,
                'clientes' => $clientes,
                'codesByCliente' => $codesByCliente,
                'error' => 'Primero creá un cliente.',
                'cliente' => '',
                'titulo' => '',
                'fechaLimite' => '',
                'aprobacion' => 'aprobado',
                'needsCliente' => true,
            ));
            return;
        }

        if ($request->isPost()) {
            if (!$this->requireCsrf($request)) {
                $error = 'La sesión del formulario expiró. Probá de nuevo.';
            } else {
                $cliente = strtolower($request->input('cliente'));
                $titulo = $request->input('titulo');
                $fechaLimite = $request->input('fecha_limite');
                $aprobacion = $request->input('aprobacion') === 'requiere' ? 'requiere' : 'aprobado';
                $codigo = Proyecto::codigoFromTitulo($titulo);
                if (!Storage::isCode($cliente)) {
                    $error = 'Cliente inválido.';
                } elseif (trim($titulo) === '' || strlen(trim($titulo)) > 160) {
                    $error = 'Nombre inválido (1–160 caracteres).';
                } elseif (!Storage::isCode($codigo)) {
                    $error = 'El nombre tiene que dejar al menos 3 letras o números para el código.';
                } else {
                    try {
                        $ok = Lock::run($empresa, function () use ($empresa, $cliente, $codigo, $titulo, $user, $aprobacion, $fechaLimite) {
                            return Proyecto::create($empresa, $cliente, $codigo, $titulo, array(
                                'operador' => $user['usuario'],
                                'aprobacion' => $aprobacion,
                                'fecha_limite' => $fechaLimite,
                            ));
                        });
                        if (!$ok) {
                            $error = 'Ese código ya existe para este cliente. Cambiá el nombre, por ejemplo agregá un 2 al final.';
                        } else {
                            $this->redirect('panel/' . $cliente . '/' . $codigo);
                        }
                    } catch (InvalidArgumentException $e) {
                        if ($e->getMessage() === 'cliente_inexistente') {
                            $error = 'Ese cliente no existe.';
                        } elseif ($e->getMessage() === 'fecha_invalida') {
                            $error = 'Fecha límite inválida.';
                        } else {
                            $error = 'Datos inválidos.';
                        }
                    } catch (Exception $e) {
                        $error = 'No se pudo crear el proyecto.';
                    }
                }
            }
        }

        $this->view('panel/proyecto_nuevo', array(
            'title' => 'Crear proyecto · ' . APP_NAME,
            'page' => 'panel',
            'user' => $user,
            'clientes' => $clientes,
            'codesByCliente' => $codesByCliente,
            'error' => $error,
            'cliente' => $cliente,
            'titulo' => $titulo,
            'fechaLimite' => $fechaLimite,
            'aprobacion' => $aprobacion,
            'needsCliente' => false,
        ));
    }
}
