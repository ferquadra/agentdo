<?php
class ProyectoController extends Controller
{
    public function nuevo(Request $request, $params = array())
    {
        $user = Auth::requireWrite();
        $empresa = $user['empresa'];
        $clientes = Cliente::listAll($empresa, 'nombre');
        $error = '';
        $cliente = $request->input('cliente');
        $codigo = '';
        $titulo = '';

        if (count($clientes) === 0) {
            $this->view('panel/proyecto_nuevo', array(
                'title' => 'Crear proyecto · ' . APP_NAME,
                'page' => 'panel',
                'user' => $user,
                'clientes' => $clientes,
                'error' => 'Primero creá un cliente.',
                'cliente' => '',
                'codigo' => '',
                'titulo' => '',
                'needsCliente' => true,
            ));
            return;
        }

        if ($request->isPost()) {
            if (!$this->requireCsrf($request)) {
                $error = 'La sesión del formulario expiró. Probá de nuevo.';
            } else {
                $cliente = strtolower($request->input('cliente'));
                $codigo = strtolower($request->input('codigo'));
                $titulo = $request->input('titulo');
                if (!Storage::isCode($cliente)) {
                    $error = 'Cliente inválido.';
                } elseif (!Storage::isCode($codigo)) {
                    $error = 'Código de proyecto inválido. Solo a-z y 0-9, entre 3 y 32.';
                } elseif (trim($titulo) === '' || strlen(trim($titulo)) > 160) {
                    $error = 'Título inválido (1–160 caracteres).';
                } else {
                    try {
                        $ok = Lock::run($empresa, function () use ($empresa, $cliente, $codigo, $titulo) {
                            return Proyecto::create($empresa, $cliente, $codigo, $titulo);
                        });
                        if (!$ok) {
                            $error = 'Ese código de proyecto ya existe para el cliente.';
                        } else {
                            $this->redirect('panel/' . $cliente . '/' . $codigo);
                        }
                    } catch (InvalidArgumentException $e) {
                        if ($e->getMessage() === 'cliente_inexistente') {
                            $error = 'Ese cliente no existe.';
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
            'error' => $error,
            'cliente' => $cliente,
            'codigo' => $codigo,
            'titulo' => $titulo,
            'needsCliente' => false,
        ));
    }
}
