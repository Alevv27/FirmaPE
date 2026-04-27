<?php
session_start();
require_once 'includes/auth.php';
require_login();

$usuario = current_user();
$mensaje = '';
$color_alerta = '#ff4d4d';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoEmail = trim(strtolower($_POST['email'] ?? ''));

    if (!filter_var($nuevoEmail, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Correo invalido.';
    } else {
        $response = api_request('PATCH', '/usuarios/' . (int) $usuario['id'], [
            'email' => $nuevoEmail,
        ]);

        if ($response['ok']) {
            $_SESSION['auth']['usuario'] = $response['data']['usuario'];
            $usuario = current_user();
            $mensaje = 'Correo actualizado correctamente.';
            $color_alerta = '#00c853';
        } else {
            $mensaje = $response['error'] ?: 'No se pudo actualizar el correo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi perfil</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .btn-volver {
            display: inline-block; margin-top: 20px; text-decoration: none;
            color: #4db8ff; font-weight: bold; font-size: 14px;
        }
        input[readonly] { background-color: #f0f0f0; cursor: not-allowed; color: #888; }
        label { display:block; text-align:left; font-size:12px; color:#666; margin: 10px 0 5px 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Mi perfil</h2>

    <form method="POST">
        <label>Nombre completo</label>
        <input type="text" value="<?= e($usuario['nombre'] ?? '') ?>" readonly>

        <label>Perfil</label>
        <input type="text" value="<?= e($usuario['perfil'] ?? '') ?>" readonly>

        <label>Empresa ID</label>
        <input type="text" value="<?= e((string) ($usuario['empresaId'] ?? '')) ?>" readonly>

        <label>Correo electronico</label>
        <input type="email" name="email" placeholder="Correo" value="<?= e($usuario['email'] ?? '') ?>" required>

        <button type="submit">Guardar cambios</button>
    </form>

    <?php if ($mensaje): ?>
        <div class="alert-error show" style="background: <?= e($color_alerta) ?>; color: white; border: none;">
            <?= e($mensaje) ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 10px;">
        <a href="principal.php" class="btn-volver">Volver al panel</a>
    </div>
</div>

</body>
</html>
