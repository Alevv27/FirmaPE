<?php
require_once 'includes/auth.php';
require_once 'includes/toast.php';

$toast = toast_message('Esta opcion pertenecia al sistema anterior. Ahora los cambios de contrasena se hacen desde administracion usando el backend Flask.', 'info');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar contrasena</title>
<link rel="stylesheet" href="css/estilos.css">
<?php render_sweetalert_assets(); ?>
</head>
<body>
<div class="container">
    <h2>Cambiar contrasena</h2>
    <div class="links" style="margin-top: 15px; text-align: center;">
        <a href="index.php">Regresar</a>
    </div>
</div>
<?php render_toast_script($toast); ?>
</body>
</html>
