<?php
class Controller
{
    protected function view($template, $data = array())
    {
        View::render($template, $data);
    }

    protected function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data);
        exit;
    }

    protected function requireCsrf(Request $request)
    {
        if (!Csrf::check($request->input('csrf'))) {
            return false;
        }
        return true;
    }
}
