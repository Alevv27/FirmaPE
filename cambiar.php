<?php
session_start();
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cambiar contrasena</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">
    <h2>Cambiar contrasena</h2>
    <div class="alert-error show">
        Esta funcionalidad depende de endpoints que hoy no existen en tu backend Flask.
    </div>
    <p class="subtitle">Por ahora el cambio de password disponible en el frontend es desde <a href="perfil.php">Mi perfil</a> con la ruta `PATCH /api/usuarios/&lt;id&gt;`.</p>
    <div class="links">
        <a href="login.php">Volver al login</a>
        <a href="perfil.php">Ir a mi perfil</a>
    </div>
</div>

</body>
</html>
