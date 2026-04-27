<?php
session_start();
require_once 'includes/auth.php';
require_module('GESTION');

$usuario = current_user();
$usuarioId = (int) $usuario['id'];
$perfil = current_profile();
$canUpload = in_array($perfil, ['GESTOR', 'ADMIN'], true);
$mensaje = '';

$usuariosResponse = api_request('GET', '/usuarios');
$usuariosApi = $usuariosResponse['ok'] ? ($usuariosResponse['data']['usuarios'] ?? []) : [];
$usuariosPorId = [];
$firmantes = [];
foreach ($usuariosApi as $u) {
    $usuariosPorId[(string) $u['id']] = $u;
    if (($u['perfil'] ?? '') === 'FIRMANTE' && ($u['activo'] ?? true)) {
        $firmantes[] = $u;
    }
}

$fecha_inicio = $_GET['f_inicio'] ?? '';
$fecha_fin = $_GET['f_fin'] ?? '';
$estado_f = $_GET['estado_f'] ?? '';
$remitente_f = trim($_GET['remitente_f'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_documento']) && $canUpload) {
    $destinatario = (int) ($_POST['firmante_id'] ?? 0);
    $nombre_archivo = basename($_FILES['pdf_documento']['name'] ?? '');
    $ruta_temporal = $_FILES['pdf_documento']['tmp_name'] ?? '';

    if ($destinatario <= 0 || $nombre_archivo === '') {
        $mensaje = 'Selecciona firmante y documento.';
    } elseif (!$conexion) {
        $mensaje = 'No hay conexion disponible para documentos.';
    } else {
        $directorio = 'documentos/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }
        $ruta_final = $directorio . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre_archivo);

        if (move_uploaded_file($ruta_temporal, $ruta_final)) {
            $stmt = $conexion->prepare("INSERT INTO documentos (id_remitente, id_destinatario, nombre_archivo, ruta_archivo, estado, fecha_creacion) VALUES (?, ?, ?, ?, 'Pendiente', NOW())");
            $remitenteStr = (string) $usuarioId;
            $destinatarioStr = (string) $destinatario;
            $stmt->bind_param("ssss", $remitenteStr, $destinatarioStr, $nombre_archivo, $ruta_final);
            $stmt->execute();
            header('Location: gestion.php?msg=ok');
            exit;
        }
        $mensaje = 'No se pudo guardar el archivo.';
    }
}

