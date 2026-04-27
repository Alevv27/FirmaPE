<?php
session_start();
require_once '../includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$msg = '';
$error = '';

$usuarioResponse = api_request('GET', '/usuarios/' . $id);
if (!$usuarioResponse['ok']) {
    header('Location: admin_panel.php?res=no_existe');
    exit;
}
$u = $usuarioResponse['data']['usuario'];
$perfiles = (api_request('GET', '/perfiles')['data']['perfiles'] ?? []);
$empresas = (api_request('GET', '/empresas')['data']['empresas'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email' => trim(strtolower($_POST['email'] ?? '')),
        'perfil_id' => (int) ($_POST['perfil_id'] ?? 0),
        'empresa_id' => (int) ($_POST['empresa_id'] ?? 0),
        'activo' => isset($_POST['activo']),
    ];

    if (($_POST['password'] ?? '') !== '') {
        $payload['password'] = $_POST['password'];
    }

    $response = api_request('PATCH', '/usuarios/' . $id, $payload);
    if ($response['ok']) {
        $msg = 'Datos actualizados correctamente.';
        $u = $response['data']['usuario'];
    } else {
        $error = $response['error'] ?: 'No se pudo actualizar.';
    }
}

$perfilActualId = null;
foreach ($perfiles as $perfil) {
    if (($perfil['codigo'] ?? '') === ($u['perfil'] ?? '')) {
        $perfilActualId = (int) $perfil['id'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --glass-bg:rgba(255,255,255,.85); --glass-border:rgba(255,255,255,.5); --text-main:#1e293b; --text-muted:#64748b; --accent:#6366f1; }
        body { margin:0; font-family:'Inter', sans-serif; min-height:100vh; display:flex; justify-content:center; align-items:center; color:var(--text-main); }
        .bg-overlay { position:fixed; inset:0; background:linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,255,255,.6)), url('../imagenes/fondopanel.png'); background-size:cover; z-index:-1; opacity:.7; }
        .card { background:var(--glass-bg); width:100%; max-width:440px; padding:40px; border-radius:24px; border:1px solid var(--glass-border); backdrop-filter:blur(20px); box-shadow:0 25px 50px -12px rgba(0,0,0,.1); box-sizing:border-box; }
        h2 { text-align:center; font-size:22px; font-weight:800; margin-bottom:25px; }
        label { display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; }
        input, select { width:100%; padding:14px 16px; margin-bottom:20px; border:1.5px solid rgba(0,0,0,.08); border-radius:12px; box-sizing:border-box; font-size:14px; background:rgba(255,255,255,.6); }
        .check-row { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
        .check-row input { width:auto; margin:0; }
        .btn-up { width:100%; padding:15px; background:#1e293b; color:white; border:none; border-radius:12px; font-weight:700; cursor:pointer; margin-top:10px; }
        .error-msg { background:#fef2f2; color:#dc2626; padding:12px; border-radius:12px; font-size:12px; margin-bottom:15px; }
        .success-msg { background:#dcfce7; color:#166534; padding:12px; border-radius:12px; font-size:13px; font-weight:600; text-align:center; margin-bottom:15px; }
        .back-link { display:block; text-align:center; margin-top:25px; font-size:13px; color:var(--text-muted); text-decoration:none; font-weight:600; }
    </style>
</head>
<body>

<div class="bg-overlay"></div>

<div class="card">
    <h2>Editar Usuario</h2>
    <?php if ($msg): ?><div class="success-msg"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>

    <form method="POST">
        <label>Nombre Completo</label>
        <input type="text" name="nombre" value="<?= e($u['nombre'] ?? '') ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= e($u['email'] ?? '') ?>" required>

        <label>Perfil</label>
        <select name="perfil_id" required>
            <?php foreach ($perfiles as $perfil): ?>
                <option value="<?= (int) $perfil['id'] ?>" <?= ((int) $perfil['id'] === $perfilActualId) ? 'selected' : '' ?>>
                    <?= e($perfil['codigo']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Empresa</label>
        <select name="empresa_id" required>
            <?php foreach ($empresas as $empresa): ?>
                <option value="<?= (int) $empresa['id'] ?>" <?= ((int) $empresa['id'] === (int) ($u['empresaId'] ?? 0)) ? 'selected' : '' ?>>
                    <?= e($empresa['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Seguridad (Opcional)</label>
        <input type="password" name="password" placeholder="Nueva contrasena">

        <div class="check-row">
            <input type="checkbox" name="activo" id="activo" <?= !empty($u['activo']) ? 'checked' : '' ?>>
            <label for="activo" style="margin:0;">Usuario activo</label>
        </div>

        <button type="submit" class="btn-up">GUARDAR CAMBIOS</button>
    </form>

    <a href="admin_panel.php" class="back-link">Cancelar y volver al panel</a>
</div>

</body>
</html>
