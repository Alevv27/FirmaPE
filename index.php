<?php
session_start();
require_once 'includes/auth.php';

$mensaje = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Ingresa un correo valido.';
    } else {
        $response = api_request('POST', '/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        if (!$response['ok']) {
            $mensaje = $response['error'] ?: 'No se pudo iniciar sesion.';
        } else {
            $_SESSION['auth'] = [
                'usuario' => $response['data']['usuario'],
                'permisos' => $response['data']['permisos'] ?? [],
                'modulos' => $response['data']['modulos'] ?? [],
            ];
            $_SESSION['user'] = $response['data']['usuario']['id'];
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | FIRMAPE</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="icon" href="imagenes/favicon.png">
    <style>
        .container {
            max-width: 420px;
            padding: 0 28px 28px;
            overflow: hidden;
        }
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 230px;
            margin: 0 -28px 24px;
            background:
                linear-gradient(90deg, rgba(224,247,255,.92) 0 50%, rgba(255,255,255,.95) 50% 100%);
        }
        .login-logo img {
            display: block;
            width: 340px;
            max-width: 86%;
            height: auto;
            margin: 0 auto;
            object-fit: contain;
        }
        h2 {
            margin: 0 0 18px;
            font-size: 24px;
            color: #000;
            text-align: center;
        }
        .login-subtitle {
            margin: -4px 0 20px;
            color: #475569;
            font-size: 14px;
            text-align: center;
        }
        .password-container { position: relative; width: 100%; }
        .password-container input { width: 100%; padding-right: 70px; box-sizing: border-box; }
        .toggle-password {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #4db8ff; font-size: 11px; font-weight: bold;
            text-transform: uppercase; user-select: none;
        }
        #overlayBienvenida {
            position: fixed; inset: 0; background: rgba(255,255,255,.98);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            z-index: 1000; visibility: hidden; opacity: 0; transition: .5s ease-in-out;
        }
        #overlayBienvenida.show { visibility: visible; opacity: 1; }
        .welcome-logo-container { width: 150px; margin-bottom: 20px; }
        .welcome-logo-container img { width: 100%; height: auto; }
        .welcome-text { color: #333; font-size: 28px; margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .welcome-subtext { color: #666; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="login-logo">
        <img src="imagenes/firmape.png" alt="Logo Empresa">
    </div>

    <h2>Bienvenido</h2>
    <p class="login-subtitle">Por favor, ingresa tus credenciales</p>

    <form method="POST">
        <input type="email" name="email" placeholder="Correo electronico" required>

        <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Contrasena" required>
            <span class="toggle-password" id="togglePass">Ver</span>
        </div>

        <button type="submit">Ingresar</button>
    </form>

    <?php if ($mensaje): ?>
        <div class="alert-error show"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="links">
        <a href="register.php">Crear cuenta</a>
        <a href="recuperar.php">Olvidaste tu contrasena?</a>
    </div>
</div>

<div id="overlayBienvenida">
    <div class="welcome-logo-container">
        <img src="imagenes/firmape.png" alt="Logo Empresa">
    </div>
    <h2 class="welcome-text">Bienvenido</h2>
    <p class="welcome-subtext">Accediendo al panel de control...</p>
</div>

<script>
const togglePass = document.getElementById('togglePass');
const passwordInput = document.getElementById('password');

togglePass.addEventListener('click', () => {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    togglePass.textContent = isPassword ? 'Ocultar' : 'Ver';
});

<?php if ($success): ?>
window.onload = () => {
    document.getElementById("overlayBienvenida").classList.add("show");
    setTimeout(() => { window.location.href = "principal.php"; }, 900);
};
<?php endif; ?>
</script>

</body>
</html>
