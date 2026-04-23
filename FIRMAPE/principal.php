<?php
session_start();
include 'config/conexion.php';

// 🔐 PROTECCIÓN
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$dni = $_SESSION['user'];

// 🔍 OBTENER NOMBRE
$stmt = $conexion->prepare("SELECT nombre FROM usuarios WHERE dni=?");
$stmt->bind_param("s", $dni);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

$usuario = $data['nombre'] ?? $dni;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel principal</title>

<link rel="stylesheet" href="css/estilos.css">
</head>

<body>

<!-- HEADER -->
<div class="header">

    <div class="logo">
        <img src="imagenes/favicon.png" alt="logo">
        <h3>FIRMAPE</h3>
    </div>

    <div class="user-info">

        <div class="top-user">
            <span>@<?= $usuario ?></span>

            <!-- 👤 PERFIL -->
            <div class="perfil" onclick="location.href='perfil.php'">
                👤
            </div>
        </div>

        <div id="hora"></div>

        <div class="logout" onclick="location.href='logout.php'">
            Cerrar sesión
        </div>

    </div>

</div>

<!-- DASHBOARD -->
<div class="dashboard">

    <div class="card" onclick="location.href='gestion.php'">
        <div class="icon">📂</div>
        <p>Gestión de documentos</p>
    </div>

    <div class="card" onclick="location.href='firmar.php'">
        <div class="icon">✍️</div>
        <p>Firmar documentos</p>
    </div>

</div>

<!-- 🕒 HORA -->
<script>
function actualizarHora() {
    const now = new Date();

    const fecha = now.toLocaleDateString('es-PE');
    const hora = now.toLocaleTimeString('es-PE');

    document.getElementById("hora").innerHTML = fecha + " | " + hora;
}

setInterval(actualizarHora, 1000);
actualizarHora();
</script>

</body>
</html>