<?php

declare(strict_types=1);

function firmape_sidebar_items(string $basePath = ''): array
{
    $items = [];

    if (has_module('GESTION') && in_array(current_profile(), ['GESTOR', 'ADMIN'], true)) {
        $items[] = ['key' => 'gestion', 'label' => 'Gestion', 'icon' => '&#128194;', 'href' => $basePath . 'gestion.php'];
    }
    if (has_module('PROCESOS_GENERAL') && in_array(current_profile(), ['GESTOR', 'ADMIN'], true)) {
        $items[] = ['key' => 'procesos', 'label' => 'Procesos', 'icon' => '&#128196;', 'href' => $basePath . 'procesos_general.php'];
    }
    if (has_module('FIRMAR') && in_array(current_profile(), ['FIRMANTE', 'ADMIN'], true)) {
        $items[] = ['key' => 'firmar', 'label' => 'Firmar', 'icon' => '&#9997;', 'href' => $basePath . 'firmante_documentos.php'];
        $items[] = ['key' => 'firma_digital', 'label' => 'Firma digital', 'icon' => '&#128395;', 'href' => $basePath . 'firmar_documento.php'];
    }
    if (has_module('ADMINISTRACION')) {
        $items[] = ['key' => 'admin', 'label' => 'Administracion', 'icon' => '&#9881;', 'href' => $basePath . 'PANELADMINISTRADOR/admin_panel.php'];
    }

    return $items;
}

function render_firmape_sidebar_styles(): void
{
    ?>
    html, body { max-width:100%; overflow-x:hidden; }
    body { overflow:hidden; }
    .app-shell { height: calc(100vh - 67px); display: grid; grid-template-columns: 282px minmax(0, 1fr); overflow:hidden; }
    .firmape-sidebar { height: calc(100vh - 67px); background:#182035; color:#e8eefb; box-shadow:16px 0 34px rgba(15,23,42,.16); overflow:hidden; align-self:start; display:flex; flex-direction:column; }
    .firmape-sidebar-brand { padding:24px 22px 20px; display:flex; align-items:center; gap:12px; border-bottom:1px solid rgba(255,255,255,.08); }
    .firmape-sidebar-brand img { width:34px; height:34px; }
    .firmape-sidebar-brand strong { display:block; letter-spacing:.4px; font-size:18px; }
    .firmape-sidebar-brand span { display:block; margin-top:2px; color:#94a3b8; font-size:12px; font-weight:700; }
    .firmape-sidebar-nav { padding:14px 12px; display:grid; gap:6px; }
    .firmape-side-link { width:100%; border:0; border-radius:9px; background:transparent; color:#cbd5e1; padding:14px; display:flex; align-items:center; gap:12px; cursor:pointer; text-align:left; font-weight:800; transition:.2s ease; text-decoration:none; font-family:inherit; font-size:14px; }
    .firmape-side-link:hover { background:rgba(255,255,255,.08); color:white; transform:translateX(2px); }
    .firmape-side-link.active { background:#2f8cff; color:white; box-shadow:0 10px 20px rgba(47,140,255,.25); }
    .firmape-side-icon { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; font-size:17px; }
    .firmape-side-text { display:grid; gap:2px; }
    .firmape-side-text small { color:rgba(226,232,240,.75); font-size:11px; font-weight:700; }
    .firmape-side-link.active small { color:rgba(255,255,255,.86); }
    .firmape-count { margin-left:auto; min-width:22px; height:22px; padding:0 7px; border-radius:999px; background:rgba(255,255,255,.16); color:white; display:inline-flex; align-items:center; justify-content:center; font-size:12px; }
    .firmape-sidebar-footer { margin-top:auto; padding:18px 22px 24px; color:#94a3b8; font-size:12px; font-weight:700; border-top:1px solid rgba(255,255,255,.08); }
    .module-content { min-width:0; height:100%; padding:28px 34px 36px; overflow:auto; scrollbar-width:none; }
    .module-content::-webkit-scrollbar { width:0; height:0; }
    .module-content .container { width:100%; }
    .module-content table { min-width:760px; }
    .module-content .card { overflow:auto; scrollbar-width:none; }
    .module-content .card::-webkit-scrollbar { width:0; height:0; }
    .module-content .pager { gap:14px; flex-wrap:wrap; }
    @media (max-width: 860px) {
        .app-shell { grid-template-columns:1fr; }
        .firmape-sidebar { position:static; min-height:0; display:block; }
        .firmape-sidebar-brand { display:none; }
        .firmape-sidebar-nav { display:flex; overflow-x:auto; padding:12px; }
        .firmape-side-link { min-width:max-content; }
        .firmape-side-text small, .firmape-sidebar-footer { display:none; }
        .module-content { padding:18px 14px 28px; }
    }
    <?php
}

function render_firmape_sidebar(string $active = 'home', string $basePath = ''): void
{
    $items = firmape_sidebar_items($basePath);
    ?>
    <div class="app-shell">
        <aside class="firmape-sidebar">
            <div class="firmape-sidebar-brand">
                <img src="<?= e($basePath . 'imagenes/favicon.png') ?>" alt="logo">
                <div>
                    <strong>FIRMAPE</strong>
                    <span>Centro de trabajo</span>
                </div>
            </div>
            <nav class="firmape-sidebar-nav">
                <a class="firmape-side-link <?= $active === 'home' ? 'active' : '' ?>" href="<?= e($basePath . 'principal.php') ?>">
                    <span class="firmape-side-icon">&#8962;</span>
                    <span class="firmape-side-text">
                        <span>Home</span>
                        <small>Dashboard</small>
                    </span>
                    <span class="firmape-count"><?= count($items) ?></span>
                </a>
                <?php foreach ($items as $item): ?>
                    <a class="firmape-side-link <?= $active === $item['key'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                        <span class="firmape-side-icon"><?= $item['icon'] ?></span>
                        <span class="firmape-side-text">
                            <span><?= e($item['label']) ?></span>
                            <small>Abrir modulo</small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="firmape-sidebar-footer">
                Perfil activo: <?= e(current_profile()) ?>
            </div>
        </aside>
    <?php
}
