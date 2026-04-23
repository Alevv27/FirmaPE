<?php
session_start();
require_once 'includes/auth.php';

if (current_user()) {
    header('Location: principal.php');
    exit;
}

$mensaje = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $mensaje = 'Completa correo y contrasena.';
    } else {
        $response = api_request('POST', '/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if ($response['ok']) {
            $_SESSION['auth'] = [
                'usuario' => $response['data']['usuario'],
                'permisos' => $response['data']['permisos'] ?? [],
                'modulos' => $response['data']['modulos'] ?? [],
            ];

            header('Location: principal.php');
            exit;
        }

        $mensaje = $response['error'] ?: 'Credenciales incorrectas.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="css/estilos.css">
<link rel="icon" href="imagenes/favicon.png">
</head>
<body>

<div class="container">
    <div class="login-logo">
        <img src="imagenes/firmape.png" alt="Logo Empresa">
    </div>

    <h2>Login</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Correo" value="<?= e($email) ?>" required>
        <input type="password" name="password" placeholder="Contrasena" required>
        <button type="submit">Ingresar</button>
    </form>

    <?php if ($mensaje !== ''): ?>
    <div class="alert-error show"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="links">
        <a href="register.php">Crear usuario</a>
        <a href="principal.php">Panel</a>
    </div>
</div>

</body>
</html>
