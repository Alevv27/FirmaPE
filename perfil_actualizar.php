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
$nuevoEmail = trim(strtolower($_POST['email'] ?? ''));

if (!filter_var($nuevoEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Correo invalido.']);
    exit;
}

$response = api_request('PATCH', '/usuarios/' . (int) $usuario['id'], [
    'email' => $nuevoEmail,
]);

if (!$response['ok']) {
    http_response_code($response['status'] ?: 400);
    echo json_encode([
        'ok' => false,
        'error' => $response['error'] ?: 'No se pudo actualizar el correo.',
    ]);
    exit;
}

$_SESSION['auth']['usuario'] = $response['data']['usuario'];

echo json_encode([
    'ok' => true,
    'mensaje' => 'Perfil actualizado correctamente.',
    'usuario' => $_SESSION['auth']['usuario'],
], JSON_UNESCAPED_UNICODE);