$docs = [];
if ($conexion) {
    $where = [];
    $params = [];
    $types = '';

    if ($perfil === 'FIRMANTE') {
        $where[] = 'id_destinatario = ?';
        $params[] = (string) $usuarioId;
        $types .= 's';
    } elseif ($perfil !== 'ADMIN') {
        $where[] = 'id_remitente = ?';
        $params[] = (string) $usuarioId;
        $types .= 's';
    }

    if ($fecha_inicio !== '' && $fecha_fin !== '') {
        $where[] = 'DATE(fecha_creacion) BETWEEN ? AND ?';
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
        $types .= 'ss';
    }
    if ($estado_f !== '') {
        $where[] = 'estado = ?';
        $params[] = $estado_f;
        $types .= 's';
    }

    $sql = 'SELECT * FROM documentos';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY fecha_creacion DESC';

    $stmt = $conexion->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $remitenteNombre = $usuariosPorId[(string) ($row['id_remitente'] ?? '')]['nombre'] ?? 'S/N';
        if ($remitente_f !== '' && stripos($remitenteNombre, $remitente_f) === false) {
            continue;
        }
        $docs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Documentos | FIRMAPE</title>
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
        .file-link { font-weight: bold; color: #333; text-decoration: none; }
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-firmado { background: #d1fae5; color: #065f46; }
        .status-rechazado { background: #fee2e2; color: #991b1b; }
        .btn-accion { text-decoration: none; padding: 7px 12px; border-radius: 5px; font-size: 10px; font-weight: bold; color: white; display: inline-block; }
        .btn-elec { background: #4db8ff; }
        .btn-digi { background: #6c5ce7; }
        .btn-rechazar { background: #ff4d4d; }
        .alert { background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:15px; font-weight:700; }
    </style>
</head>
<body>
<header class="header-gestion">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="imagenes/favicon.png" width="30" alt="logo">
        <h3 style="margin:0; color: #333;">GESTION - <?= e($perfil) ?></h3>
    </div>
    <a href="principal.php" style="text-decoration:none; color:#4db8ff; font-weight:bold;">VOLVER</a>
</header>

<div class="container">
    <?php if ($mensaje): ?><div class="alert"><?= e($mensaje) ?></div><?php endif; ?>

    <?php if ($canUpload): ?>
    <div class="card">
        <h2>Solicitar Nueva Firma</h2>
        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">Firmante:</label>
                <select name="firmante_id" style="padding:10px; width:250px; border-radius:6px; border:1px solid #ddd;" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($firmantes as $f): ?>
                        <option value="<?= (int) $f['id'] ?>"><?= e($f['nombre']) ?> - <?= e($f['email']) ?></option>
                    <?php endforeach; ?>
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
        <h2>Listado de Documentos</h2>
        <form method="GET" class="filtro-bar">
            <div><label>Desde:</label><input type="date" name="f_inicio" value="<?= e($fecha_inicio) ?>"></div>
            <div><label>Hasta:</label><input type="date" name="f_fin" value="<?= e($fecha_fin) ?>"></div>
            <div>
                <label>Estado:</label>
                <select name="estado_f">
                    <option value="">-- Todos --</option>
                    <option value="Pendiente" <?= ($estado_f === 'Pendiente') ? 'selected' : '' ?>>Pendientes</option>
                    <option value="Firmado" <?= ($estado_f === 'Firmado') ? 'selected' : '' ?>>Firmados</option>
                    <option value="Rechazado" <?= ($estado_f === 'Rechazado') ? 'selected' : '' ?>>Rechazados</option>
                </select>
            </div>
            <div><label>Remitente:</label><input type="text" name="remitente_f" placeholder="Nombre..." value="<?= e($remitente_f) ?>"></div>
            <button type="submit" class="btn-filtrar">FILTRAR</button>
            <a href="gestion.php" class="btn-limpiar">LIMPIAR</a>
        </form>

        <table>
            <thead>
                <tr><th>Fecha envio</th><th>Documento</th><th>Remitente</th><th>Destinatario</th><th style="text-align:center;">Estado / Accion</th></tr>
            </thead>
            <tbody>
                <?php if ($docs): ?>
                    <?php foreach ($docs as $doc): ?>
                    <?php
                        $remitente = $usuariosPorId[(string) ($doc['id_remitente'] ?? '')]['nombre'] ?? 'S/N';
                        $destinatario = $usuariosPorId[(string) ($doc['id_destinatario'] ?? '')]['nombre'] ?? 'S/N';
                        $estado = $doc['estado'] ?? '';
                    ?>
                    <tr>
                        <td style="color:#888; font-family:monospace; font-size:12px;"><?= e(date('d/m/Y H:i', strtotime($doc['fecha_creacion'] ?? 'now'))) ?></td>
                        <td><a href="<?= e($doc['ruta_archivo'] ?? '#') ?>" target="_blank" class="file-link"><?= e($doc['nombre_archivo'] ?? '') ?></a></td>
                        <td><?= e($remitente) ?></td>
                        <td><?= e($destinatario) ?></td>
                        <td style="text-align:center;">
                            <?php if ($perfil === 'FIRMANTE' && $estado === 'Pendiente'): ?>
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <a href="firmar.php?id_doc=<?= (int) $doc['id'] ?>&archivo=<?= urlencode($doc['ruta_archivo']) ?>" class="btn-accion btn-elec">F. ELECTRONICA</a>
                                    <a href="firmar_documento.php?id_doc=<?= (int) $doc['id'] ?>&archivo_existente=<?= urlencode($doc['ruta_archivo']) ?>" class="btn-accion btn-digi">F. DIGITAL</a>
                                    <a href="rechazar.php?id=<?= (int) $doc['id'] ?>" class="btn-accion btn-rechazar">RECHAZAR</a>
                                </div>
                            <?php else: ?>
                                <span class="status-badge status-<?= e(strtolower($estado)) ?>"><?= e($estado) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:50px; color:#999;">No hay registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
