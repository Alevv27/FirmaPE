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
    $accion = trim($_POST['accion'] ?? '');
    $pin = trim($_POST['pin'] ?? '');
    $alias = trim($_POST['alias'] ?? 'Certificado servidor FIRMAPE');
    if ($accion === 'enviar_codigo') {
        $response = api_request('POST', '/usuarios/' . $usuarioId . '/certificado/enviar-codigo', [
            'alias' => $alias,
        ]);
    } else {
        $response = api_request('POST', '/usuarios/' . $usuarioId . '/certificado', [
            'pin' => $pin,
            'alias' => $alias,
            'codigo' => trim($_POST['codigo'] ?? ''),
            'certificado_token' => trim($_POST['certificado_token'] ?? ''),
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $certificadoId = (int) ($_GET['id'] ?? 0);
    if ($certificadoId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Certificado no valido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $response = api_request('DELETE', '/usuarios/' . $usuarioId . '/certificado/' . $certificadoId);
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
