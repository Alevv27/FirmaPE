<?php
session_start();
include 'config/conexion.php';

if (isset($_GET['id']) && isset($_SESSION['user'])) {
    $id_doc = $_GET['id'];
    $dni_gerente = $_SESSION['user'];

    // 1. Obtener datos del documento y nombre de quien rechaza
    $query = $conexion->query("SELECT d.id_remitente, d.nombre_archivo, u.nombre 
                               FROM documentos d 
                               JOIN usuarios u ON u.dni = '$dni_gerente' 
                               WHERE d.id = '$id_doc'");
    $datos = $query->fetch_assoc();

    if ($datos) {
        $id_trabajador = $datos['id_remitente'];
        $nombre_archivo = $datos['nombre_archivo'];
        $nombre_gerente = $datos['nombre'] ?? $dni_gerente;

        // 2. Cambiar estado a Rechazado
        $conexion->query("UPDATE documentos SET estado = 'Rechazado' WHERE id = '$id_doc'");

        // 3. Insertar notificación para el trabajador (CON NEGRITA)
        $mensaje = "<b>❌ $nombre_gerente rechazó tu documento:</b> $nombre_archivo";
        
        $stmt = $conexion->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
        $stmt->bind_param("ss", $id_trabajador, $mensaje);
        $stmt->execute();
    }
}

// Redirigir de vuelta a gestión
header("Location: gestion.php");
exit();