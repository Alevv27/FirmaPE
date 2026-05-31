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
$actual = $_POST['password_actual'] ?? '';
$nueva = $_POST['password_nueva'] ?? '';
$confirmar = $_POST['password_confirmar'] ?? '';

if ($nueva !== $confirmar) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$login = api_request('POST', '/auth/login', [
    'dni' => $usuario['dni'] ?? '',
    'password' => $actual,
]);

if (!$login['ok']) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'La contraseña actual no es correcta.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$response = api_request('PATCH', '/usuarios/' . (int) $usuario['id'], [
    'password' => $nueva,
]);

if (!$response['ok']) {
    http_response_code($response['status'] ?: 400);
    echo json_encode([
        'ok' => false,
        'error' => $response['error'] ?: 'No se pudo actualizar la contraseña.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'mensaje' => 'Contraseña actualizada correctamente.',
], JSON_UNESCAPED_UNICODE);
