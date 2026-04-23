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

// 🔍 OBTENER DATOS
$stmt = $conexion->prepare("SELECT nombre, correo FROM usuarios WHERE dni=?");
$stmt->bind_param("s", $dni);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

$nombre = $user['nombre'] ?? "";
$correo = $user['correo'] ?? "";

// 💾 GUARDAR CAMBIOS
if ($_POST) {

    $nuevoNombre = $_POST['nombre'];
    $nuevoCorreo = $_POST['correo'];

    $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, correo=? WHERE dni=?");
    $stmt->bind_param("sss", $nuevoNombre, $nuevoCorreo, $dni);
    $stmt->execute();

    $nombre = $nuevoNombre;
    $correo = $nuevoCorreo;

    $mensaje = "Datos actualizados correctamente";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Mi perfil</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">

<h2>Mi perfil</h2>

<form method="POST">

<input type="text" name="nombre" placeholder="Nombre completo" value="<?= $nombre ?>" required>

<input type="email" name="correo" placeholder="Correo" value="<?= $correo ?>" required>

<button type="submit">Guardar cambios</button>

</form>

<?php if ($mensaje): ?>
<div class="alert-error show" style="background:#00c853;">
    <?= $mensaje ?>
</div>
<?php endif; ?>

<br>
<a href="principal.php">⬅ Volver al panel</a>

</div>

</body>
</html>