<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">
<h2>Bienvenido <?= $_SESSION['user'] ?></h2>

<a href="logout.php">Cerrar sesión</a>
</div>

</body>
</html>