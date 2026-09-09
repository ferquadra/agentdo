<?php
class View
{
    public static function render($template, $data = array())
    {
        $file = APP_PATH . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vista no encontrada: ' . $template);
        }

        if (!isset($data['title'])) {
            $data['title'] = APP_NAME;
        }
        if (!isset($data['page'])) {
            $data['page'] = '';
        }

        extract($data);
        ob_start();
        require $file;
        $body = ob_get_clean();

        require APP_PATH . '/views/layouts/default.php';
    }
}
