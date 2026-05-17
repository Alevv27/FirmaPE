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
        grid-template-columns: 1fr 2fr 1fr;
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
    .firmape-topbar-user { display:flex; align-items:center; justify-content:flex-end; gap:18px; }
    .firmape-profile-link { text-decoration:none; color:#000; display:flex; align-items:center; gap:10px; font-size:15px; border:0; background:transparent; padding:0; cursor:pointer; font-family:inherit; }
    .firmape-user-pill { background:#f0f0f0; padding:7px 11px; border-radius:8px; border:1px solid #ddd; font-size:15px; }
    .firmape-logout { background:#ff4d4d; color:white; padding:9px 16px; border-radius:6px; text-decoration:none; font-weight:900; font-size:12px; }
    .firmape-badge { font-size:10px; color:white; padding:2px 6px; border-radius:4px; margin-left:5px; vertical-align:middle; text-transform:uppercase; }
    .firmape-badge-admin { background:#6366f1; }
    .firmape-badge-firmante { background:#10b981; }
    .firmape-badge-gestor { background:#0ea5e9; }
    .firmape-badge-usuario { background:#6b7280; }
    .firmape-profile-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; padding:22px; z-index:3000; }
    .firmape-profile-backdrop.show { display:flex; }
    .firmape-profile-modal { width:100%; max-width:520px; background:rgba(255,255,255,.98); border-radius:18px; box-shadow:0 30px 70px rgba(15,23,42,.32); overflow:hidden; color:#111827; }
    .firmape-profile-head { padding:20px 24px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0; }
    .firmape-profile-head h2 { margin:0; font-size:21px; font-weight:900; }
    .firmape-profile-close { width:34px; height:34px; border:0; border-radius:999px; background:#f1f5f9; cursor:pointer; font-size:20px; font-weight:900; color:#475569; }
    .firmape-profile-body { padding:22px 24px 24px; }
    .firmape-profile-field { margin-bottom:14px; }
    .firmape-profile-field label { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:900; margin-bottom:7px; }
    .firmape-profile-field input { width:100%; padding:12px 13px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; outline:none; background:white; }
    .firmape-profile-field input:focus { border-color:#2f8cff; box-shadow:0 0 0 3px rgba(47,140,255,.14); }
    .firmape-profile-field input[readonly] { background:#f8fafc; color:#64748b; cursor:not-allowed; }
    .firmape-profile-status { display:none; margin:2px 0 14px; padding:10px 12px; border-radius:10px; font-size:13px; font-weight:800; }
    .firmape-profile-status.show { display:block; }
    .firmape-profile-status.success { background:#dcfce7; color:#166534; }
    .firmape-profile-status.error { background:#fee2e2; color:#991b1b; }
    .firmape-profile-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:18px; border-top:1px solid #e2e8f0; }
    .firmape-profile-btn { padding:12px 18px; border-radius:10px; font-size:13px; font-weight:800; border:0; cursor:pointer; }
    .firmape-profile-cancel { background:#f1f5f9; color:#334155; }
    .firmape-profile-save { background:#1e293b; color:#fff; }
    .firmape-profile-save:disabled { opacity:.7; cursor:wait; }
    @media (max-width: 860px) {
        .firmape-topbar { height:auto; grid-template-columns:1fr; gap:10px; padding:12px 18px; text-align:center; }
        .firmape-topbar-logo, .firmape-topbar-user { justify-content:center; }
    }
    <?php
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
    $badgeClass = [
        'ADMIN' => 'firmape-badge-admin',
        'FIRMANTE' => 'firmape-badge-firmante',
        'GESTOR' => 'firmape-badge-gestor',
    ][$perfil] ?? 'firmape-badge-usuario';
    $profileEndpoint = $basePath . 'perfil_actualizar.php';
    ?>
    <header class="firmape-topbar">
        <div class="firmape-topbar-logo">
            <img src="<?= e($basePath . 'imagenes/favicon.png') ?>" alt="logo">
            <h3>FIRMAPE</h3>
        </div>
        <div class="firmape-topbar-clock"><div id="hora"></div></div>
        <div class="firmape-topbar-user">
            <button type="button" class="firmape-profile-link" onclick="abrirPerfilFirmape()">
                <span>Hola, <b><?= e($nombre) ?></b><span class="firmape-badge <?= e($badgeClass) ?>"><?= e($perfil) ?></span></span>
                <div class="firmape-user-pill">Usuario</div>
            </button>
            <a href="<?= e($basePath . 'logout.php') ?>" class="firmape-logout">SALIR</a>
        </div>
    </header>
    <div class="firmape-profile-backdrop" id="firmapePerfilModal" onclick="cerrarPerfilFirmape(event)">
        <div class="firmape-profile-modal" role="dialog" aria-modal="true" aria-labelledby="firmapePerfilTitulo">
            <div class="firmape-profile-head">
                <h2 id="firmapePerfilTitulo">Mi perfil</h2>
                <button type="button" class="firmape-profile-close" onclick="cerrarPerfilFirmape()">×</button>
            </div>
            <form class="firmape-profile-body" id="firmapePerfilForm">
                <div class="firmape-profile-field">
                    <label>Nombre completo</label>
                    <input type="text" value="<?= e($nombreCompleto) ?>" readonly>
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
                <div class="firmape-profile-status" id="firmapePerfilStatus"></div>
                <div class="firmape-profile-actions">
                    <button type="button" class="firmape-profile-btn firmape-profile-cancel" onclick="cerrarPerfilFirmape()">Cancelar</button>
                    <button type="submit" class="firmape-profile-btn firmape-profile-save" id="firmapePerfilGuardar">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    const firmapePerfilEndpoint = <?= json_encode($profileEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

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

    function abrirPerfilFirmape() {
        const modal = document.getElementById('firmapePerfilModal');
        const status = document.getElementById('firmapePerfilStatus');
        if (status) {
            status.className = 'firmape-profile-status';
            status.textContent = '';
        }
        modal.classList.add('show');
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
    </script>
    <?php
}
