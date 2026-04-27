<?php

/*
 * Cliente para el backend Flask.
 *
 * En Render configura FIRMAPE_API_URL con el valor:
 * https://app-tienda-massiel-backen-prd.onrender.com/api
 *
 * En local puedes usar:
 * http://127.0.0.1:5000/api
 */
function api_base_url(): string
{
    $base = getenv('FIRMAPE_API_URL') ?: 'https://app-tienda-massiel-backen-prd.onrender.com/api';
    return rtrim($base, '/');
}

function api_request(string $method, string $path, ?array $payload = null): array
{
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
            'error' => $curlError ?: 'No se pudo conectar con el backend',
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
 * Conexion MySQL legacy usada solo por el flujo de documentos de este front.
 * La autenticacion, usuarios, perfiles y empresas ahora salen del backend Flask.
 */
$conexion = new mysqli(
    getenv('MYSQL_HOST') ?: 'sql10.freesqldatabase.com',
    getenv('MYSQL_USER') ?: 'sql10824373',
    getenv('MYSQL_PASSWORD') ?: 'LVXmthUgxs',
    getenv('MYSQL_DATABASE') ?: 'sql10824373'
);

if ($conexion->connect_error) {
    $conexion = null;
}
?>
