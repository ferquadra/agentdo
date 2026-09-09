<?php
class ShareController extends Controller
{
    public function show(Request $request, $params = array())
    {
        $hash = isset($params['hash']) ? $params['hash'] : '';
        $ext = isset($params['ext']) ? strtolower($params['ext']) : '';

        if (!preg_match('/^[a-z0-9]{40}$/', $hash)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $share = Share::find($hash);
        if ($share === null) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $realExt = strtolower(pathinfo($share['archivo'], PATHINFO_EXTENSION));
        if ($ext !== '' && $ext !== $realExt) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $path = Storage::margenDir(
            $share['empresa'],
            $share['cliente'],
            $share['proyecto'],
            $hash
        ) . '/' . $share['archivo'];

        if (!is_file($path)) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $real = realpath($path);
        $base = realpath(Storage::margenDir(
            $share['empresa'],
            $share['cliente'],
            $share['proyecto'],
            $hash
        ));
        if ($real === false || $base === false || strpos(str_replace('\\', '/', $real), str_replace('\\', '/', $base) . '/') !== 0) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $mime = self::mimeFor($share['archivo']);
        $filename = basename($share['archivo']);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    public function showJson(Request $request, $params = array())
    {
        $hash = isset($params['hash']) ? $params['hash'] : '';
        $share = Share::findJson($hash);
        if ($share === null) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => 'not_found'));
            return;
        }

        $payload = Proyecto::publicExport(
            $share['empresa'],
            $share['cliente'],
            $share['proyecto'],
            $hash
        );
        if ($payload === null) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => 'not_found'));
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    private static function mimeFor($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = array(
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'wmv' => 'video/x-ms-wmv',
        );
        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }
}
