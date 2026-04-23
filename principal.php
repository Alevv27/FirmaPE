<?php
session_start();
require_once 'includes/auth.php';

require_login();

$usuario = current_user();
$modulos = current_modules();
$errorModulo = ($_GET['error'] ?? '') === 'modulo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel principal</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="header">
    <div class="logo">
        <img src="imagenes/favicon.png" alt="logo">
        <h3>FIRMAPE</h3>
    </div>

    <div class="user-info">
        <div class="top-user">
            <span><?= e($usuario['nombre'] ?? $usuario['email'] ?? 'Usuario') ?></span>
            <div class="perfil" onclick="location.href='perfil.php'">U</div>
        </div>
        <div><?= e($usuario['email'] ?? '') ?></div>
        <div><?= e($usuario['perfil'] ?? '') ?></div>
        <div id="hora"></div>
        <div class="logout" onclick="location.href='logout.php'">Cerrar sesion</div>
    </div>
</div>

<div class="dashboard">
    <?php if ($errorModulo): ?>
    <div class="full-alert">No tienes permiso para entrar a ese modulo.</div>
    <?php endif; ?>

    <?php if (has_module('ADMINISTRACION')): ?>
    <div class="card" onclick="location.href='register.php'">
        <div class="icon">ADM</div>
        <p>Administracion de usuarios</p>
    </div>
    <?php endif; ?>

    <?php if (has_module('GESTION')): ?>
    <div class="card" onclick="location.href='gestion.php'">
        <div class="icon">DOC</div>
        <p>Gestion de documentos</p>
    </div>
    <?php endif; ?>

    <?php if (has_module('FIRMAR')): ?>
    <div class="card" onclick="location.href='firmar.php'">
        <div class="icon">FIR</div>
        <p>Firmar documentos</p>
    </div>
    <?php endif; ?>

    <?php if (!$modulos): ?>
    <div class="full-alert">Tu perfil no tiene modulos habilitados.</div>
    <?php endif; ?>
</div>

<div class="module-strip">
    <?php foreach ($modulos as $modulo): ?>
    <span class="module-pill"><?= e($modulo['nombre'] ?? $modulo['codigo'] ?? '') ?></span>
    <?php endforeach; ?>
</div>

<script>
function actualizarHora() {
    const now = new Date();
    const fecha = now.toLocaleDateString('es-PE');
    const hora = now.toLocaleTimeString('es-PE');
    document.getElementById('hora').innerHTML = fecha + ' | ' + hora;
}

setInterval(actualizarHora, 1000);
actualizarHora();
</script>

</body>
</html>
