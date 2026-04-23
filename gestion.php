<?php
session_start();
require_once 'includes/auth.php';

require_module('GESTION');

$usuario = current_user();
$permisos = current_permissions();
$modulos = current_modules();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container container-wide">
    <h2>Gestion de documentos</h2>
    <p class="subtitle">Vista conectada con tu backend Flask y permisos reales por perfil.</p>

    <div class="perfil-resumen">
        <div><strong>Usuario:</strong> <?= e($usuario['nombre'] ?? '') ?></div>
        <div><strong>Correo:</strong> <?= e($usuario['email'] ?? '') ?></div>
        <div><strong>Perfil:</strong> <?= e($usuario['perfil'] ?? '') ?></div>
        <div><strong>Empresa ID:</strong> <?= e((string) ($usuario['empresaId'] ?? '')) ?></div>
    </div>

    <div class="grid-two">
        <div class="info-panel">
            <h3>Permisos</h3>
            <ul class="plain-list">
                <li>Administracion: <?= !empty($permisos['administracion']) ? 'Si' : 'No' ?></li>
                <li>Gestion: <?= !empty($permisos['gestion']) ? 'Si' : 'No' ?></li>
                <li>Firmar: <?= !empty($permisos['firmar']) ? 'Si' : 'No' ?></li>
            </ul>
        </div>

        <div class="info-panel">
            <h3>Modulos habilitados</h3>
            <?php foreach ($modulos as $modulo): ?>
            <span class="module-pill"><?= e($modulo['nombre'] ?? $modulo['codigo'] ?? '') ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="links" style="justify-content:center;">
        <a href="principal.php">Volver al panel</a>
        <?php if (has_module('FIRMAR')): ?>
        <a href="firmar.php">Ir a firmar</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
