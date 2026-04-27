<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['auth']['usuario'] ?? null;
}

function current_modules(): array
{
    return $_SESSION['auth']['modulos'] ?? [];
}

function current_permissions(): array
{
    return $_SESSION['auth']['permisos'] ?? [];
}

function current_profile(): string
{
    return strtoupper((string) ($_SESSION['auth']['usuario']['perfil'] ?? ''));
}

function has_permission(string $permiso): bool
{
    return (bool) (current_permissions()[$permiso] ?? false);
}

function has_module(string $codigo): bool
{
    $codigo = strtoupper($codigo);
    foreach (current_modules() as $modulo) {
        if (strtoupper((string) ($modulo['codigo'] ?? '')) === $codigo) {
            return true;
        }
    }

    return false;
}

function require_login(): void
{
    if (!isset($_SESSION['auth']['usuario']['id'])) {
        header('Location: index.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (!has_module('ADMINISTRACION')) {
        header('Location: ../principal.php?error=modulo');
        exit;
    }
}

function require_module(string $codigo): void
{
    require_login();

    if (!has_module($codigo)) {
        header('Location: principal.php?error=modulo');
        exit;
    }
}

function refresh_session_user(): bool
{
    $user = current_user();

    if (!$user || !isset($user['id'])) {
        return false;
    }

    $response = api_request('GET', '/usuarios/' . (int) $user['id']);

    if (!$response['ok']) {
        return false;
    }

    $_SESSION['auth']['usuario'] = $response['data']['usuario'];
    return true;
}
