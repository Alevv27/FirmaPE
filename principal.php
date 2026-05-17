<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/topbar.php';
require_login();

$usuario = current_user();
$perfil = current_profile();
$nombre = explode(' ', trim($usuario['nombre'] ?? 'Usuario'))[0] ?: 'Usuario';

$badgeClass = [
    'ADMIN' => 'bg-admin',
    'FIRMANTE' => 'bg-firmante',
    'GESTOR' => 'bg-gestor',
][$perfil] ?? 'bg-usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal | FIRMAPE</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #101827;
            overflow: hidden;
            background: linear-gradient(to right, rgba(204,231,240,0.74), rgba(126,200,227,0.74)),
                        url("imagenes/fondope.png");
            background-size: cover;
            background-attachment: fixed;
        }
        <?php render_firmape_topbar_styles(); ?>
        .app-shell {
            height: calc(100vh - 67px);
            display: grid;
            grid-template-columns: 282px minmax(0, 1fr);
            overflow:hidden;
        }
        .sidebar {
            height: calc(100vh - 67px);
            background: #182035;
            color: #e8eefb;
            box-shadow: 16px 0 34px rgba(15, 23, 42, .16);
            overflow: hidden;
            align-self: start;
            display:flex;
            flex-direction:column;
        }
        .sidebar-brand { padding: 24px 22px 20px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(255,255,255,.08); }
        .sidebar-brand img { width: 34px; height: 34px; }
        .sidebar-brand strong { letter-spacing:.4px; font-size:18px; }
        .sidebar-brand span { display:block; margin-top:2px; color:#94a3b8; font-size:12px; font-weight:700; }
        .sidebar-nav { padding:14px 12px; display:grid; gap:6px; }
        .side-link {
            width:100%;
            border:0;
            border-radius:9px;
            background:transparent;
            color:#cbd5e1;
            padding: 14px 14px;
            display:flex;
            align-items:center;
            gap:12px;
            cursor:pointer;
            text-align:left;
            font-weight:800;
            transition:.2s ease;
        }
        .side-link:hover { background:rgba(255,255,255,.08); color:white; transform:translateX(2px); }
        .side-link.active { background:#2f8cff; color:white; box-shadow:0 10px 20px rgba(47,140,255,.25); }
        .side-link .side-icon { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; font-size:17px; }
        .side-link .side-text { display:grid; gap:2px; }
        .side-link small { color:rgba(226,232,240,.75); font-size:11px; font-weight:700; }
        .side-link.active small { color:rgba(255,255,255,.86); }
        .side-link .count {
            margin-left:auto; min-width:22px; height:22px; padding:0 7px;
            border-radius:999px; background:rgba(255,255,255,.16); color:white;
            display:inline-flex; align-items:center; justify-content:center; font-size:12px;
        }
        .sidebar-footer {
            margin-top:auto;
            padding:18px 22px 24px;
            color:#94a3b8;
            font-size:12px;
            font-weight:700;
            border-top:1px solid rgba(255,255,255,.08);
        }
        .dashboard-area {
            min-width:0;
            height:100%;
            padding: 38px 44px 54px;
            overflow:auto;
            scrollbar-width:none;
        }
        .dashboard-area::-webkit-scrollbar { width:0; height:0; }
        .dashboard-head {
            max-width: 1080px;
            margin:0 auto 22px;
            display:flex;
            justify-content:space-between;
            align-items:flex-end;
            gap:20px;
        }
        .dashboard-head h1 { margin:0 0 8px; font-size:30px; letter-spacing:0; }
        .dashboard-head p { margin:0; color:#334155; font-weight:600; }
        .dashboard-summary {
            min-width:152px;
            padding:14px 16px;
            border-radius:12px;
            background:rgba(255,255,255,.78);
            border:1px solid rgba(226,232,240,.88);
            box-shadow:0 12px 26px rgba(15,23,42,.1);
            text-align:right;
        }
        .dashboard-summary strong { display:block; font-size:26px; line-height:1; }
        .dashboard-summary span { color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase; }
        .dashboard-container {
            max-width: 1080px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            padding: 0;
        }
        .card-moderna {
            position:relative; overflow:hidden; min-height: 188px;
            background: rgba(255,255,255,.94); padding: 24px; border-radius: 12px;
            cursor: pointer; transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            box-shadow: 0 16px 34px rgba(15,23,42,0.13);
            border: 1px solid rgba(226,232,240,.95);
            display:flex; flex-direction:column; justify-content:space-between;
        }
        .card-moderna::before {
            content:""; position:absolute; inset:0 auto 0 0; width:5px;
            background: var(--accent, #2f8cff); opacity:.95;
        }
        .card-moderna:hover { transform: translateY(-6px); box-shadow: 0 24px 46px rgba(15,23,42,0.2); border-color:rgba(47,140,255,.42); }
        .card-top { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; }
        .card-moderna .icon {
            width:62px; height:62px; border-radius:16px; display:flex; align-items:center; justify-content:center;
            font-size:34px; background: var(--soft, #eff6ff); color: var(--accent, #2f8cff);
        }
        .card-moderna .arrow {
            width:34px; height:34px; border-radius:999px; display:flex; align-items:center; justify-content:center;
            background:#f8fafc; color:#64748b; font-weight:900; transition:.2s ease;
        }
        .card-moderna:hover .arrow { background:var(--accent, #2f8cff); color:white; transform:translateX(2px); }
        .card-moderna h3 { margin: 20px 0 8px; font-size: 17px; text-transform: uppercase; font-weight: 900; letter-spacing:0; }
        .card-moderna p { margin:0; color:#64748b; font-size:13px; line-height:1.35; font-weight:600; }
        .module-gestion { --accent:#f59e0b; --soft:#fff7ed; }
        .module-procesos { --accent:#7c3aed; --soft:#f5f3ff; }
        .module-firmar { --accent:#10b981; --soft:#ecfdf5; }
        .module-digital { --accent:#0ea5e9; --soft:#f0f9ff; }
        .module-admin { --accent:#64748b; --soft:#f8fafc; }
        @media (max-width: 1180px) {
            .dashboard-container { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 860px) {
            .app-shell { grid-template-columns:1fr; }
            .sidebar { position:static; min-height:0; display:block; }
            .sidebar-brand { display:none; }
            .sidebar-nav { display:flex; overflow-x:auto; padding:12px; }
            .side-link { min-width:max-content; }
            .side-link small, .sidebar-footer { display:none; }
            .dashboard-area { padding: 22px 18px 34px; }
            .dashboard-head { display:block; }
            .dashboard-summary { margin-top:14px; text-align:left; }
            .dashboard-container { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<?php render_firmape_topbar(); ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'modulo'): ?>
    <div style="max-width:900px; margin:25px auto 0; background:#fee2e2; color:#991b1b; padding:14px 18px; border-radius:10px; font-weight:700;">
        No tienes permiso para acceder a ese modulo.
    </div>
<?php endif; ?>

<?php
$modulosDisponibles = [];
if (has_module('GESTION') && in_array($perfil, ['GESTOR', 'ADMIN'], true)) {
    $modulosDisponibles[] = ['label' => 'Gestion', 'icon' => '&#128194;', 'href' => 'gestion.php'];
}
if (has_module('PROCESOS_GENERAL') && in_array($perfil, ['GESTOR', 'ADMIN'], true)) {
    $modulosDisponibles[] = ['label' => 'Procesos', 'icon' => '&#128196;', 'href' => 'procesos_general.php'];
}
if (has_module('FIRMAR') && in_array($perfil, ['FIRMANTE', 'ADMIN'], true)) {
    $modulosDisponibles[] = ['label' => 'Firmar', 'icon' => '&#9997;', 'href' => 'firmante_documentos.php'];
    $modulosDisponibles[] = ['label' => 'Firma digital', 'icon' => '&#128395;', 'href' => 'firmar_documento.php'];
}
if (has_module('ADMINISTRACION')) {
    $modulosDisponibles[] = ['label' => 'Administracion', 'icon' => '&#9881;', 'href' => 'PANELADMINISTRADOR/admin_panel.php'];
}
?>

<div class="app-shell">
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="imagenes/favicon.png" alt="logo">
        <div>
            <strong>FIRMAPE</strong>
            <span>Centro de trabajo</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <button class="side-link active" type="button">
            <span class="side-icon">&#8962;</span>
            <span class="side-text">
                <span>Home</span>
                <small>Dashboard</small>
            </span>
            <span class="count"><?= count($modulosDisponibles) ?></span>
        </button>
        <?php foreach ($modulosDisponibles as $modulo): ?>
            <button class="side-link" type="button" onclick="location.href='<?= e($modulo['href']) ?>'">
                <span class="side-icon"><?= $modulo['icon'] ?></span>
                <span class="side-text">
                    <span><?= e($modulo['label']) ?></span>
                    <small>Abrir modulo</small>
                </span>
            </button>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        Perfil activo: <?= e($perfil) ?>
    </div>
</aside>

<main class="dashboard-area">
    <section class="dashboard-head">
        <div>
            <h1>Dashboard</h1>
            <p>Accede rapidamente a las herramientas disponibles para tu perfil.</p>
        </div>
        <div class="dashboard-summary">
            <strong><?= count($modulosDisponibles) ?></strong>
            <span>Modulos activos</span>
        </div>
    </section>

    <section class="dashboard-container">
    <?php if (has_module('GESTION') && in_array($perfil, ['GESTOR', 'ADMIN'], true)): ?>
        <div class="card-moderna module-gestion" onclick="location.href='gestion.php'">
            <div class="card-top">
                <span class="icon">&#128194;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Gestion de archivos</h3>
            <p>Registra procesos, carga documentos PDF y ubica la zona de firma.</p>
        </div>
    <?php endif; ?>

    <?php if (has_module('PROCESOS_GENERAL') && in_array($perfil, ['GESTOR', 'ADMIN'], true)): ?>
        <div class="card-moderna module-procesos" onclick="location.href='procesos_general.php'">
            <div class="card-top">
                <span class="icon">&#128196;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Procesos General</h3>
            <p>Consulta procesos pendientes, firmados, rechazados y eliminados.</p>
        </div>
    <?php endif; ?>

    <?php if (has_module('FIRMAR') && $perfil === 'FIRMANTE'): ?>
        <div class="card-moderna module-firmar" onclick="location.href='firmante_documentos.php'">
            <div class="card-top">
                <span class="icon">&#9997;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Firmar Documentos</h3>
            <p>Revisa tu bandeja de documentos y responde solicitudes de firma.</p>
        </div>

        <div class="card-moderna module-digital" onclick="location.href='firmar_documento.php'">
            <div class="card-top">
                <span class="icon">&#128395;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Firma Digital</h3>
            <p>Abre documentos mediante enlace o token para completar la firma.</p>
        </div>
    <?php endif; ?>

    <?php if (has_module('FIRMAR') && $perfil === 'ADMIN'): ?>
        <div class="card-moderna module-firmar" onclick="location.href='firmante_documentos.php'">
            <div class="card-top">
                <span class="icon">&#9997;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Firmar Documentos</h3>
            <p>Revisa documentos asignados y solicitudes disponibles para firma.</p>
        </div>

        <div class="card-moderna module-digital" onclick="location.href='firmar_documento.php'">
            <div class="card-top">
                <span class="icon">&#128395;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Firma Digital</h3>
            <p>Gestiona la firma digital de documentos con vista enfocada.</p>
        </div>
    <?php endif; ?>

    <?php if (has_module('ADMINISTRACION')): ?>
        <div class="card-moderna module-admin" onclick="location.href='PANELADMINISTRADOR/admin_panel.php'">
            <div class="card-top">
                <span class="icon">&#9881;</span>
                <span class="arrow">&#8594;</span>
            </div>
            <h3>Administracion</h3>
            <p>Administra usuarios, perfiles y configuraciones principales.</p>
        </div>
    <?php endif; ?>
    </section>
</main>
</div>

</body>
</html>
