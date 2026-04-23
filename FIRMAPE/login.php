<?php
session_start();
include 'config/conexion.php';

$mensaje = "";

if ($_POST) {
    $dni = $_POST['dni'];
    $pass = $_POST['password'];

    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE dni=?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();

    $res = $stmt->get_result()->fetch_assoc();

    if ($res && password_verify($pass, $res['password'])) {
        $_SESSION['user'] = $dni;
        header("Location: principal.php");
        exit;
    } else {
        $mensaje = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="css/estilos.css">
<link rel="icon" href="imagenes/favicon.png">
</head>
<body>

<div class="container">

<!-- 🔥 LOGO EMPRESA -->
<div class="login-logo">
    <img src="imagenes/firmape.png" alt="Logo Empresa">
</div>

<h2>Login</h2>

<form method="POST">

<input type="text" name="dni" placeholder="DNI" required>
<input type="password" name="password" placeholder="Contraseña" required>

<button type="submit">Ingresar</button>

</form>

<?php if (!empty($mensaje)): ?>
<p class="error"><?= $mensaje ?></p>
<?php endif; ?>

<div class="links">
    <a href="register.php">Crear cuenta</a>
    <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
</div>

</div>

</body>
</html>