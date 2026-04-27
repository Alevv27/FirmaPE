<?php
session_start();
require_once '../includes/auth.php';

$mensaje = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $response = api_request('POST', '/auth/login', [
        'email' => $email,
        'password' => $password,
    ]);

    if (!$response['ok']) {
        $mensaje = $response['error'] ?: 'No se pudo iniciar sesion.';
    } elseif (!in_array('ADMINISTRACION', array_column($response['data']['modulos'] ?? [], 'codigo'), true)) {
        $mensaje = 'Acceso denegado: no tienes modulo de administracion.';
    } else {
        $_SESSION['auth'] = [
            'usuario' => $response['data']['usuario'],
            'permisos' => $response['data']['permisos'] ?? [],
            'modulos' => $response['data']['modulos'] ?? [],
        ];
        $_SESSION['admin_id'] = $response['data']['usuario']['id'];
        $_SESSION['user'] = $response['data']['usuario']['id'];
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --card-bg: rgba(255,255,255,.95); --text-main:#1e293b; --text-muted:#64748b; --accent:#6366f1; --border:#e2e8f0; }
        body {
            margin: 0; font-family: 'Inter', sans-serif; background-image: url('../imagenes/fondopanel.png');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; justify-content: center; align-items: center; height: 100vh; color: var(--text-main);
        }
        body::before { content:""; position:absolute; inset:0; background:rgba(0,0,0,.3); z-index:-1; }
        .container {
            background: var(--card-bg); padding: 45px; border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,.5); width: 100%; max-width: 380px;
            text-align: center; border: 1px solid rgba(255,255,255,.3); backdrop-filter: blur(10px);
        }
        .header-area h2 { margin:0; font-size:28px; font-weight:700; }
        .header-area p { color:var(--text-muted); font-size:14px; margin:8px 0 30px; }
        .input-group { text-align:left; margin-bottom:20px; position:relative; }
        label { display:block; font-size:12px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; }
        input { width:100%; padding:14px 16px; border:1.5px solid var(--border); border-radius:12px; box-sizing:border-box; font-size:15px; background:#fff; }
        input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 4px rgba(99,102,241,.1); }
        .password-wrapper { position:relative; }
        .toggle-btn { position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:11px; font-weight:700; color:var(--accent); text-transform:uppercase; }
        button { width:100%; padding:15px; background:#1f2937; color:white; border:none; border-radius:12px; cursor:pointer; font-weight:600; font-size:16px; margin-top:10px; }
        .alert-error { background:#fef2f2; color:#b91c1c; padding:12px; border-radius:10px; margin-top:20px; font-size:13px; border:1px solid #fee2e2; }
        #overlayBienvenida { position:fixed; inset:0; background:#fff; display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:1000; visibility:hidden; opacity:0; transition:.6s; }
        #overlayBienvenida.show { visibility:visible; opacity:1; }
        .loader { width:48px; height:48px; border:5px solid #f3f3f3; border-top:5px solid #1f2937; border-radius:50%; animation:spin 1s linear infinite; margin-bottom:20px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <h2>Panel de Control</h2>
        <p>Administracion de usuarios y perfiles.</p>
    </div>

    <form method="POST">
        <div class="input-group">
            <label>Correo</label>
            <input type="email" name="email" placeholder="admin@empresa.com" required>
        </div>

        <div class="input-group">
            <label>Contrasena</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="********" required>
                <span class="toggle-btn" id="btnToggle">Ver</span>
            </div>
        </div>

        <button type="submit">Iniciar Sesion</button>
    </form>

    <?php if ($mensaje): ?><div class="alert-error"><?= e($mensaje) ?></div><?php endif; ?>
</div>

<div id="overlayBienvenida">
    <div class="loader"></div>
    <h2 style="font-size: 24px;">Autenticacion exitosa</h2>
    <p>Cargando panel administrativo...</p>
</div>

<script>
document.getElementById('btnToggle').addEventListener('click', () => {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
    document.getElementById('btnToggle').textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
});
<?php if ($success): ?>
window.onload = () => {
    document.getElementById("overlayBienvenida").classList.add("show");
    setTimeout(() => { window.location.href = "admin_panel.php"; }, 900);
};
<?php endif; ?>
</script>

</body>
</html>
