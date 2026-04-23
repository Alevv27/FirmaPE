<?php

declare(strict_types=1);

if (!defined('API_BASE_URL')) {
    $apiUrl = getenv('FIRMAPE_API_URL') ?: 'http://127.0.0.1:5000/api';
    define('API_BASE_URL', rtrim($apiUrl, '/'));
}

function api_request(string $method, string $path, ?array $payload = null): array
{
    $url = API_BASE_URL . '/' . ltrim($path, '/');
    $headers = ['Accept: application/json'];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $error !== '') {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'No se pudo conectar con el backend Flask.',
        ];
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => 'El backend devolvio una respuesta invalida.',
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300 && (($data['ok'] ?? true) === true),
        'status' => $status,
        'data' => $data,
        'error' => $data['error'] ?? null,
    ];
}
