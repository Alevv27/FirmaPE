<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/auth.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
require_admin();

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    if ($id !== (int) current_user()['id']) {
        api_request('DELETE', '/usuarios/' . $id);
    }
    header('Location: admin_panel.php?res=eliminado');
    exit;
}

$mensaje = '';
$error = '';
$perfilesResponse = api_request('GET', '/perfiles');
$empresasResponse = api_request('GET', '/empresas');
$perfiles = $perfilesResponse['ok'] ? ($perfilesResponse['data']['perfiles'] ?? []) : [];
$empresas = $empresasResponse['ok'] ? ($empresasResponse['data']['empresas'] ?? []) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id'])) {
    $id = (int) $_POST['editar_id'];
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
        $mensaje = 'Usuario actualizado correctamente.';
    } else {
        $error = $response['error'] ?: 'No se pudo actualizar el usuario.';
    }
}

$usuariosResponse = api_request('GET', '/usuarios');
$usuarios = $usuariosResponse['ok'] ? ($usuariosResponse['data']['usuarios'] ?? []) : [];
$totalUsers = count($usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --glass-bg: rgba(255,255,255,.92); --glass-border: rgba(226,232,240,.8); --text-main:#111827; --accent:#2f8cff; --dark:#182035; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:'Inter', sans-serif; background:linear-gradient(to right, rgba(204,231,240,.74), rgba(126,200,227,.74)), url('../imagenes/fondope.png'); background-size:cover; background-attachment:fixed; color:var(--text-main); }
        <?php render_firmape_topbar_styles(); ?>
        <?php render_firmape_sidebar_styles(); ?>
        .container { max-width:1180px; margin:0 auto; padding:0 20px; }
        .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:20px; }
        .page-head h1 { margin:0 0 6px; font-size:30px; }
        .page-head p { margin:0; color:#475569; font-weight:700; }
        .stats { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:18px; margin-bottom:22px; }
        .stat-card { background:var(--glass-bg); padding:20px; border-radius:14px; border:1px solid var(--glass-border); box-shadow:0 14px 30px rgba(15,23,42,.1); display:flex; align-items:center; justify-content:space-between; gap:14px; }
        .stat-card .stat-icon { width:54px; height:54px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; background:#eff6ff; color:var(--accent); }
        .stat-card label { display:block; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.7px; font-weight:900; margin-bottom:8px; }
        .stat-card p { margin:0; font-size:30px; font-weight:900; }
        .stat-card .small-value { font-size:13px; color:#059669; word-break:break-all; }
        .card { background:var(--glass-bg); border-radius:16px; border:1px solid var(--glass-border); overflow:hidden; box-shadow:0 18px 42px rgba(15,23,42,.12); }
        .card-header { padding:22px 26px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; gap:16px; }
        .card-header h3 { margin:0; font-size:20px; }
        .table-wrap { overflow:auto; scrollbar-width:none; }
        .table-wrap::-webkit-scrollbar { width:0; height:0; }
        table { width:100%; border-collapse:collapse; }
        th { padding:14px 18px; text-align:left; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.8px; background:#f8fafc; }
        td { padding:16px 18px; font-size:14px; border-bottom:1px solid #edf2f7; text-align:left; vertical-align:middle; }
        tbody tr:hover { background:#f8fafc; }
        .role-badge { color:#fff; padding:6px 10px; border-radius:999px; font-size:10px; font-weight:900; text-transform:uppercase; display:inline-block; min-width:78px; text-align:center; }
        .role-admin { background:#6366f1; }
        .role-firmante { background:#10b981; }
        .role-gestor { background:#0ea5e9; }
        .status-ok { display:inline-flex; align-items:center; gap:6px; color:#065f46; font-weight:900; }
        .status-ok::before { content:""; width:8px; height:8px; border-radius:999px; background:#22c55e; }
        .btn { padding:12px 18px; border-radius:10px; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; border:none; display:inline-flex; align-items:center; justify-content:center; }
        .btn-dark { background:#1e293b; color:#fff; }
        .btn-panel { background:#e0f2fe; color:#0369a1; }
        .btn-session { background:#fee2e2; color:#dc2626; }
        .action-group { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .btn-edit { background:#eef2ff; color:#4338ca; }
        .btn-danger { background:#fee2e2; color:#dc2626; font-weight:900; border:none; cursor:pointer; border-radius:9px; padding:9px 11px; }
        .alert { margin:0 0 18px; background:#dcfce7; color:#166534; padding:12px 16px; border-radius:10px; font-weight:800; }
        .alert-error { background:#fee2e2; color:#991b1b; }
        .modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.52); display:none; align-items:center; justify-content:center; padding:22px; z-index:2000; }
        .modal-backdrop.show { display:flex; }
        .modal-card { width:100%; max-width:520px; background:rgba(255,255,255,.98); border-radius:18px; box-shadow:0 30px 70px rgba(15,23,42,.3); overflow:hidden; }
        .modal-head { padding:20px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0; }
        .modal-head h2 { margin:0; font-size:21px; }
        .modal-close { width:34px; height:34px; border:0; border-radius:999px; background:#f1f5f9; cursor:pointer; font-size:20px; font-weight:900; color:#475569; }
        .modal-body { padding:22px 24px 24px; }
        .modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .modal-field.full { grid-column:1 / -1; }
        .modal-field label { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:900; margin-bottom:7px; }
        .modal-field input, .modal-field select { width:100%; padding:12px 13px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; outline:none; background:white; }
        .modal-field input:focus, .modal-field select:focus { border-color:#2f8cff; box-shadow:0 0 0 3px rgba(47,140,255,.14); }
        .modal-check { display:flex; align-items:center; gap:9px; font-weight:800; color:#334155; }
        .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:18px; border-top:1px solid #e2e8f0; }
        .btn-cancel { background:#f1f5f9; color:#334155; }
        @media (max-width: 1000px) {
            .stats { grid-template-columns:1fr; }
            table { min-width:900px; }
        }
        @media (max-width: 620px) {
            .modal-grid { grid-template-columns:1fr; }
            .modal-field.full { grid-column:auto; }
        }
    </style>
</head>
<body>
    <?php render_firmape_topbar('../'); ?>

    <?php render_firmape_sidebar('admin', '../'); ?>
    <main class="module-content">
    <div class="container">
        <?php if (isset($_GET['res'])): ?><div class="alert">Operacion realizada.</div><?php endif; ?>
        <?php if ($mensaje): ?><div class="alert"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <div class="page-head">
            <div>
                <h1>Administracion</h1>
                <p>Gestiona usuarios, perfiles y acceso a los modulos.</p>
            </div>
            <a href="admin_crear.php" class="btn btn-dark">+ Nuevo Usuario</a>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div><label>Usuarios registrados</label><p><?= $totalUsers ?></p></div>
                <span class="stat-icon">&#128101;</span>
            </div>
            <div class="stat-card">
                <div><label>Servicio backend</label><p class="small-value"><?= e(api_base_url()) ?></p></div>
                <span class="stat-icon">&#128187;</span>
            </div>
            <div class="stat-card">
                <div><label>Perfil actual</label><p style="font-size:24px;"><?= e(current_profile()) ?></p></div>
                <span class="stat-icon">&#9881;</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Gestion de Usuarios</h3>
                <span style="color:#64748b; font-size:13px; font-weight:800;">Total: <?= $totalUsers ?></span>
            </div>
            <div class="table-wrap">
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
                            <?php $roleClass = 'role-' . strtolower((string) ($u['perfil'] ?? '')); ?>
                            <td><span class="role-badge <?= e($roleClass) ?>"><?= e($u['perfil'] ?? '') ?></span></td>
                            <td><?= e((string) ($u['empresaId'] ?? '')) ?></td>
                            <td><?= !empty($u['activo']) ? '<span class="status-ok">Si</span>' : 'No' ?></td>
                            <td>
                                <div class="action-group">
                                    <button
                                        type="button"
                                        class="btn btn-edit"
                                        onclick="abrirEditarUsuario(this)"
                                        data-id="<?= (int) $u['id'] ?>"
                                        data-nombre="<?= e($u['nombre'] ?? '') ?>"
                                        data-email="<?= e($u['email'] ?? '') ?>"
                                        data-perfil="<?= e($u['perfil'] ?? '') ?>"
                                        data-empresa="<?= (int) ($u['empresaId'] ?? 0) ?>"
                                        data-activo="<?= !empty($u['activo']) ? '1' : '0' ?>"
                                    >Editar</button>
                                    <button onclick="confirmar('admin_panel.php?eliminar=<?= (int) $u['id'] ?>')" class="btn-danger">Borrar</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </main>
    </div>

    <div class="modal-backdrop" id="modalEditarUsuario" onclick="cerrarEditarUsuario(event)">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalEditarTitulo">
            <div class="modal-head">
                <h2 id="modalEditarTitulo">Editar Usuario</h2>
                <button type="button" class="modal-close" onclick="cerrarEditarUsuario()">×</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="editar_id" id="editarId">
                <div class="modal-grid">
                    <div class="modal-field full">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre" id="editarNombre" required>
                    </div>
                    <div class="modal-field full">
                        <label>Email</label>
                        <input type="email" name="email" id="editarEmail" required>
                    </div>
                    <div class="modal-field">
                        <label>Perfil</label>
                        <select name="perfil_id" id="editarPerfil" required>
                            <?php foreach ($perfiles as $perfil): ?>
                                <option value="<?= (int) $perfil['id'] ?>" data-codigo="<?= e($perfil['codigo']) ?>"><?= e($perfil['codigo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label>Empresa</label>
                        <select name="empresa_id" id="editarEmpresa" required>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?= (int) $empresa['id'] ?>"><?= e($empresa['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-field full">
                        <label>Seguridad opcional</label>
                        <input type="password" name="password" placeholder="Nueva contrasena">
                    </div>
                    <div class="modal-field full">
                        <label class="modal-check">
                            <input type="checkbox" name="activo" id="editarActivo">
                            Usuario activo
                        </label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="cerrarEditarUsuario()">Cancelar</button>
                    <button type="submit" class="btn btn-dark">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirEditarUsuario(button) {
        document.getElementById('editarId').value = button.dataset.id || '';
        document.getElementById('editarNombre').value = button.dataset.nombre || '';
        document.getElementById('editarEmail').value = button.dataset.email || '';
        document.getElementById('editarEmpresa').value = button.dataset.empresa || '';
        document.getElementById('editarActivo').checked = button.dataset.activo === '1';

        const perfil = button.dataset.perfil || '';
        const perfilSelect = document.getElementById('editarPerfil');
        [...perfilSelect.options].forEach(option => {
            option.selected = option.dataset.codigo === perfil;
        });

        document.getElementById('modalEditarUsuario').classList.add('show');
    }

    function cerrarEditarUsuario(event) {
        if (event && event.target.id !== 'modalEditarUsuario') return;
        document.getElementById('modalEditarUsuario').classList.remove('show');
    }

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
