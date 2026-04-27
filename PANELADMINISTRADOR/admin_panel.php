<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/auth.php';
require_admin();

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    if ($id !== (int) current_user()['id']) {
        api_request('DELETE', '/usuarios/' . $id);
    }
    header('Location: admin_panel.php?res=eliminado');
    exit;
}

$usuariosResponse = api_request('GET', '/usuarios');
$usuarios = $usuariosResponse['ok'] ? ($usuariosResponse['data']['usuarios'] ?? []) : [];
$totalUsers = count($usuarios);

$totalDocsPend = 0;
if ($conexion) {
    $res = $conexion->query("SELECT COUNT(*) AS total FROM documentos WHERE estado = 'Pendiente'");
    if ($res) {
        $totalDocsPend = (int) ($res->fetch_assoc()['total'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --glass-bg: rgba(255,255,255,.75); --glass-border: rgba(255,255,255,.5); --text-main:#1e293b; --accent:#6366f1; }
        body { margin:0; font-family:'Inter', sans-serif; background-color:#f8fafc; }
        .bg-overlay { position:fixed; inset:0; background:linear-gradient(135deg, rgba(255,255,255,.92), rgba(255,255,255,.6)), url('../imagenes/fondopanel.png'); background-size:cover; z-index:-1; opacity:.7; }
        .navbar { background:rgba(255,255,255,.85); padding:15px 40px; border-bottom:1px solid var(--glass-border); display:flex; justify-content:space-between; align-items:center; backdrop-filter:blur(12px); position:sticky; top:0; z-index:100; }
        .container { max-width:1200px; margin:30px auto; padding:0 20px; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:30px; }
        .stat-card { background:var(--glass-bg); padding:25px; border-radius:20px; border:1px solid var(--glass-border); text-align:center; backdrop-filter:blur(15px); }
        .card { background:var(--glass-bg); border-radius:20px; border:1px solid var(--glass-border); backdrop-filter:blur(15px); margin-bottom:30px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,.04); }
        .card-header { padding:22px 30px; border-bottom:1px solid rgba(0,0,0,.04); display:flex; justify-content:space-between; align-items:center; }
        table { width:100%; border-collapse:collapse; }
        th { padding:18px 25px; text-align:center; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:1px; }
        td { padding:18px 25px; font-size:14px; border-bottom:1px solid rgba(0,0,0,.03); text-align:center; }
        .role-badge { background:#000; color:#fff; padding:5px 12px; border-radius:8px; font-size:10px; font-weight:800; text-transform:uppercase; display:inline-block; }
        .btn { padding:12px 22px; border-radius:12px; font-size:13px; font-weight:600; text-decoration:none; cursor:pointer; border:none; }
        .btn-dark { background:#1e293b; color:#fff; }
        .btn-danger { color:#ef4444; font-weight:700; background:none; border:none; cursor:pointer; }
        .alert { max-width:1200px; margin:0 auto 20px; background:#dcfce7; color:#166534; padding:12px 16px; border-radius:10px; font-weight:700; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <nav class="navbar">
        <h2 style="margin:0;">FIRMAPE <span style="font-weight:300; color:#64748b;">ADMIN</span></h2>
        <div>
            <a href="../principal.php" class="btn" style="background:#e0f2fe; color:#0369a1;">Panel</a>
            <a href="logout.php" class="btn" style="background:#fee2e2; color:#dc2626;">Cerrar Sesion</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_GET['res'])): ?><div class="alert">Operacion realizada.</div><?php endif; ?>

        <div class="stats">
            <div class="stat-card"><label>USUARIOS</label><p style="font-size:36px; font-weight:800; margin:5px 0;"><?= $totalUsers ?></p></div>
            <div class="stat-card"><label>PENDIENTES</label><p style="font-size:36px; font-weight:800; margin:5px 0; color:var(--accent);"><?= $totalDocsPend ?></p></div>
            <div class="stat-card"><label>BACKEND</label><p style="font-size:13px; font-weight:700; color:#10b981; margin-top:20px;"><?= e(api_base_url()) ?></p></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 style="margin:0;">Gestion de Usuarios</h3>
                <a href="admin_crear.php" class="btn btn-dark">+ Nuevo Usuario</a>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Empresa</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><strong><?= (int) $u['id'] ?></strong></td>
                            <td><?= e($u['nombre'] ?? '') ?></td>
                            <td><?= e($u['email'] ?? '') ?></td>
                            <td><span class="role-badge"><?= e($u['perfil'] ?? '') ?></span></td>
                            <td><?= e((string) ($u['empresaId'] ?? '')) ?></td>
                            <td><?= !empty($u['activo']) ? 'Si' : 'No' ?></td>
                            <td>
                                <a href="admin_editar.php?id=<?= (int) $u['id'] ?>" style="color:var(--accent); font-weight:700; text-decoration:none; margin-right:10px;">Editar</a>
                                <button onclick="confirmar('admin_panel.php?eliminar=<?= (int) $u['id'] ?>')" class="btn-danger">Borrar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmar(url) {
        Swal.fire({
            title: 'Eliminar usuario?',
            text: 'Esta accion no tiene vuelta atras.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000',
            confirmButtonText: 'Si, borrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => { if (result.isConfirmed) window.location.href = url; });
    }
    </script>
</body>
</html>
