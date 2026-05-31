<?php
session_start();
require_once 'includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
    exit;
}

$usuario = current_user();
$archivo = $_FILES['foto_perfil'] ?? null;

if (!$archivo || !isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Selecciona una foto valida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($archivo['size'] ?? 0) > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'La foto no debe superar los 2 MB.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$mimePermitidos = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($archivo['tmp_name']);

if (!isset($mimePermitidos[$mime])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Formato no permitido. Usa JPG, PNG o WEBP.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'perfiles';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo preparar la carpeta de perfiles.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = $mimePermitidos[$mime];
$nombreArchivo = 'perfil_' . (int) $usuario['id'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$destino = $uploadDir . DIRECTORY_SEPARATOR . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la foto.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rutaPublica = 'uploads/perfiles/' . $nombreArchivo;
$response = api_request('PATCH', '/usuarios/' . (int) $usuario['id'], [
    'foto_perfil' => $rutaPublica,
]);

if (!$response['ok']) {
    http_response_code($response['status'] ?: 400);
    echo json_encode([
        'ok' => false,
        'error' => $response['error'] ?: 'No se pudo actualizar la foto de perfil.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['auth']['usuario'] = $response['data']['usuario'];
$baseWeb = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$fotoUrl = ($baseWeb === '' ? '' : $baseWeb) . '/' . $rutaPublica;

echo json_encode([
    'ok' => true,
    'mensaje' => 'Foto de perfil actualizada correctamente.',
    'fotoPerfil' => $rutaPublica,
    'fotoPerfilUrl' => $fotoUrl,
    'usuario' => $_SESSION['auth']['usuario'],
], JSON_UNESCAPED_UNICODE);
