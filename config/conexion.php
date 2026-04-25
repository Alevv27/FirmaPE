<?php
$conexion = new mysqli(
    "sql10.freesqldatabase.com",
    "sql10824373",
    "LVXmthUgxs",
    "sql10824373"
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
