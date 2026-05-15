<?php

/*
 * Cliente para el backend Flask.
 *
 * En Render puedes configurar FIRMAPE_API_URL con el valor:
 * https://app-tienda-massiel-backen-prd.onrender.com/api
 *
 * En local puedes usar:
 * http://127.0.0.1:5000/api
 */
function api_base_url(): string
{
    $envBase = getenv('FIRMAPE_API_URL');
    if ($envBase) {
        return rtrim($envBase, '/');
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $isLocal = str_starts_with($host, 'localhost')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '::1');

    $base = $isLocal
        ? 'http://127.0.0.1:5000/api'
        : 'https://app-tienda-massiel-backen-prd.onrender.com/api';

    return rtrim($base, '/');
}

function api_request(string $method, string $path, ?array $payload = null): array
{
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'La extension curl de PHP no esta habilitada',
        ];
    }

    $url = api_base_url() . '/' . ltrim($path, '/');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => null,
            'error' => 'El servicio no esta disponible en este momento. Intentalo nuevamente en unos minutos.',
            'technical_error' => $curlError ?: 'No se pudo conectar con el backend',
        ];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => 'Respuesta invalida del backend',
            'raw' => $raw,
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300 && ($data['ok'] ?? true),
        'status' => $status,
        'data' => $data,
        'error' => $data['error'] ?? null,
    ];
}

/*
 * Compatibilidad con pantallas antiguas.
 * Este front ya no abre conexiones directas a base de datos; todo debe pasar
 * por el backend Flask usando api_request().
 */
$conexion = null;
?>
