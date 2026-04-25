<?php
session_start();
include 'config/conexion.php';

// Verificamos que el ID de la notificación y la sesión existan
if (isset($_GET['id']) && isset($_SESSION['user'])) {
    $id = $_GET['id'];
    $dni_usuario = $_SESSION['user'];

    // Eliminamos la notificación de la base de datos
    // Solo si pertenece al usuario que tiene la sesión iniciada (por seguridad)
    $stmt = $conexion->prepare("DELETE FROM notificaciones WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("is", $id, $dni_usuario);
    
    if ($stmt->execute()) {
        echo "ok"; // Esto es lo que lee el JavaScript para saber que todo salió bien
    } else {
        echo "error";
    }
    $stmt->close();
} else {
    echo "parámetros faltantes";
}
?>