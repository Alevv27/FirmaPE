<?php
session_start();
require_once 'includes/auth.php';
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
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)),
                        url("imagenes/fondope.png");
            background-size: cover;
            background-attachment: fixed;
        }
        .header-moderno {
            padding: 12px 40px;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: #000;
        }
        .logo-section { display: flex; align-items: center; gap: 12px; }
        .logo-section img { width: 38px; }
        .logo-section h3 { margin: 0; font-size: 22px; letter-spacing: 1px; font-weight: 800; }
        .reloj-center { text-align: center; font-size: 15px; font-weight: 600; color: #333; }
        .user-controls { display: flex; align-items: center; justify-content: flex-end; gap: 20px; }
        .perfil-link { text-decoration: none; color: #000; display: flex; align-items: center; gap: 10px; font-size: 15px; }
        .btn-logout {
            background: #ff4d4d; color: white; padding: 8px 16px; border-radius: 6px;
            text-decoration: none; font-weight: bold; font-size: 12px;
        }
        .badge-rol {
            font-size: 10px; color: white; padding: 2px 6px; border-radius: 4px;
            margin-left: 5px; vertical-align: middle; text-transform: uppercase;
        }
        .bg-admin { background: #6366f1; }
        .bg-firmante { background: #10b981; }
        .bg-gestor { background: #0ea5e9; }
        .bg-usuario { background: #6b7280; }
        .dashboard-container {
            max-width: 1200px; margin: 100px auto; display: flex;
            justify-content: center; gap: 30px; flex-wrap: wrap; padding: 0 20px;
        }
        .card-moderna {
            background: white; width: 280px; padding: 60px 20px; border-radius: 20px;
            text-align: center; cursor: pointer; transition: all .25s ease;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .card-moderna:hover { transform: translateY(-10px); box-shadow: 0 25px 50px rgba(0,0,0,0.22); }
        .card-moderna .icon { font-size: 56px; margin-bottom: 20px; display: block; }
        .card-moderna h3 { margin: 0; font-size: 18px; text-transform: uppercase; font-weight: 900; }
    </style>
</head>
<body>

<header class="header-moderno">
    <div class="logo-section">
        <img src="imagenes/favicon.png" alt="logo">
        <h3>FIRMAPE</h3>
    </div>

    <div class="reloj-center"><div id="hora"></div></div>

    <div class="user-controls">
        <a href="perfil.php" class="perfil-link">
            <span>Hola, <b><?= e($nombre) ?></b><span class="badge-rol <?= $badgeClass ?>"><?= e($perfil) ?></span></span>
            <div style="background:#f0f0f0; padding:6px 10px; border-radius:8px; border:1px solid #ddd;">Usuario</div>
        </a>
        <a href="logout.php" class="btn-logout">SALIR</a>
    </div>
</header>

<?php if (isset($_GET['error']) && $_GET['error'] === 'modulo'): ?>
    <div style="max-width:900px; margin:25px auto 0; background:#fee2e2; color:#991b1b; padding:14px 18px; border-radius:10px; font-weight:700;">
        No tienes permiso para acceder a ese modulo.
    </div>
<?php endif; ?>

<main class="dashboard-container">
    <?php if (has_module('GESTION')): ?>
        <div class="card-moderna" onclick="location.href='gestion.php'">
            <span class="icon">&#128194;</span>
            <h3>Gestion de archivos</h3>
        </div>
    <?php endif; ?>

    <?php if (has_module('FIRMAR')): ?>
        <div class="card-moderna" onclick="location.href='firmar.php'">
            <span class="icon">&#9997;</span>
            <h3>Firmar Documentos</h3>
        </div>

        <div class="card-moderna" onclick="location.href='firmar_documento.php'">
            <span class="icon">&#128395;</span>
            <h3>Firma Digital</h3>
        </div>
    <?php endif; ?>

    <?php if (has_module('ADMINISTRACION')): ?>
        <div class="card-moderna" onclick="location.href='PANELADMINISTRADOR/admin_panel.php'">
            <span class="icon">&#9881;</span>
            <h3>Administracion</h3>
        </div>
    <?php endif; ?>
</main>

<script>
function actualizarHora() {
    const now = new Date();
    const opciones = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
    let fecha = now.toLocaleDateString('es-PE', opciones);
    let hora = now.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    document.getElementById("hora").innerHTML = fecha.charAt(0).toUpperCase() + fecha.slice(1) + " | " + hora.toUpperCase();
}
setInterval(actualizarHora, 1000);
actualizarHora();
</script>

</body>
</html>
