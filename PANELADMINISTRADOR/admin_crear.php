<?php
session_start();
require_once '../includes/auth.php';
require_admin();

$error = '';
$perfiles = (api_request('GET', '/perfiles')['data']['perfiles'] ?? []);
$empresas = (api_request('GET', '/empresas')['data']['empresas'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email' => trim(strtolower($_POST['email'] ?? '')),
        'password' => $_POST['password'] ?? '',
        'perfil_id' => (int) ($_POST['perfil_id'] ?? 0),
        'empresa_id' => (int) ($_POST['empresa_id'] ?? 0),
    ];

    if ($payload['nombre'] === '' || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL) || $payload['password'] === '') {
        $error = 'Completa nombre, email y contrasena.';
    } else {
        $response = api_request('POST', '/usuarios', $payload);
        if ($response['ok']) {
            header('Location: admin_panel.php?res=creado');
            exit;
        }
        $error = $response['error'] ?: 'Error al guardar.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin:0; font-family:'Inter', sans-serif; display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .bg-overlay { position:fixed; inset:0; background:linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.6)), url('../imagenes/fondopanel.png'); background-size:cover; z-index:-1; }
        .card { background:rgba(255,255,255,.85); width:100%; max-width:420px; padding:40px; border-radius:24px; border:1px solid #fff; backdrop-filter:blur(20px); box-shadow:0 20px 40px rgba(0,0,0,.1); }
        h2 { text-align:center; font-weight:800; margin-bottom:25px; }
        label { display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:8px; text-transform:uppercase; }
        input, select { width:100%; padding:14px; margin-bottom:20px; border:1.5px solid rgba(0,0,0,.08); border-radius:12px; box-sizing:border-box; }
        .btn-add { width:100%; padding:15px; background:#000; color:white; border:none; border-radius:12px; font-weight:700; cursor:pointer; }
        .error-msg { background:#fef2f2; color:#dc2626; padding:10px; border-radius:10px; font-size:12px; margin-bottom:15px; text-align:center; }
        .back { display:block; text-align:center; margin-top:20px; color:#64748b; text-decoration:none; font-size:13px; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="card">
        <h2>Nuevo Usuario</h2>
        <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre</label><input type="text" name="nombre" required>
            <label>Email</label><input type="email" name="email" required>
            <label>Contrasena</label><input type="password" name="password" required>
            <label>Perfil</label>
            <select name="perfil_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($perfiles as $perfil): ?>
                    <option value="<?= (int) $perfil['id'] ?>"><?= e($perfil['codigo']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Empresa</label>
            <select name="empresa_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($empresas as $empresa): ?>
                    <option value="<?= (int) $empresa['id'] ?>"><?= e($empresa['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-add">CREAR USUARIO</button>
        </form>
        <a href="admin_panel.php" class="back">Volver al panel</a>
    </div>
</body>
</html>
