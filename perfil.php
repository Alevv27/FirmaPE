<?php
session_start();
require_once 'includes/auth.php';

require_login();
refresh_session_user();

$user = current_user();
$mensaje = '';
$error = '';

$nombre = $user['nombre'] ?? '';
$correo = $user['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoNombre = trim($_POST['nombre'] ?? '');
    $nuevoCorreo = strtolower(trim($_POST['correo'] ?? ''));
    $nuevoPassword = $_POST['password'] ?? '';

    if ($nuevoNombre === '' || $nuevoCorreo === '') {
        $error = 'Nombre y correo son obligatorios.';
    } else {
        $payload = [
            'nombre' => $nuevoNombre,
            'email' => $nuevoCorreo,
        ];

        if ($nuevoPassword !== '') {
            $payload['password'] = $nuevoPassword;
        }

        $response = api_request('PATCH', '/usuarios/' . (int) $user['id'], $payload);

        if ($response['ok']) {
            $_SESSION['auth']['usuario'] = $response['data']['usuario'];
            $user = current_user();
            $nombre = $user['nombre'] ?? $nuevoNombre;
            $correo = $user['email'] ?? $nuevoCorreo;
            $mensaje = 'Datos actualizados correctamente.';
        } else {
            $error = $response['error'] ?: 'No se pudo actualizar el perfil.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi perfil</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">
    <h2>Mi perfil</h2>

    <form method="POST">
        <input type="text" name="nombre" placeholder="Nombre completo" value="<?= e($nombre) ?>" required>
        <input type="email" name="correo" placeholder="Correo" value="<?= e($correo) ?>" required>
        <input type="password" name="password" placeholder="Nueva contrasena (opcional)">

        <div class="perfil-resumen">
            <div><strong>Perfil:</strong> <?= e($user['perfil'] ?? '') ?></div>
            <div><strong>Empresa ID:</strong> <?= e((string) ($user['empresaId'] ?? '')) ?></div>
            <div><strong>Estado:</strong> <?= !empty($user['activo']) ? 'Activo' : 'Inactivo' ?></div>
        </div>

        <button type="submit">Guardar cambios</button>
    </form>

    <?php if ($error !== ''): ?>
    <div class="alert-error show"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($mensaje !== ''): ?>
    <div class="alert-success"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="modulos-box">
        <h3>Modulos habilitados</h3>
        <?php foreach (current_modules() as $modulo): ?>
        <span class="module-pill"><?= e($modulo['nombre'] ?? $modulo['codigo'] ?? '') ?></span>
        <?php endforeach; ?>
    </div>

    <br>
    <a href="principal.php">Volver al panel</a>
</div>

</body>
</html>
