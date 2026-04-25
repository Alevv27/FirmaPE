<?php
session_start();
require_once 'config/conexion.php';

// 1. SEGURIDAD: Redirigir al login si no hay sesión
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$dni_sesion = $_SESSION['user'];

// 2. CONSULTA DE ROL EN TIEMPO REAL (Seguridad máxima)
$stmt = $conexion->prepare("SELECT nombre, rol FROM usuarios WHERE dni = ?");
$stmt->bind_param("s", $dni_sesion);
$stmt->execute();
$resultado = $stmt->get_result();
$datos_user = $resultado->fetch_assoc();

// Limpiamos el rol: quitamos espacios y pasamos a minúsculas
$rol = isset($datos_user['rol']) ? trim(strtolower($datos_user['rol'])) : 'usuario';
$nombre_corto = (!empty($datos_user['nombre'])) ? explode(' ', $datos_user['nombre'])[0] : $dni_sesion;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to right, rgba(204,231,240,0.8), rgba(126,200,227,0.8)), 
                        url("imagenes/fondope.png");
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* --- HEADER --- */
        .header-moderno {
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .logo h3 { margin: 0; font-weight: 800; color: #1e293b; font-size: 24px; }

        .user-info { display: flex; align-items: center; gap: 15px; }

        .badge-rol {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: white;
            background: #64748b; /* Color por defecto */
        }
        /* Colores dinámicos por rol */
        .rol-admin { background: #6366f1; }
        .rol-firmante { background: #10b981; }
        .rol-usuario { background: #f59e0b; }

        .btn-logout {
            background: #ef4444;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: 0.3s;
        }

        /* --- GRILLA DE TARJETAS --- */
        .dashboard-grid {
            max-width: 1200px;
            margin: 80px auto;
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
            padding: 0 20px;
        }

        .card {
            background: white;
            width: 300px;
            padding: 50px 20px;
            border-radius: 28px;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .card .icon { font-size: 65px; margin-bottom: 20px; display: block; }
        .card h3 { margin: 0; font-size: 19px; font-weight: 800; text-transform: uppercase; }
        .card p { font-size: 14px; color: #64748b; margin-top: 12px; line-height: 1.4; }
    </style>
</head>
<body>

<header class="header-moderno">
    <div class="logo"><h3>FIRMAPE</h3></div>

    <div class="user-info">
        <span>Hola, <b><?= htmlspecialchars($nombre_corto) ?></b></span>
        <span class="badge-rol rol-<?= $rol ?>"><?= $rol ?></span>
        <a href="logout.php" class="btn-logout">SALIR</a>
    </div>
</header>

<main class="dashboard-grid">

    <a href="gestion.php" class="card">
        <span class="icon">📂</span>
        <h3>Gestión de archivos</h3>
        <p>Sube tus documentos y revisa el estado de tus envíos.</p>
    </a>

    <?php if ($rol === 'firmante'): ?>
        <a href="firmar.php" class="card">
            <span class="icon">✍️</span>
            <h3>Firmar Documentos</h3>
            <p>Revisa y firma los documentos que te han sido asignados.</p>
        </a>
    <?php endif; ?>

    <?php if ($rol === 'admin' || $rol === 'firmante'): ?>
        <a href="firma_electronica.php" class="card">
            <span class="icon">🪪</span>
            <h3>Firma Electrónica</h3>
            <p>Gestiona tus certificados y configuraciones de firma.</p>
        </a>
    <?php endif; ?>

    <?php if ($rol === 'admin'): ?>
        <a href="admin_panel.php" class="card" style="border: 2px dashed #6366f1;">
            <span class="icon">⚙️</span>
            <h3>Panel Admin</h3>
            <p>Control total de usuarios, roles y reportes del sistema.</p>
        </a>
    <?php endif; ?>

</main>

<script>
    // Reloj opcional o cualquier otra función JS que necesites
</script>

</body>
</html>