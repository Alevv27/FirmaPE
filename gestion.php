<?php
session_start();
include 'config/conexion.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$dni_usuario = $_SESSION['user'];

// 1. DATOS DEL USUARIO
$stmt_rol = $conexion->prepare("SELECT nombre, rol FROM usuarios WHERE dni = ?");
$stmt_rol->bind_param("s", $dni_usuario);
$stmt_rol->execute();
$res_rol = $stmt_rol->get_result();
$user_data = $res_rol->fetch_assoc();
$rol_actual = strtolower(trim($user_data['rol'] ?? 'usuario'));

// --- FILTROS GLOBALES ---
$fecha_inicio = $_GET['f_inicio'] ?? '';
$fecha_fin    = $_GET['f_fin'] ?? '';
$estado_f      = $_GET['estado_f'] ?? '';
$remitente_f  = $_GET['remitente_f'] ?? ''; 

// --- LÓGICA DE ENVÍO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_documento']) && $rol_actual === 'usuario') {
    $destinatario = $_POST['gerente_dni'];
    $nombre_archivo = $_FILES['pdf_documento']['name'];
    $ruta_temporal = $_FILES['pdf_documento']['tmp_name'];
    $directorio = "documentos/";
    if (!is_dir($directorio)) { mkdir($directorio, 0777, true); }
    $ruta_final = $directorio . time() . "_" . $nombre_archivo;

    if (move_uploaded_file($ruta_temporal, $ruta_final)) {
        $stmt_ins = $conexion->prepare("INSERT INTO documentos (id_remitente, id_destinatario, nombre_archivo, ruta_archivo, estado, fecha_creacion) VALUES (?, ?, ?, ?, 'Pendiente', NOW())");
        $stmt_ins->bind_param("ssss", $dni_usuario, $destinatario, $nombre_archivo, $ruta_final);
        $stmt_ins->execute();
        header("Location: gestion.php?msg=ok");
        exit();
    }
}

// --- CONSULTA ---
if ($rol_actual === 'admin') {
    $sql = "SELECT d.*, u_rem.nombre as remitente, u_dest.nombre as destinatario FROM documentos d 
            LEFT JOIN usuarios u_rem ON d.id_remitente = u_rem.dni 
            LEFT JOIN usuarios u_dest ON d.id_destinatario = u_dest.dni WHERE 1=1";
} elseif ($rol_actual === 'firmante') {
    $sql = "SELECT d.*, u_rem.nombre as remitente, u_dest.nombre as destinatario FROM documentos d 
            LEFT JOIN usuarios u_rem ON d.id_remitente = u_rem.dni 
            LEFT JOIN usuarios u_dest ON d.id_destinatario = u_dest.dni 
            WHERE d.id_destinatario = '$dni_usuario' AND d.estado = 'Pendiente'";
} else {
    $sql = "SELECT d.*, u_rem.nombre as remitente, u_dest.nombre as destinatario FROM documentos d 
            LEFT JOIN usuarios u_rem ON d.id_remitente = u_rem.dni 
            LEFT JOIN usuarios u_dest ON d.id_destinatario = u_dest.dni 
            WHERE d.id_remitente = '$dni_usuario'";
}

if (!empty($fecha_inicio) && !empty($fecha_fin)) { $sql .= " AND DATE(d.fecha_creacion) BETWEEN '$fecha_inicio' AND '$fecha_fin'"; }
if (!empty($estado_f)) { $sql .= " AND d.estado = '$estado_f'"; }
if (!empty($remitente_f)) { $sql .= " AND u_rem.nombre LIKE '%$remitente_f%'"; }

