<?php
session_start();
require_once 'includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$usuario = current_user();
$usuarioId = (int) ($usuario['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = api_request('GET', '/usuarios/' . $usuarioId . '/certificado');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $alias = trim($_POST['alias'] ?? 'Certificado servidor FIRMAPE');
    $response = api_request('POST', '/usuarios/' . $usuarioId . '/certificado', [
        'pin' => $pin,
        'alias' => $alias,
    ]);
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
    exit;
}

if (!$response['ok']) {
    http_response_code($response['status'] ?: 400);
    echo json_encode([
        'ok' => false,
        'error' => $response['error'] ?: 'No se pudo procesar el certificado.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($response['data'], JSON_UNESCAPED_UNICODE);
