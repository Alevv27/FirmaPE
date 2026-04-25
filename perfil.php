<?php
session_start();
include 'config/conexion.php';

// 🔐 PROTECCIÓN
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$dni = $_SESSION['user'];
$mensaje = "";
$color_alerta = "#ff4d4d"; // Rojo por defecto para errores

// 🔍 OBTENER DATOS ACTUALES
$stmt = $conexion->prepare("SELECT nombre, correo FROM usuarios WHERE dni=?");
$stmt->bind_param("s", $dni);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

$nombre = $user['nombre'] ?? "";
$correo = $user['correo'] ?? "";

// 💾 GUARDAR CAMBIOS (Solo correo)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nuevoCorreo = trim($_POST['correo']);

    // 1. Verificar si el correo ya lo tiene OTRO usuario (que no sea yo)
    $check = $conexion->prepare("SELECT id FROM usuarios WHERE correo=? AND dni != ?");
    $check->bind_param("ss", $nuevoCorreo, $dni);
    $check->execute();
    $existe = $check->get_result();

    if ($existe->num_rows > 0) {
        $mensaje = "El correo ya está registrado por otro usuario";
        $color_alerta = "#ff4d4d"; // Rojo
    } else {
        // 2. Actualizamos SOLO el correo
        $stmt = $conexion->prepare("UPDATE usuarios SET correo=? WHERE dni=?");
        $stmt->bind_param("ss", $nuevoCorreo, $dni);
        
        if ($stmt->execute()) {
            $correo = $nuevoCorreo;
            $mensaje = "Correo actualizado correctamente";
            $color_alerta = "#00c853"; // Verde
        } else {
            $mensaje = "Error al guardar los cambios";
            $color_alerta = "#ff4d4d"; // Rojo
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi perfil</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .btn-volver {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #4db8ff;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn-volver:hover {
            color: #1a8cff;
            text-decoration: underline;
        }
        /* Estilo para el campo que no se puede editar */
        input[readonly] {
            background-color: #f0f0f0;
            cursor: not-allowed;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>Mi perfil</h2>

    <form method="POST">
        <label style="display:block; text-align:left; font-size:12px; color:#666; margin: 0 0 5px 10px;">Nombre Completo (No editable)</label>
        <input type="text" value="<?= htmlspecialchars($nombre) ?>" readonly>

        <label style="display:block; text-align:left; font-size:12px; color:#666; margin: 10px 0 5px 10px;">Correo Electrónico</label>
        <input type="email" name="correo" placeholder="Correo" value="<?= htmlspecialchars($correo) ?>" required>

        <button type="submit">Guardar cambios</button>
    </form>

    <?php if ($mensaje): ?>
        <div class="alert-error show" style="background: <?= $color_alerta ?>; color: white; border: none;">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 10px;">
        <a href="principal.php" class="btn-volver">⬅ Volver al panel</a>
    </div>

</div>

</body>
</html>