$sql .= " ORDER BY d.fecha_creacion DESC";
$resultado_docs = $conexion->query($sql);
$firmantes_list = $conexion->query("SELECT dni, nombre FROM usuarios WHERE rol = 'firmante'");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Documentos | FIRMAPE</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)), url("imagenes/fondope.png"); background-size: cover; background-attachment: fixed; }
        .header-gestion { background: white; padding: 12px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { border-bottom: 3px solid #4db8ff; padding-bottom: 10px; font-size: 1.2rem; color: #333; margin-top: 0; }
        .filtro-bar { display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; border: 1px solid #eee; flex-wrap: wrap; }
        .filtro-bar div { display: flex; flex-direction: column; gap: 4px; }
        .filtro-bar label { font-size: 11px; font-weight: bold; color: #555; }
        .filtro-bar input, .filtro-bar select { padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 12px; }
        .btn-filtrar { background: #4db8ff; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-limpiar { background: #bbb; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background: #f1f4f6; }
        th { text-align: left; padding: 12px 10px; font-size: 11px; color: #666; text-transform: uppercase; border-bottom: 2px solid #ddd; }
        td { padding: 15px 10px; border-bottom: 1px solid #eee; font-size: 13px; vertical-align: middle; }
        .file-link { font-weight: bold; color: #333; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-firmado { background: #d1fae5; color: #065f46; }
        .status-rechazado { background: #fee2e2; color: #991b1b; }
        .btn-accion { text-decoration: none; padding: 7px 12px; border-radius: 5px; font-size: 10px; font-weight: bold; color: white; display: inline-block; transition: 0.2s; }
        .btn-elec { background: #4db8ff; }
        .btn-digi { background: #6c5ce7; }
        .btn-rechazar { background: #ff4d4d; }
    </style>
</head>
<body>
<header class="header-gestion">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="imagenes/favicon.png" width="30">
        <h3 style="margin:0; color: #333;">GESTIÓN - <?= strtoupper($rol_actual) ?></h3>
    </div>
    <a href="principal.php" style="text-decoration:none; color:#4db8ff; font-weight:bold;">VOLVER</a>
</header>

<div class="container">
    <?php if ($rol_actual === 'usuario'): ?>
    <div class="card">
        <h2>📤 Solicitar Nueva Firma</h2>
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">Firmante:</label>
                <select name="gerente_dni" style="padding:10px; width:250px; border-radius:6px; border:1px solid #ddd;" required>
                    <option value="">-- Seleccione --</option>
                    <?php while($f = $firmantes_list->fetch_assoc()): ?>
                        <option value="<?= $f['dni'] ?>"><?= htmlspecialchars($f['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">PDF:</label>
                <input type="file" name="pdf_documento" accept="application/pdf" required>
            </div>
            <button type="submit" style="background:#4db8ff; color:white; border:none; padding:12px 20px; border-radius:5px; cursor:pointer; font-weight:bold;">ENVIAR</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2>📂 Listado de Documentos</h2>
        <form method="GET" class="filtro-bar">
            <div><label>Desde:</label><input type="date" name="f_inicio" value="<?= $fecha_inicio ?>"></div>
            <div><label>Hasta:</label><input type="date" name="f_fin" value="<?= $fecha_fin ?>"></div>
            <div>
                <label>Estado:</label>
                <select name="estado_f">
                    <option value="">-- Todos --</option>
                    <option value="Pendiente" <?= ($estado_f == 'Pendiente') ? 'selected' : '' ?>>Pendientes</option>
                    <option value="Firmado" <?= ($estado_f == 'Firmado') ? 'selected' : '' ?>>Firmados</option>
                    <option value="Rechazado" <?= ($estado_f == 'Rechazado') ? 'selected' : '' ?>>Rechazados</option>
                </select>
            </div>
            <div><label>Remitente:</label><input type="text" name="remitente_f" placeholder="Nombre..." value="<?= htmlspecialchars($remitente_f) ?>"></div>
            <button type="submit" class="btn-filtrar">FILTRAR</button>
            <a href="gestion.php" class="btn-limpiar">LIMPIAR</a>
        </form>

        <table>
            <thead>
                <tr><th>Fecha Envío</th><th>Documento</th><th>Remitente</th><th>Destinatario</th><th style="text-align:center;">Estado / Acción</th></tr>
            </thead>
            <tbody>
                <?php if($resultado_docs && $resultado_docs->num_rows > 0): ?>
                    <?php while($doc = $resultado_docs->fetch_assoc()): ?>
                    <tr>
                        <td style="color:#888; font-family:monospace; font-size:12px;">📅 <?= date('d/m/Y H:i', strtotime($doc['fecha_creacion'])) ?></td>
                        <td><a href="<?= $doc['ruta_archivo'] ?>" target="_blank" class="file-link">📄 <?= htmlspecialchars($doc['nombre_archivo']) ?></a></td>
                        <td><?= htmlspecialchars($doc['remitente'] ?? 'S/N') ?></td>
                        <td><?= htmlspecialchars($doc['destinatario'] ?? 'S/N') ?></td>
                        <td style="text-align:center;">
                            <?php if ($rol_actual === 'firmante'): ?>
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <a href="firmar.php?id_doc=<?= $doc['id'] ?>&archivo=<?= urlencode($doc['ruta_archivo']) ?>" class="btn-accion btn-elec">F. ELECTRÓNICA</a>
                                    
                                    <a href="firmar_documento.php?id_doc=<?= $doc['id'] ?>&archivo_existente=<?= urlencode($doc['ruta_archivo']) ?>" class="btn-accion btn-digi">F. DIGITAL</a>
                                    
                                    <a href="rechazar.php?id=<?= $doc['id'] ?>" class="btn-accion btn-rechazar">RECHAZAR</a>
                                </div>
                            <?php else: ?>
                                <span class="status-badge status-<?= strtolower($doc['estado']) ?>"><?= $doc['estado'] ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:50px; color:#999;">No hay registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>