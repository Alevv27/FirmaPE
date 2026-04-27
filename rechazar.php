<?php
session_start();
require_once 'includes/auth.php';
require_module('FIRMAR');

if (isset($_GET['id']) && $conexion) {
    $id_doc = (int) $_GET['id'];

    $stmt = $conexion->prepare("SELECT id_remitente, nombre_archivo FROM documentos WHERE id = ?");
    $stmt->bind_param("i", $id_doc);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_assoc();

    if ($datos) {
        $id_trabajador = $datos['id_remitente'];
        $nombre_archivo = $datos['nombre_archivo'];
        $nombre_firmante = current_user()['nombre'] ?? 'Un firmante';

        $stmt_estado = $conexion->prepare("UPDATE documentos SET estado = 'Rechazado' WHERE id = ?");
        $stmt_estado->bind_param("i", $id_doc);
        $stmt_estado->execute();

        $mensaje = "<b>$nombre_firmante rechazo tu documento:</b> $nombre_archivo";
        $stmt_notif = $conexion->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
        $stmt_notif->bind_param("ss", $id_trabajador, $mensaje);
        $stmt_notif->execute();
    }
}

header("Location: gestion.php");
exit;
