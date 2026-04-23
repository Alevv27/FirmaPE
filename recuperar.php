<?php
session_start();
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contrasena</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">
    <h2>Recuperar contrasena</h2>
    <div class="alert-error show">
        Esta pantalla ya no usa MySQL local. Tu backend Flask actual no expone un endpoint de recuperacion de contrasena.
    </div>
    <p class="subtitle">Si quieres, el siguiente paso es crear en Flask las rutas para solicitar codigo y cambiar password.</p>
    <div class="links">
        <a href="login.php">Volver al login</a>
        <a href="cambiar.php">Ver estado</a>
    </div>
</div>

</body>
</html>
