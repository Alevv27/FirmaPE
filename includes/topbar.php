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
    .firmape-profile-link { text-decoration:none; color:#000; display:flex; align-items:center; gap:10px; font-size:15px; }
    .firmape-user-pill { background:#f0f0f0; padding:7px 11px; border-radius:8px; border:1px solid #ddd; }
    .firmape-logout { background:#ff4d4d; color:white; padding:9px 16px; border-radius:6px; text-decoration:none; font-weight:900; font-size:12px; }
    .firmape-badge { font-size:10px; color:white; padding:2px 6px; border-radius:4px; margin-left:5px; vertical-align:middle; text-transform:uppercase; }
    .firmape-badge-admin { background:#6366f1; }
    .firmape-badge-firmante { background:#10b981; }
    .firmape-badge-gestor { background:#0ea5e9; }
    .firmape-badge-usuario { background:#6b7280; }
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
    $badgeClass = [
        'ADMIN' => 'firmape-badge-admin',
        'FIRMANTE' => 'firmape-badge-firmante',
        'GESTOR' => 'firmape-badge-gestor',
    ][$perfil] ?? 'firmape-badge-usuario';
    ?>
    <header class="firmape-topbar">
        <div class="firmape-topbar-logo">
            <img src="<?= e($basePath . 'imagenes/favicon.png') ?>" alt="logo">
            <h3>FIRMAPE</h3>
        </div>
        <div class="firmape-topbar-clock"><div id="hora"></div></div>
        <div class="firmape-topbar-user">
            <a href="<?= e($basePath . 'perfil.php') ?>" class="firmape-profile-link">
                <span>Hola, <b><?= e($nombre) ?></b><span class="firmape-badge <?= e($badgeClass) ?>"><?= e($perfil) ?></span></span>
                <div class="firmape-user-pill">Usuario</div>
            </a>
            <a href="<?= e($basePath . 'logout.php') ?>" class="firmape-logout">SALIR</a>
        </div>
    </header>
    <script>
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
    </script>
    <?php
}
