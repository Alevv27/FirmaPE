<?php

declare(strict_types=1);

function render_firmape_topbar_styles(): void
{
    ?>
    .firmape-topbar {
        box-sizing: border-box;
        height: 67px;
        padding: 12px 40px;
        display: grid;
        grid-template-columns: minmax(210px, 1fr) minmax(320px, 2fr) minmax(360px, 1fr);
        align-items: center;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,.15);
        color: #000;
        position: relative;
        z-index: 10;
    }
    .firmape-topbar * { box-sizing: border-box; }
    .firmape-topbar-logo { display:flex; align-items:center; gap:12px; }
    .firmape-topbar-logo img { width:38px; }
    .firmape-topbar-logo h3 { margin:0; font-size:22px; letter-spacing:1px; font-weight:900; }
    .firmape-topbar-clock { text-align:center; font-size:15px; font-weight:700; color:#111827; }
    .firmape-topbar-user { display:flex; align-items:center; justify-content:flex-end; gap:18px; min-width:0; white-space:nowrap; }
    .firmape-profile-link { text-decoration:none; color:#000; display:flex; align-items:center; gap:10px; font-size:15px; border:0; background:transparent; padding:0; cursor:pointer; font-family:inherit; white-space:nowrap; }
    .firmape-profile-link span { display:inline-flex; align-items:center; gap:5px; white-space:nowrap; }
    .firmape-user-avatar { width:40px; height:40px; border-radius:999px; background:#0b3266; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:900; box-shadow:0 4px 12px rgba(15,23,42,.22); border:2px solid #e0f2fe; overflow:hidden; flex:0 0 auto; }
    .firmape-user-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
    .firmape-account-wrap { position:relative; }
    .firmape-account-menu { position:absolute; right:0; top:calc(100% + 10px); width:270px; background:white; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 18px 45px rgba(15,23,42,.18); padding:10px; display:none; z-index:20; }
    .firmape-account-menu.show { display:block; }
    .firmape-account-title { padding:8px 10px; font-size:12px; font-weight:900; color:#64748b; text-transform:uppercase; letter-spacing:.5px; }
    .firmape-account-item { width:100%; border:0; background:transparent; display:flex; align-items:center; gap:12px; padding:12px 10px; border-radius:9px; cursor:pointer; text-align:left; color:#0f172a; font-weight:800; font-family:inherit; }
    .firmape-account-item:hover { background:#f1f5f9; }
    .firmape-account-item.danger { color:#dc2626; text-decoration:none; }
    .firmape-account-item.danger .firmape-account-icon { background:#fee2e2; color:#dc2626; }
    .firmape-account-icon { width:28px; height:28px; border-radius:9px; background:#e0f2fe; color:#0f69b5; display:inline-flex; align-items:center; justify-content:center; font-weight:900; flex:0 0 auto; }
    .firmape-account-icon svg, .firmape-profile-tab-icon svg { width:16px; height:16px; stroke:currentColor; }
    .firmape-account-separator { height:1px; background:#e2e8f0; margin:8px 0; }
    .firmape-badge { font-size:10px; color:white; padding:2px 6px; border-radius:4px; margin-left:5px; vertical-align:middle; text-transform:uppercase; }
    .firmape-badge-admin { background:#6366f1; }
    .firmape-badge-firmante { background:#10b981; }
    .firmape-badge-gestor { background:#0ea5e9; }
    .firmape-badge-usuario { background:#6b7280; }
    .firmape-profile-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; padding:22px; z-index:3000; }
    .firmape-profile-backdrop.show { display:flex; }
    .firmape-profile-modal { width:100%; max-width:760px; background:rgba(255,255,255,.98); border-radius:18px; box-shadow:0 30px 70px rgba(15,23,42,.32); overflow:hidden; color:#111827; }
    .firmape-profile-head { padding:20px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0; }
    .firmape-profile-head h2 { margin:0; font-size:21px; font-weight:900; }
    .firmape-profile-close { width:34px; height:34px; border:0; border-radius:999px; background:#f1f5f9; cursor:pointer; font-size:20px; font-weight:900; color:#475569; }
    .firmape-profile-body { padding:22px 24px 24px; display:grid; grid-template-columns:210px 1fr; gap:22px; }
    .firmape-profile-nav { border-right:1px solid #e2e8f0; padding-right:14px; }
    .firmape-profile-nav-title { margin:0 0 10px; font-size:12px; color:#64748b; text-transform:uppercase; letter-spacing:.5px; font-weight:900; }
    .firmape-profile-tab { width:100%; border:0; background:transparent; border-radius:10px; padding:12px 10px; display:flex; align-items:center; gap:10px; cursor:pointer; color:#0f172a; font-weight:800; text-align:left; font-family:inherit; }
    .firmape-profile-tab:hover, .firmape-profile-tab.active { background:#eff6ff; color:#0f69b5; }
    .firmape-profile-tab-icon { width:28px; height:28px; border-radius:9px; background:#e0f2fe; display:inline-flex; align-items:center; justify-content:center; color:#0f69b5; font-weight:900; }
    .firmape-profile-section { display:none; min-width:0; }
    .firmape-profile-section.active { display:block; }
    .firmape-profile-field { margin-bottom:14px; }
    .firmape-profile-field label { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:900; margin-bottom:7px; }
    .firmape-profile-field input { width:100%; padding:12px 13px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; outline:none; background:white; }
    .firmape-profile-field input:focus { border-color:#2f8cff; box-shadow:0 0 0 3px rgba(47,140,255,.14); }
    .firmape-profile-field input[readonly] { background:#f8fafc; color:#64748b; cursor:not-allowed; }
    .firmape-photo-card { margin-bottom:16px; padding:14px; border:1px solid #dbe3ef; border-radius:14px; background:#f8fafc; display:grid; grid-template-columns:76px 1fr; gap:14px; align-items:center; }
    .firmape-photo-preview { width:72px; height:72px; border-radius:999px; background:#0b3266; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:20px; overflow:hidden; border:3px solid #e0f2fe; }
    .firmape-photo-preview img { width:100%; height:100%; object-fit:cover; display:block; }
    .firmape-photo-copy strong { display:block; font-size:14px; color:#0f172a; margin-bottom:3px; }
    .firmape-photo-copy small { display:block; color:#64748b; font-weight:700; margin-bottom:10px; }
    .firmape-photo-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .firmape-photo-input { max-width:220px; font-size:12px; color:#334155; }
    .firmape-photo-btn { background:#0f69b5; color:#fff; padding:10px 13px; border:0; border-radius:9px; font-size:12px; font-weight:900; cursor:pointer; }
    .firmape-photo-btn svg { width:14px; height:14px; margin-right:6px; vertical-align:-2px; }
    .firmape-photo-btn:disabled { opacity:.7; cursor:wait; }
    .firmape-crop-backdrop { position:fixed; inset:0; background:rgba(3,7,18,.82); display:none; align-items:center; justify-content:center; z-index:4000; padding:18px; }
    .firmape-crop-backdrop.show { display:flex; }
    .firmape-crop-modal { width:min(520px, 96vw); height:min(560px, 92vh); background:#111827; color:#fff; border-radius:16px; overflow:hidden; box-shadow:0 28px 70px rgba(0,0,0,.42); display:grid; grid-template-rows:auto 1fr auto; }
    .firmape-crop-head { padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; background:#0b1220; }
    .firmape-crop-head strong { font-size:15px; font-weight:900; }
    .firmape-crop-close, .firmape-crop-confirm { border:0; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
    .firmape-crop-close { width:36px; height:36px; border-radius:999px; background:#1f2937; color:#fff; font-size:22px; }
    .firmape-crop-stage { position:relative; overflow:hidden; min-height:0; background:#171717; touch-action:none; cursor:grab; }
    .firmape-crop-stage.dragging { cursor:grabbing; }
    .firmape-crop-stage img { position:absolute; left:0; top:0; max-width:none; user-select:none; pointer-events:none; transform-origin:center center; }
    .firmape-crop-mask { position:absolute; inset:0; pointer-events:none; background:radial-gradient(circle at center, transparent 0 145px, rgba(0,0,0,.58) 146px); }
    .firmape-crop-ring { position:absolute; left:50%; top:50%; width:292px; height:292px; transform:translate(-50%,-50%); border:2px solid rgba(255,255,255,.82); border-radius:999px; pointer-events:none; box-shadow:0 0 0 1px rgba(15,23,42,.12); }
    .firmape-crop-controls { padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:14px; background:#0b1220; }
    .firmape-crop-zoom { flex:1; display:flex; align-items:center; gap:10px; }
    .firmape-crop-zoom button { width:34px; height:34px; border:0; border-radius:10px; background:#1f2937; color:#fff; font-size:18px; cursor:pointer; }
    .firmape-crop-zoom input { width:100%; accent-color:#22c55e; }
    .firmape-crop-confirm { width:58px; height:58px; border-radius:999px; background:#22c55e; color:white; font-size:32px; box-shadow:0 12px 30px rgba(34,197,94,.3); }
    .firmape-code-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.62); display:none; align-items:center; justify-content:center; z-index:4100; padding:20px; }
    .firmape-code-backdrop.show { display:flex; }
    .firmape-code-modal { width:min(430px, 94vw); background:#fff; border-radius:16px; box-shadow:0 26px 70px rgba(15,23,42,.35); overflow:hidden; color:#0f172a; }
    .firmape-code-head { padding:20px 22px 12px; display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
    .firmape-code-head h3 { margin:0; font-size:19px; font-weight:900; }
    .firmape-code-head p { margin:6px 0 0; color:#64748b; font-size:13px; line-height:1.35; }
    .firmape-code-close { width:34px; height:34px; border:0; border-radius:999px; background:#f1f5f9; color:#475569; font-size:20px; font-weight:900; cursor:pointer; }
    .firmape-code-body { padding:0 22px 22px; display:grid; gap:14px; }
    .firmape-code-input { width:100%; padding:14px 15px; border:1px solid #cbd5e1; border-radius:11px; font-size:18px; letter-spacing:5px; text-align:center; font-weight:900; outline:none; }
    .firmape-code-input:focus { border-color:#0f69b5; box-shadow:0 0 0 3px rgba(15,105,181,.14); }
    .firmape-code-actions { display:flex; justify-content:flex-end; gap:10px; }
    .firmape-code-actions button { border:0; border-radius:10px; padding:11px 16px; font-weight:900; cursor:pointer; }
    .firmape-code-cancel { background:#f1f5f9; color:#334155; }
    .firmape-code-confirm { background:#0f69b5; color:#fff; }
    .firmape-profile-status { display:none; margin:2px 0 14px; padding:10px 12px; border-radius:10px; font-size:13px; font-weight:800; }
    .firmape-profile-status.show { display:block; }
    .firmape-profile-status.success { background:#dcfce7; color:#166534; }
    .firmape-profile-status.error { background:#fee2e2; color:#991b1b; }
    .firmape-profile-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:18px; border-top:1px solid #e2e8f0; }
    .firmape-profile-btn { padding:12px 18px; border-radius:10px; font-size:13px; font-weight:800; border:0; cursor:pointer; }
    .firmape-profile-cancel { background:#f1f5f9; color:#334155; }
    .firmape-profile-save { background:#1e293b; color:#fff; }
    .firmape-profile-save:disabled { opacity:.7; cursor:wait; }
    .firmape-cert-box { margin-top:18px; padding-top:18px; border-top:1px solid #e2e8f0; display:grid; gap:12px; }
    .firmape-cert-title { font-size:13px; color:#334155; font-weight:900; text-transform:uppercase; letter-spacing:.4px; }
    .firmape-cert-card { border:1px solid #dbe3ef; border-radius:12px; padding:13px; background:#f8fafc; display:grid; gap:8px; }
    .firmape-cert-status { font-size:13px; font-weight:900; color:#64748b; }
    .firmape-cert-status.ok { color:#047857; }
    .firmape-cert-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .firmape-cert-grid input { width:100%; padding:11px 12px; border:1px solid #cbd5e1; border-radius:10px; outline:none; }
    .firmape-cert-enroll { background:#0f69b5; color:white; }
    .firmape-cert-list { display:grid; gap:8px; }
    .firmape-cert-row { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; padding:11px 12px; border:1px solid #e2e8f0; border-radius:10px; background:white; }
    .firmape-cert-row strong { display:block; color:#0f172a; font-size:13px; }
    .firmape-cert-row small { color:#64748b; font-weight:700; }
    .firmape-cert-delete { width:34px; height:34px; border:0; border-radius:9px; background:#fee2e2; color:#dc2626; font-size:18px; font-weight:900; cursor:pointer; }
    .firmape-cert-delete:hover { background:#fecaca; }
    @media (max-width: 860px) {
        .firmape-topbar { height:auto; grid-template-columns:1fr; gap:10px; padding:12px 18px; text-align:center; }
        .firmape-topbar-logo, .firmape-topbar-user { justify-content:center; }
        .firmape-account-menu { right:50%; transform:translateX(50%); }
        .firmape-profile-body { grid-template-columns:1fr; }
        .firmape-profile-nav { border-right:0; border-bottom:1px solid #e2e8f0; padding:0 0 14px; }
    }
    <?php
}

function firmape_account_icon(string $name): string
{
    $icons = [
        'user' => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
        'lock' => '<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'camera' => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
    ];

    $path = $icons[$name] ?? $icons['user'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function render_firmape_topbar(string $basePath = ''): void
{
    $usuario = current_user() ?? [];
    $perfil = current_profile();
    $nombre = explode(' ', trim($usuario['nombre'] ?? 'Usuario'))[0] ?: 'Usuario';
    $nombreCompleto = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
    if ($nombreCompleto === '') {
        $nombreCompleto = 'Usuario';
    }
    $partesNombre = preg_split('/\s+/', trim($nombreCompleto)) ?: [];
    $iniciales = '';
    foreach (array_slice(array_filter($partesNombre), 0, 2) as $parte) {
        $iniciales .= strtoupper(substr($parte, 0, 1));
    }
    if ($iniciales === '') {
        $iniciales = 'U';
    }
    $fotoPerfil = trim((string) ($usuario['fotoPerfil'] ?? $usuario['foto_perfil'] ?? ''));
    $fotoSrc = '';
    if ($fotoPerfil !== '') {
        $fotoSrc = preg_match('/^https?:\/\//i', $fotoPerfil) ? $fotoPerfil : $basePath . ltrim($fotoPerfil, '/');
    }
    $badgeClass = [
        'ADMIN' => 'firmape-badge-admin',
        'FIRMANTE' => 'firmape-badge-firmante',
        'GESTOR' => 'firmape-badge-gestor',
    ][$perfil] ?? 'firmape-badge-usuario';
    $profileEndpoint = $basePath . 'perfil_actualizar.php';
    $photoEndpoint = $basePath . 'perfil_foto.php';
    $passwordEndpoint = $basePath . 'contrasena_actualizar.php';
    $certEndpoint = $basePath . 'certificado_usuario.php';
    ?>
    <header class="firmape-topbar">
        <div class="firmape-topbar-logo">
            <img src="<?= e($basePath . 'imagenes/favicon.png') ?>" alt="logo">
            <h3>FIRMAPE</h3>
        </div>
        <div class="firmape-topbar-clock"><div id="hora"></div></div>
        <div class="firmape-topbar-user">
            <button type="button" class="firmape-profile-link" onclick="toggleCuentaFirmape(event)">
                <span>Hola, <b><?= e($nombre) ?></b><span class="firmape-badge <?= e($badgeClass) ?>"><?= e($perfil) ?></span></span>
                <div class="firmape-user-avatar" id="firmapeTopbarAvatar" title="Mi cuenta">
                    <?php if ($fotoSrc !== ''): ?>
                    <img src="<?= e($fotoSrc) ?>" alt="Foto de perfil">
                    <?php else: ?>
                    <?= e($iniciales) ?>
                    <?php endif; ?>
                </div>
            </button>
            <div class="firmape-account-wrap">
                <div class="firmape-account-menu" id="firmapeCuentaMenu">
                    <div class="firmape-account-title">Cuenta</div>
                    <button type="button" class="firmape-account-item" onclick="abrirPerfilFirmape('datos')"><span class="firmape-account-icon"><?= firmape_account_icon('user') ?></span>Mis datos</button>
                    <button type="button" class="firmape-account-item" onclick="abrirPerfilFirmape('password')"><span class="firmape-account-icon"><?= firmape_account_icon('lock') ?></span>Cambiar contraseña</button>
                    <?php if ($perfil === 'FIRMANTE'): ?>
                    <div class="firmape-account-separator"></div>
                    <div class="firmape-account-title">Seguridad y certificados</div>
                    <button type="button" class="firmape-account-item" onclick="abrirPerfilFirmape('certificados')"><span class="firmape-account-icon"><?= firmape_account_icon('shield') ?></span>Mis certificados</button>
                    <?php endif; ?>
                    <div class="firmape-account-separator"></div>
                    <a class="firmape-account-item danger" href="<?= e($basePath . 'logout.php') ?>"><span class="firmape-account-icon"><?= firmape_account_icon('logout') ?></span>Cerrar sesión</a>
                </div>
            </div>
        </div>
    </header>
    <div class="firmape-profile-backdrop" id="firmapePerfilModal" onclick="cerrarPerfilFirmape(event)">
        <div class="firmape-profile-modal" role="dialog" aria-modal="true" aria-labelledby="firmapePerfilTitulo">
            <div class="firmape-profile-head">
                <h2 id="firmapePerfilTitulo">Mi cuenta</h2>
                <button type="button" class="firmape-profile-close" onclick="cerrarPerfilFirmape()">×</button>
            </div>
            <div class="firmape-profile-body">
                <nav class="firmape-profile-nav">
                    <p class="firmape-profile-nav-title">Cuenta</p>
                    <button type="button" class="firmape-profile-tab" data-section="datos" onclick="mostrarSeccionCuenta('datos')"><span class="firmape-profile-tab-icon"><?= firmape_account_icon('user') ?></span>Mis datos</button>
                    <button type="button" class="firmape-profile-tab" data-section="password" onclick="mostrarSeccionCuenta('password')"><span class="firmape-profile-tab-icon"><?= firmape_account_icon('lock') ?></span>Cambiar contraseña</button>
                    <?php if ($perfil === 'FIRMANTE'): ?>
                    <p class="firmape-profile-nav-title" style="margin-top:18px;">Seguridad</p>
                    <button type="button" class="firmape-profile-tab" data-section="certificados" onclick="mostrarSeccionCuenta('certificados')"><span class="firmape-profile-tab-icon"><?= firmape_account_icon('shield') ?></span>Mis certificados</button>
                    <?php endif; ?>
                </nav>
                <div>
                    <div class="firmape-profile-status" id="firmapePerfilStatus"></div>

                    <section class="firmape-profile-section" id="firmapeSectionDatos">
                        <div class="firmape-photo-card">
                            <div class="firmape-photo-preview" id="firmapePhotoPreview">
                                <?php if ($fotoSrc !== ''): ?>
                                <img src="<?= e($fotoSrc) ?>" alt="Foto de perfil">
                                <?php else: ?>
                                <?= e($iniciales) ?>
                                <?php endif; ?>
                            </div>
                            <div class="firmape-photo-copy">
                                <strong>Foto de perfil</strong>
                                <small>Usa una imagen JPG, PNG o WEBP de hasta 2 MB.</small>
                                <form class="firmape-photo-form" id="firmapeFotoForm" enctype="multipart/form-data">
                                    <input class="firmape-photo-input" type="file" name="foto_perfil" id="firmapeFotoInput" accept="image/jpeg,image/png,image/webp" required>
                                    <button type="submit" class="firmape-photo-btn" id="firmapeFotoGuardar">
                                        <?= firmape_account_icon('camera') ?> Actualizar foto
                                    </button>
                                </form>
                            </div>
                        </div>
                        <form id="firmapePerfilForm">
                            <div class="firmape-profile-field">
                                <label>Nombre completo</label>
                                <input type="text" value="<?= e($nombreCompleto) ?>" readonly>
                            </div>
                            <div class="firmape-profile-field">
                                <label>DNI</label>
                                <input type="text" value="<?= e($usuario['dni'] ?? '') ?>" readonly>
                            </div>
                            <div class="firmape-profile-field">
                                <label>Perfil</label>
                                <input type="text" value="<?= e($perfil) ?>" readonly>
                            </div>
                            <div class="firmape-profile-field">
                                <label>Empresa ID</label>
                                <input type="text" value="<?= e((string) ($usuario['empresaId'] ?? '')) ?>" readonly>
                            </div>
                            <div class="firmape-profile-field">
                                <label>Correo electronico</label>
                                <input type="email" name="email" id="firmapePerfilEmail" value="<?= e($usuario['email'] ?? '') ?>" required>
                            </div>
                            <div class="firmape-profile-actions">
                                <button type="button" class="firmape-profile-btn firmape-profile-cancel" onclick="cerrarPerfilFirmape()">Cancelar</button>
                                <button type="submit" class="firmape-profile-btn firmape-profile-save" id="firmapePerfilGuardar">Guardar cambios</button>
                            </div>
                        </form>
                    </section>

                    <section class="firmape-profile-section" id="firmapeSectionPassword">
                        <form id="firmapePasswordForm">
                            <div class="firmape-profile-field">
                                <label>Contraseña actual</label>
                                <input type="password" name="password_actual" autocomplete="current-password" required>
                            </div>
                            <div class="firmape-profile-field">
                                <label>Nueva contraseña</label>
                                <input type="password" name="password_nueva" autocomplete="new-password" required>
                            </div>
                            <div class="firmape-profile-field">
                                <label>Confirmar nueva contraseña</label>
                                <input type="password" name="password_confirmar" autocomplete="new-password" required>
                            </div>
                            <div class="firmape-profile-actions">
                                <button type="button" class="firmape-profile-btn firmape-profile-cancel" onclick="cerrarPerfilFirmape()">Cancelar</button>
                                <button type="submit" class="firmape-profile-btn firmape-profile-save" id="firmapePasswordGuardar">Actualizar contraseña</button>
                            </div>
                        </form>
                    </section>

                    <?php if ($perfil === 'FIRMANTE'): ?>
                    <section class="firmape-profile-section" id="firmapeSectionCertificados">
                        <div class="firmape-cert-box" style="margin-top:0;padding-top:0;border-top:0;">
                            <div class="firmape-cert-title">Mis certificados</div>
                            <div class="firmape-cert-card">
                                <div class="firmape-cert-status" id="firmapeCertStatus">Consultando certificado...</div>
                                <div id="firmapeCertInfo" class="firmape-cert-list"></div>
                                <div class="firmape-cert-grid" id="firmapeCertForm">
                                    <input type="text" id="firmapeCertAlias" value="Certificado servidor FIRMAPE" placeholder="Nombre del certificado">
                                    <input type="password" id="firmapeCertPin" placeholder="PIN de 4 a 8 digitos" inputmode="numeric" maxlength="8">
                                </div>
                                <button type="button" class="firmape-profile-btn firmape-cert-enroll" id="firmapeCertEnroll">Agregar certificado servidor</button>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="firmape-crop-backdrop" id="firmapeCropModal">
        <div class="firmape-crop-modal" role="dialog" aria-modal="true">
            <div class="firmape-crop-head">
                <button type="button" class="firmape-crop-close" onclick="cerrarCropFirmape()">×</button>
                <strong>Arrastra la imagen para ajustar.</strong>
                <span style="width:36px"></span>
            </div>
            <div class="firmape-crop-stage" id="firmapeCropStage">
                <img id="firmapeCropImage" alt="Ajustar foto">
                <div class="firmape-crop-mask"></div>
                <div class="firmape-crop-ring"></div>
            </div>
            <div class="firmape-crop-controls">
                <div class="firmape-crop-zoom">
                    <button type="button" onclick="cambiarZoomFirmape(-0.1)">−</button>
                    <input type="range" id="firmapeCropZoom" min="1" max="3" step="0.01" value="1">
                    <button type="button" onclick="cambiarZoomFirmape(0.1)">+</button>
                </div>
                <button type="button" class="firmape-crop-confirm" onclick="confirmarCropFirmape()">✓</button>
            </div>
        </div>
    </div>
    <div class="firmape-code-backdrop" id="firmapeCodeModal">
        <div class="firmape-code-modal" role="dialog" aria-modal="true" aria-labelledby="firmapeCodeTitle">
            <div class="firmape-code-head">
                <div>
                    <h3 id="firmapeCodeTitle">Confirmar certificado</h3>
                    <p id="firmapeCodeText">Ingresa el codigo enviado a tu correo.</p>
                </div>
                <button type="button" class="firmape-code-close" onclick="resolverCodigoCertificadoFirmape(null)">×</button>
            </div>
            <div class="firmape-code-body">
                <input type="text" id="firmapeCodeInput" class="firmape-code-input" inputmode="numeric" maxlength="6" placeholder="000000">
                <div class="firmape-code-actions">
                    <button type="button" class="firmape-code-cancel" onclick="resolverCodigoCertificadoFirmape(null)">Cancelar</button>
                    <button type="button" class="firmape-code-confirm" onclick="resolverCodigoCertificadoFirmape()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    const firmapePerfilEndpoint = <?= json_encode($profileEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const firmapeFotoEndpoint = <?= json_encode($photoEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const firmapeAvatarFallback = <?= json_encode($iniciales, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const firmapePasswordEndpoint = <?= json_encode($passwordEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const firmapeCertEndpoint = <?= json_encode($certEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const firmapePuedeCertificado = <?= $perfil === 'FIRMANTE' ? 'true' : 'false' ?>;
    let firmapeFotoRecortada = null;
    let firmapeCropState = { url: '', naturalWidth: 0, naturalHeight: 0, scale: 1, minScale: 1, x: 0, y: 0, dragging: false, startX: 0, startY: 0 };
    let firmapeCodeResolver = null;

    function actualizarHora() {
        const now = new Date();
        const opciones = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
        let fecha = now.toLocaleDateString('es-PE', opciones);
        let hora = now.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const target = document.getElementById("hora");
        if (target) target.innerHTML = fecha.charAt(0).toUpperCase() + fecha.slice(1) + " | " + hora.toUpperCase();
    }
    setInterval(actualizarHora, 1000);
    actualizarHora();

    function toggleCuentaFirmape(event) {
        event?.stopPropagation();
        document.getElementById('firmapeCuentaMenu')?.classList.toggle('show');
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.firmape-topbar-user')) {
            document.getElementById('firmapeCuentaMenu')?.classList.remove('show');
        }
    });

    function mostrarSeccionCuenta(section) {
        document.querySelectorAll('.firmape-profile-section').forEach((item) => item.classList.remove('active'));
        document.querySelectorAll('.firmape-profile-tab').forEach((item) => item.classList.toggle('active', item.dataset.section === section));
        const target = document.getElementById('firmapeSection' + section.charAt(0).toUpperCase() + section.slice(1));
        if (target) target.classList.add('active');
        if (section === 'certificados' && firmapePuedeCertificado) cargarCertificadoFirmape();
    }

    function abrirPerfilFirmape(section = 'datos') {
        document.getElementById('firmapeCuentaMenu')?.classList.remove('show');
        const modal = document.getElementById('firmapePerfilModal');
        const status = document.getElementById('firmapePerfilStatus');
        if (status) {
            status.className = 'firmape-profile-status';
            status.textContent = '';
        }
        modal.classList.add('show');
        mostrarSeccionCuenta(section);
        setTimeout(() => document.getElementById('firmapePerfilEmail')?.focus(), 40);
    }

    function cerrarPerfilFirmape(event) {
        if (event && event.target.id !== 'firmapePerfilModal') return;
        document.getElementById('firmapePerfilModal')?.classList.remove('show');
    }

    function mostrarEstadoPerfil(tipo, texto) {
        const status = document.getElementById('firmapePerfilStatus');
        status.textContent = texto;
        status.className = 'firmape-profile-status show ' + tipo;
    }

    function actualizarAvatarFirmape(src) {
        const topbarAvatar = document.getElementById('firmapeTopbarAvatar');
        const photoPreview = document.getElementById('firmapePhotoPreview');
        const content = src
            ? '<img src="' + src + '" alt="Foto de perfil">'
            : firmapeAvatarFallback;
        if (topbarAvatar) topbarAvatar.innerHTML = content;
        if (photoPreview) photoPreview.innerHTML = content;
    }

    function abrirCropFirmape(file) {
        const modal = document.getElementById('firmapeCropModal');
        const image = document.getElementById('firmapeCropImage');
        const zoom = document.getElementById('firmapeCropZoom');
        if (!modal || !image || !zoom) return;
        if (firmapeCropState.url) URL.revokeObjectURL(firmapeCropState.url);
        firmapeCropState.url = URL.createObjectURL(file);
        image.onload = () => {
            const stage = document.getElementById('firmapeCropStage');
            const rect = stage.getBoundingClientRect();
            const cropSize = 292;
            firmapeCropState.naturalWidth = image.naturalWidth;
            firmapeCropState.naturalHeight = image.naturalHeight;
            firmapeCropState.minScale = Math.max(cropSize / image.naturalWidth, cropSize / image.naturalHeight);
            firmapeCropState.scale = firmapeCropState.minScale;
            firmapeCropState.x = rect.width / 2;
            firmapeCropState.y = rect.height / 2;
            zoom.min = String(firmapeCropState.minScale);
            zoom.max = String(firmapeCropState.minScale * 3);
            zoom.value = String(firmapeCropState.scale);
            pintarCropFirmape();
        };
        image.src = firmapeCropState.url;
        modal.classList.add('show');
    }

    function cerrarCropFirmape() {
        document.getElementById('firmapeCropModal')?.classList.remove('show');
    }

    function pintarCropFirmape() {
        const image = document.getElementById('firmapeCropImage');
        const stage = document.getElementById('firmapeCropStage');
        if (!image || !stage) return;
        const imgW = firmapeCropState.naturalWidth * firmapeCropState.scale;
        const imgH = firmapeCropState.naturalHeight * firmapeCropState.scale;
        const rect = stage.getBoundingClientRect();
        const cropSize = 292;
        const cropLeft = rect.width / 2 - cropSize / 2;
        const cropRight = rect.width / 2 + cropSize / 2;
        const cropTop = rect.height / 2 - cropSize / 2;
        const cropBottom = rect.height / 2 + cropSize / 2;
        firmapeCropState.x = Math.min(cropLeft + imgW / 2, Math.max(cropRight - imgW / 2, firmapeCropState.x));
        firmapeCropState.y = Math.min(cropTop + imgH / 2, Math.max(cropBottom - imgH / 2, firmapeCropState.y));
        image.style.width = imgW + 'px';
        image.style.height = imgH + 'px';
        image.style.transform = 'translate(' + (firmapeCropState.x - imgW / 2) + 'px, ' + (firmapeCropState.y - imgH / 2) + 'px)';
    }

    function cambiarZoomFirmape(delta) {
        const zoom = document.getElementById('firmapeCropZoom');
        if (!zoom) return;
        const value = Math.min(parseFloat(zoom.max), Math.max(parseFloat(zoom.min), parseFloat(zoom.value) + delta));
        zoom.value = String(value);
        firmapeCropState.scale = value;
        pintarCropFirmape();
    }

    document.getElementById('firmapeCropZoom')?.addEventListener('input', (event) => {
        firmapeCropState.scale = parseFloat(event.target.value);
        pintarCropFirmape();
    });

    document.getElementById('firmapeCropStage')?.addEventListener('pointerdown', (event) => {
        const stage = event.currentTarget;
        firmapeCropState.dragging = true;
        firmapeCropState.startX = event.clientX - firmapeCropState.x;
        firmapeCropState.startY = event.clientY - firmapeCropState.y;
        stage.classList.add('dragging');
        stage.setPointerCapture(event.pointerId);
    });

    document.getElementById('firmapeCropStage')?.addEventListener('pointermove', (event) => {
        if (!firmapeCropState.dragging) return;
        firmapeCropState.x = event.clientX - firmapeCropState.startX;
        firmapeCropState.y = event.clientY - firmapeCropState.startY;
        pintarCropFirmape();
    });

    document.getElementById('firmapeCropStage')?.addEventListener('pointerup', (event) => {
        firmapeCropState.dragging = false;
        event.currentTarget.classList.remove('dragging');
    });

    function confirmarCropFirmape() {
        const image = document.getElementById('firmapeCropImage');
        const stage = document.getElementById('firmapeCropStage');
        if (!image || !stage) return;
        const rect = stage.getBoundingClientRect();
        const cropSize = 292;
        const output = 512;
        const canvas = document.createElement('canvas');
        canvas.width = output;
        canvas.height = output;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, output, output);

        const imgW = firmapeCropState.naturalWidth * firmapeCropState.scale;
        const imgH = firmapeCropState.naturalHeight * firmapeCropState.scale;
        const imageLeft = firmapeCropState.x - imgW / 2;
        const imageTop = firmapeCropState.y - imgH / 2;
        const cropLeft = rect.width / 2 - cropSize / 2;
        const cropTop = rect.height / 2 - cropSize / 2;
        const sx = (cropLeft - imageLeft) / firmapeCropState.scale;
        const sy = (cropTop - imageTop) / firmapeCropState.scale;
        const sw = cropSize / firmapeCropState.scale;
        const sh = cropSize / firmapeCropState.scale;

        ctx.drawImage(image, sx, sy, sw, sh, 0, 0, output, output);
        canvas.toBlob((blob) => {
            if (!blob) {
                mostrarEstadoPerfil('error', 'No se pudo preparar la imagen.');
                return;
            }
            firmapeFotoRecortada = blob;
            actualizarAvatarFirmape(URL.createObjectURL(blob));
            cerrarCropFirmape();
            mostrarEstadoPerfil('success', 'Foto ajustada. Presiona Actualizar foto para guardarla.');
        }, 'image/jpeg', 0.9);
    }

    document.getElementById('firmapeFotoInput')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        firmapeFotoRecortada = null;
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            mostrarEstadoPerfil('error', 'Formato no permitido. Usa JPG, PNG o WEBP.');
            event.target.value = '';
            return;
        }
        abrirCropFirmape(file);
    });

    document.getElementById('firmapeFotoForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = document.getElementById('firmapeFotoInput');
        const button = document.getElementById('firmapeFotoGuardar');
        if (!input?.files?.length) {
            mostrarEstadoPerfil('error', 'Selecciona una foto de perfil.');
            return;
        }
        button.disabled = true;
        button.innerHTML = 'Subiendo...';

        try {
            const formData = new FormData();
            formData.append('foto_perfil', firmapeFotoRecortada || input.files[0], 'foto_perfil.jpg');
            const response = await fetch(firmapeFotoEndpoint, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'No se pudo actualizar la foto.');
            }
            input.value = '';
            firmapeFotoRecortada = null;
            actualizarAvatarFirmape(data.fotoPerfilUrl || data.fotoPerfil || '');
            mostrarEstadoPerfil('success', data.mensaje || 'Foto de perfil actualizada correctamente.');
        } catch (error) {
            mostrarEstadoPerfil('error', error.message || 'No se pudo actualizar la foto.');
        } finally {
            button.disabled = false;
            button.innerHTML = <?= json_encode(firmape_account_icon('camera') . ' Actualizar foto', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        }
    });

    document.getElementById('firmapePerfilForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = document.getElementById('firmapePerfilGuardar');
        const formData = new FormData(event.currentTarget);
        button.disabled = true;
        button.textContent = 'Guardando...';

        try {
            const response = await fetch(firmapePerfilEndpoint, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'No se pudo actualizar el perfil.');
            }
            mostrarEstadoPerfil('success', data.mensaje || 'Perfil actualizado correctamente.');
        } catch (error) {
            mostrarEstadoPerfil('error', error.message || 'No se pudo actualizar el perfil.');
        } finally {
            button.disabled = false;
            button.textContent = 'Guardar cambios';
        }
    });

    document.getElementById('firmapePasswordForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = document.getElementById('firmapePasswordGuardar');
        const formData = new FormData(event.currentTarget);
        if (formData.get('password_nueva') !== formData.get('password_confirmar')) {
            mostrarEstadoPerfil('error', 'Las contraseñas no coinciden.');
            return;
        }
        button.disabled = true;
        button.textContent = 'Actualizando...';

        try {
            const response = await fetch(firmapePasswordEndpoint, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'No se pudo actualizar la contraseña.');
            }
            event.currentTarget.reset();
            mostrarEstadoPerfil('success', data.mensaje || 'Contraseña actualizada correctamente.');
        } catch (error) {
            mostrarEstadoPerfil('error', error.message || 'No se pudo actualizar la contraseña.');
        } finally {
            button.disabled = false;
            button.textContent = 'Actualizar contraseña';
        }
    });

    async function cargarCertificadoFirmape() {
        const status = document.getElementById('firmapeCertStatus');
        const info = document.getElementById('firmapeCertInfo');
        if (!status || !info) return;
        try {
            const response = await fetch(firmapeCertEndpoint, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.error || 'No se pudo consultar el certificado.');
            const cert = data.certificado || {};
            const certificados = cert.certificados || [];
            if (certificados.length) {
                status.textContent = certificados.length + ' certificado' + (certificados.length === 1 ? '' : 's') + ' servidor enrolado' + (certificados.length === 1 ? '' : 's');
                status.className = 'firmape-cert-status ok';
                info.innerHTML = certificados.map((item) => `
                    <div class="firmape-cert-row">
                        <div>
                            <strong>${escapeHtmlFirmape(item.alias || 'Certificado servidor FIRMAPE')}</strong>
                            <small>Serie: ${escapeHtmlFirmape(item.serial || 'S/N')} · ${escapeHtmlFirmape(item.origen || 'Servidor')}</small>
                        </div>
                        <button type="button" class="firmape-cert-delete" title="Eliminar certificado" onclick="eliminarCertificadoFirmape(${Number(item.id)})">×</button>
                    </div>
                `).join('');
                document.getElementById('firmapeCertEnroll').textContent = 'Agregar otro certificado';
            } else {
                status.textContent = 'No tienes certificados servidor enrolados';
                status.className = 'firmape-cert-status';
                info.innerHTML = '<small>Enrola un certificado servidor emulado para usar Firma Servidor con PIN.</small>';
                document.getElementById('firmapeCertEnroll').textContent = 'Agregar certificado servidor';
            }
        } catch (error) {
            status.textContent = error.message || 'No se pudo consultar el certificado.';
            status.className = 'firmape-cert-status';
        }
    }

    function escapeHtmlFirmape(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function pedirCodigoCertificadoFirmape(email) {
        return new Promise((resolve) => {
            firmapeCodeResolver = resolve;
            const modal = document.getElementById('firmapeCodeModal');
            const input = document.getElementById('firmapeCodeInput');
            const text = document.getElementById('firmapeCodeText');
            text.textContent = 'Se envio un codigo a ' + (email || 'tu correo') + '. Ingresalo para enrolar el certificado.';
            input.value = '';
            modal.classList.add('show');
            setTimeout(() => input.focus(), 50);
        });
    }

    function resolverCodigoCertificadoFirmape(valor) {
        const modal = document.getElementById('firmapeCodeModal');
        const input = document.getElementById('firmapeCodeInput');
        const codigo = valor === null ? null : input.value.trim();
        modal.classList.remove('show');
        if (firmapeCodeResolver) firmapeCodeResolver(codigo);
        firmapeCodeResolver = null;
    }

    document.getElementById('firmapeCodeInput')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') resolverCodigoCertificadoFirmape();
        if (event.key === 'Escape') resolverCodigoCertificadoFirmape(null);
    });

    async function eliminarCertificadoFirmape(id) {
        if (!id) {
            mostrarEstadoPerfil('error', 'Este certificado antiguo no se puede eliminar desde aqui. Vuelve a enrolarlo para migrarlo.');
            return;
        }
        if (!confirm('¿Eliminar este certificado?')) return;
        try {
            const response = await fetch(firmapeCertEndpoint + '?id=' + encodeURIComponent(id), {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.error || 'No se pudo eliminar el certificado.');
            mostrarEstadoPerfil('success', 'Certificado eliminado correctamente.');
            cargarCertificadoFirmape();
        } catch (error) {
            mostrarEstadoPerfil('error', error.message || 'No se pudo eliminar el certificado.');
        }
    }

    document.getElementById('firmapeCertEnroll')?.addEventListener('click', async () => {
        const pin = document.getElementById('firmapeCertPin').value.trim();
        const alias = document.getElementById('firmapeCertAlias').value.trim();
        if (!alias) {
            mostrarEstadoPerfil('error', 'Ingresa un nombre para el certificado.');
            return;
        }
        if (!/^\d{4,8}$/.test(pin)) {
            mostrarEstadoPerfil('error', 'El PIN del certificado debe tener entre 4 y 8 digitos.');
            return;
        }
        const button = document.getElementById('firmapeCertEnroll');
        button.disabled = true;
        button.textContent = 'Enviando codigo...';
        try {
            const codeData = new FormData();
            codeData.append('accion', 'enviar_codigo');
            codeData.append('alias', alias);
            const codeResponse = await fetch(firmapeCertEndpoint, { method: 'POST', body: codeData, headers: { 'Accept': 'application/json' } });
            const codePayload = await codeResponse.json();
            if (!codeResponse.ok || !codePayload.ok) throw new Error(codePayload.error || 'No se pudo enviar el codigo.');

            const codigo = await pedirCodigoCertificadoFirmape(codePayload.email || '');
            if (!codigo) {
                mostrarEstadoPerfil('error', 'Enrolamiento cancelado. Ingresa el codigo recibido para continuar.');
                return;
            }

            button.textContent = 'Agregando certificado...';
            const formData = new FormData();
            formData.append('pin', pin);
            formData.append('alias', alias);
            formData.append('codigo', codigo.trim());
            formData.append('certificado_token', codePayload.certificado_token || '');
            const response = await fetch(firmapeCertEndpoint, { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.error || 'No se pudo enrolar el certificado.');
            document.getElementById('firmapeCertPin').value = '';
            document.getElementById('firmapeCertAlias').value = 'Certificado servidor FIRMAPE';
            mostrarEstadoPerfil('success', 'Certificado servidor agregado correctamente. Se envio la confirmacion a tu correo.');
            cargarCertificadoFirmape();
        } catch (error) {
            mostrarEstadoPerfil('error', error.message || 'No se pudo enrolar el certificado.');
        } finally {
            button.disabled = false;
            button.textContent = 'Agregar otro certificado';
        }
    });
    </script>
    <?php
}
