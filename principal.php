<?php
session_start();
include 'config/conexion.php';

// Redirigir si no hay sesión activa
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$dni = $_SESSION['user'];

// 1. OBTENEMOS EL NOMBRE Y EL ROL DEL USUARIO
$stmt = $conexion->prepare("SELECT nombre, rol FROM usuarios WHERE dni=?");
$stmt->bind_param("s", $dni);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

// Limpiamos el rol para evitar errores de espacios o mayúsculas
$user_rol = isset($data['rol']) ? trim(strtolower($data['rol'])) : 'usuario';

// Preparamos el nombre para mostrar
$usuario_display = (!empty($data['nombre'])) ? explode(' ', $data['nombre'])[0] : $dni;
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
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: #000;
        }

        .logo-section { display: flex; align-items: center; gap: 12px; }
        .logo-section img { width: 38px; }
        .logo-section h3 { margin: 0; font-size: 22px; letter-spacing: 1px; font-weight: 800; }

        .reloj-center {
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .user-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
        }

        .perfil-link {
            text-decoration: none;
            color: #000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .btn-logout {
            background: #ff4d4d;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
        }

        .badge-rol {
            font-size: 10px;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
            vertical-align: middle;
            text-transform: uppercase;
        }
        .bg-admin { background: #6366f1; }
        .bg-firmante { background: #10b981; }
        .bg-usuario { background: #6b7280; }

        .dashboard-container {
            max-width: 1200px;
            margin: 100px auto;
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .card-moderna {
            background: white;
            width: 280px; 
            padding: 60px 20px;
            border-radius: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .card-moderna:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }

        .card-restringida {
            filter: grayscale(0.3);
            opacity: 0.9;
        }

        .card-moderna .icon { font-size: 70px; margin-bottom: 20px; display: block; }
        .card-moderna h3 { margin: 0; font-size: 18px; text-transform: uppercase; font-weight: 900; }
    </style>
</head>
<body>

<header class="header-moderno">
    <div class="logo-section">
        <img src="imagenes/favicon.png" alt="logo">
        <h3>FIRMAPE</h3>
    </div>

    <div class="reloj-center">
        <div id="hora"></div>
    </div>

    <div class="user-controls">
        <a href="perfil.php" class="perfil-link">
            <span>Hola, <b><?= htmlspecialchars($usuario_display) ?></b>
                <?php if($user_rol === 'admin'): ?>
                    <span class="badge-rol bg-admin">ADMIN</span>
                <?php elseif($user_rol === 'firmante'): ?>
                    <span class="badge-rol bg-firmante">FIRMANTE</span>
                <?php else: ?>
                    <span class="badge-rol bg-usuario">USUARIO</span>
                <?php endif; ?>
            </span>
            <div style="background:#f0f0f0; padding:6px 10px; border-radius:8px; border:1px solid #ddd;">👤</div>
        </a>

        <a href="logout.php" class="btn-logout">SALIR</a>
    </div>
</header>

<main class="dashboard-container">
    <div class="card-moderna" onclick="location.href='gestion.php'">
        <span class="icon">📂</span>
        <h3>Gestión de archivos</h3>
    </div>

    <?php if ($user_rol === 'admin' || $user_rol === 'firmante'): ?>
        
        <?php 
            $click_firmar = "location.href='firmar.php'";
            $clase_firmar = "";
            if ($user_rol === 'admin') {
                $click_firmar = "alertaSoloFirmantes()";
                $clase_firmar = "card-restringida";
            }
        ?>
        <div class="card-moderna <?= $clase_firmar ?>" onclick="<?= $click_firmar ?>">
            <span class="icon">✍️</span>
            <h3>Firmar Documentos</h3>
        </div>

        <?php 
            // CAMBIO REALIZADO AQUÍ: Redirección a firmar_documento.php
            $click_config = "location.href='firmar_documento.php'";
            $clase_config = "";
            if ($user_rol === 'admin') {
                $click_config = "alertaSoloFirmantes()";
                $clase_config = "card-restringida";
            }
        ?>
        <div class="card-moderna <?= $clase_config ?>" onclick="<?= $click_config ?>">
            <span class="icon">🪪</span>
            <h3>Firma Electrónica</h3>
        </div>

    <?php endif; ?>
</main>

<script>
function alertaSoloFirmantes() {
    alert("⛔ ACCESO RESTRINGIDO\n\nComo ADMINISTRADOR puedes supervisar la gestión, pero estas acciones son exclusivas para el rol de FIRMANTE.");
}

function actualizarHora() {
    const now = new Date();
    const opciones = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
    let fecha = now.toLocaleDateString('es-PE', opciones);
    let hora = now.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    let fechaFormateada = fecha.charAt(0).toUpperCase() + fecha.slice(1);
    document.getElementById("hora").innerHTML = fechaFormateada + " | " + hora.toUpperCase();
}
setInterval(actualizarHora, 1000);
actualizarHora();
</script>

</body>
</html>