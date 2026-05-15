<?php
session_start();
require_once 'includes/auth.php';
require_module('GESTION');

$usuario = current_user();
$usuarioId = (int) $usuario['id'];
$perfil = current_profile();
$canUpload = in_array($perfil, ['GESTOR', 'ADMIN'], true);
$mensaje = '';
$tipoMensaje = '';

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
    $firmanteId = (int) ($_POST['firmante_id'] ?? 0);
    $nombreProceso = trim($_POST['nombre_proceso'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $firmaPagina = (int) ($_POST['firma_pagina'] ?? 1);
    $firmaX = $_POST['firma_x'] ?? '';
    $firmaY = $_POST['firma_y'] ?? '';
    $firmaW = $_POST['firma_w'] ?? '';
    $archivo = $_FILES['pdf_documento'];

    if ($nombreProceso === '') {
        $mensaje = 'Ingrese el nombre del proceso.';
        $tipoMensaje = 'error';
    } elseif ($firmanteId <= 0) {
        $mensaje = 'Seleccione un firmante.';
        $tipoMensaje = 'error';
    } elseif (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $mensaje = 'No se pudo leer el PDF seleccionado.';
        $tipoMensaje = 'error';
    } else {
        $nombreOriginal = basename((string) $archivo['name']);
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            $mensaje = 'Solo se permiten archivos PDF.';
            $tipoMensaje = 'error';
        } else {
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $nombreSeguro = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreOriginal);
            $nombreArchivo = date('YmdHis') . '_' . $nombreSeguro;
            $rutaDestino = $uploadDir . DIRECTORY_SEPARATOR . $nombreArchivo;
            $rutaPublica = 'uploads/' . $nombreArchivo;

            if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                $mensaje = 'No se pudo guardar el PDF en el front.';
                $tipoMensaje = 'error';
            } else {
                $response = api_request('POST', '/procesos', [
                    'firmante_id' => $firmanteId,
                    'creador_id' => $usuarioId,
                    'nombre_proceso' => $nombreProceso,
                    'descripcion' => $descripcion,
                    'nombre_archivo' => $nombreOriginal,
                    'ruta_archivo' => $rutaPublica,
                    'firma_pagina' => $firmaPagina,
                    'firma_x' => $firmaX,
                    'firma_y' => $firmaY,
                    'firma_w' => $firmaW,
                ]);

                if ($response['ok']) {
                    $mensaje = 'Proceso creado. Se envio la notificacion al firmante.';
                    $tipoMensaje = 'success';
                } else {
                    $mensaje = $response['error'] ?: 'No se pudo crear el proceso de firma.';
                    $tipoMensaje = 'error';
                }
            }
        }
    }
}

$procesosPath = $perfil === 'ADMIN' ? '/procesos' : '/procesos?creador_id=' . $usuarioId;
if ($estado_f !== '') {
    $procesosPath .= (str_contains($procesosPath, '?') ? '&' : '?') . 'estado=' . urlencode(strtoupper($estado_f));
}
if ($fecha_inicio !== '') {
    $procesosPath .= (str_contains($procesosPath, '?') ? '&' : '?') . 'fecha_desde=' . urlencode($fecha_inicio);
}
if ($fecha_fin !== '') {
    $procesosPath .= (str_contains($procesosPath, '?') ? '&' : '?') . 'fecha_hasta=' . urlencode($fecha_fin);
}
$procesosPath .= (str_contains($procesosPath, '?') ? '&' : '?') . 'per_page=10&page=' . max((int) ($_GET['page'] ?? 1), 1);

$docsResponse = api_request('GET', $procesosPath);
$docs = $docsResponse['ok'] ? ($docsResponse['data']['procesos'] ?? []) : [];
$pagination = $docsResponse['ok'] ? ($docsResponse['data']['pagination'] ?? ['page' => 1, 'pages' => 1, 'total' => count($docs)]) : ['page' => 1, 'pages' => 1, 'total' => 0];
if (!$docsResponse['ok'] && !$mensaje) {
    $mensaje = $docsResponse['error'] ?: 'No se pudieron cargar los procesos.';
    $tipoMensaje = 'error';
}

function gestion_query_link(array $params): string
{
    $base = [
        'f_inicio' => $_GET['f_inicio'] ?? '',
        'f_fin' => $_GET['f_fin'] ?? '',
        'estado_f' => $_GET['estado_f'] ?? '',
        'remitente_f' => $_GET['remitente_f'] ?? '',
    ];
    $query = array_filter(array_merge($base, $params), fn($v) => $v !== '' && $v !== null);
    return 'gestion.php' . ($query ? '?' . http_build_query($query) : '');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Documentos | FIRMAPE</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
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
        .status-eliminado { background: #e5e7eb; color: #374151; }
        .btn-accion { text-decoration: none; padding: 7px 12px; border-radius: 5px; font-size: 10px; font-weight: bold; color: white; display: inline-block; }
        .btn-elec { background: #4db8ff; }
        .btn-digi { background: #6c5ce7; }
        .btn-rechazar { background: #ff4d4d; }
        .alert { padding:12px; border-radius:8px; margin-bottom:15px; font-weight:700; }
        .alert-error { background:#fee2e2; color:#991b1b; }
        .alert-success { background:#d1fae5; color:#065f46; }
        .pager { display:flex; justify-content:space-between; align-items:center; margin-top:18px; color:#64748b; font-size:13px; font-weight:700; }
        .pager a, .pager span { padding:8px 12px; border-radius:8px; text-decoration:none; background:#f1f5f9; color:#334155; }
        .pager span.disabled { opacity:.45; }
        .process-form { display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:end; }
        .field label { display:block; font-size:12px; font-weight:800; margin-bottom:5px; color:#334155; }
        .field input, .field textarea, .field select { width:100%; padding:10px; border-radius:6px; border:1px solid #ddd; box-sizing:border-box; }
        .field textarea { min-height:70px; resize:vertical; }
        .full { grid-column: 1 / -1; }
        .preview-wrap { display:none; grid-column: 1 / -1; gap:14px; grid-template-columns: 260px 1fr; align-items:start; padding:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
        .preview-controls { display:grid; gap:10px; }
        #gestionPdfStage { position:relative; width:max-content; max-width:100%; line-height:0; background:white; border:1px solid #cbd5e1; }
        #gestionPdfCanvas { display:block; max-width:100%; height:auto; }
        #gestionFirmaBox { position:absolute; left:30px; top:30px; width:140px; min-height:44px; border:2px solid #6c5ce7; background:rgba(241,245,249,.86); color:#4338ca; border-radius:6px; cursor:move; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; text-align:center; line-height:1.1; }
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
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje === 'success' ? 'alert-success' : 'alert-error' ?>"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($canUpload): ?>
    <div class="card">
        <h2>Crear Nuevo Proceso</h2>
        <form method="POST" enctype="multipart/form-data" class="process-form">
            <div class="field">
                <label>Nombre del proceso:</label>
                <input type="text" name="nombre_proceso" maxlength="180" required>
            </div>
            <div class="field">
                <label>Firmante:</label>
                <select name="firmante_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($firmantes as $f): ?>
                        <option value="<?= (int) $f['id'] ?>"><?= e($f['nombre']) ?> - <?= e($f['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field full">
                <label>Detalle o descripcion:</label>
                <textarea name="descripcion" placeholder="Describe el documento o instrucciones para el firmante..."></textarea>
            </div>
            <div class="field">
                <label>PDF:</label>
                <input type="file" id="pdfDocumento" name="pdf_documento" accept="application/pdf" required>
            </div>
            <div>
                <button type="submit" style="background:#4db8ff; color:white; border:none; padding:12px 20px; border-radius:5px; cursor:pointer; font-weight:bold;">CREAR PROCESO</button>
            </div>

            <div id="previewFirmaProceso" class="preview-wrap">
                <div class="preview-controls">
                    <strong>Posicion de firma</strong>
                    <small>Arrastra el recuadro al lugar donde debe firmar el usuario.</small>
                    <label>Pagina</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <button type="button" onclick="cambiarPaginaGestion(-1)">-</button>
                        <span><b id="gestionPaginaActual">1</b> / <span id="gestionPaginasTotal">1</span></span>
                        <button type="button" onclick="cambiarPaginaGestion(1)">+</button>
                    </div>
                    <label>Tamano</label>
                    <input type="range" id="gestionFirmaSize" min="90" max="260" value="140">
                </div>
                <div id="gestionPdfStage">
                    <canvas id="gestionPdfCanvas"></canvas>
                    <div id="gestionFirmaBox">FIRMA AQUI</div>
                </div>
            </div>
            <input type="hidden" name="firma_pagina" id="firmaPagina" value="1">
            <input type="hidden" name="firma_x" id="firmaX" value="0.15">
            <input type="hidden" name="firma_y" id="firmaY" value="0.75">
            <input type="hidden" name="firma_w" id="firmaW" value="0.25">
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
                    <option value="PENDIENTE" <?= (strtoupper($estado_f) === 'PENDIENTE') ? 'selected' : '' ?>>Pendientes</option>
                    <option value="FIRMADO" <?= (strtoupper($estado_f) === 'FIRMADO') ? 'selected' : '' ?>>Firmados</option>
                    <option value="RECHAZADO" <?= (strtoupper($estado_f) === 'RECHAZADO') ? 'selected' : '' ?>>Rechazados</option>
                    <option value="ELIMINADO" <?= (strtoupper($estado_f) === 'ELIMINADO') ? 'selected' : '' ?>>Eliminados</option>
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
                        $remitente = $doc['creador']['nombre'] ?? 'S/N';
                        $destinatario = $doc['firmante']['nombre'] ?? 'S/N';
                        $estado = strtoupper((string) ($doc['estado'] ?? ''));
                        $fechaProceso = $doc['fecha_creacion'] ?? '';
                    ?>
                    <tr>
                        <td style="color:#888; font-family:monospace; font-size:12px;"><?= e($fechaProceso ? date('d/m/Y H:i', strtotime($fechaProceso)) : '') ?></td>
                        <td>
                            <a href="<?= e($doc['ruta_archivo'] ?? '#') ?>" target="_blank" class="file-link"><?= e($doc['nombre_proceso'] ?: ($doc['nombre_archivo'] ?? '')) ?></a><br>
                            <small><?= e($doc['descripcion'] ?? '') ?></small>
                        </td>
                        <td><?= e($remitente) ?></td>
                        <td><?= e($destinatario) ?></td>
                        <td style="text-align:center;">
                            <span class="status-badge status-<?= e(strtolower($estado)) ?>"><?= e($estado) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:50px; color:#999;">No hay registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pager">
            <div>Mostrando maximo 10 procesos. Total: <?= (int) ($pagination['total'] ?? 0) ?></div>
            <div style="display:flex; gap:8px; align-items:center;">
                <?php if (($pagination['page'] ?? 1) > 1): ?>
                    <a href="<?= e(gestion_query_link(['page' => (int) $pagination['page'] - 1])) ?>">Anterior</a>
                <?php else: ?>
                    <span class="disabled">Anterior</span>
                <?php endif; ?>
                <span>Pagina <?= (int) ($pagination['page'] ?? 1) ?> / <?= (int) ($pagination['pages'] ?? 1) ?></span>
                <?php if (($pagination['page'] ?? 1) < ($pagination['pages'] ?? 1)): ?>
                    <a href="<?= e(gestion_query_link(['page' => (int) $pagination['page'] + 1])) ?>">Siguiente</a>
                <?php else: ?>
                    <span class="disabled">Siguiente</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

let gestionPdfDoc = null;
let gestionPagina = 1;
let gestionTotal = 1;
const pdfInputGestion = document.getElementById('pdfDocumento');
const previewFirmaProceso = document.getElementById('previewFirmaProceso');
const gestionCanvas = document.getElementById('gestionPdfCanvas');
const gestionCtx = gestionCanvas ? gestionCanvas.getContext('2d') : null;
const gestionFirmaBox = document.getElementById('gestionFirmaBox');

if (pdfInputGestion) {
    pdfInputGestion.addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;
        const data = new Uint8Array(await file.arrayBuffer());
        gestionPdfDoc = await pdfjsLib.getDocument(data).promise;
        gestionTotal = gestionPdfDoc.numPages;
        gestionPagina = 1;
        document.getElementById('gestionPaginasTotal').textContent = gestionTotal;
        previewFirmaProceso.style.display = 'grid';
        await renderGestionPagina();
    });
}

async function renderGestionPagina() {
    if (!gestionPdfDoc) return;
    const page = await gestionPdfDoc.getPage(gestionPagina);
    const baseViewport = page.getViewport({ scale: 1 });
    const maxWidth = 760;
    const scale = Math.min(1.1, maxWidth / baseViewport.width);
    const viewport = page.getViewport({ scale });
    gestionCanvas.width = viewport.width;
    gestionCanvas.height = viewport.height;
    gestionCanvas.style.width = viewport.width + 'px';
    gestionCanvas.style.height = viewport.height + 'px';
    await page.render({ canvasContext: gestionCtx, viewport }).promise;
    document.getElementById('gestionPaginaActual').textContent = gestionPagina;
    document.getElementById('firmaPagina').value = gestionPagina;
    posicionInicialGestion();
}

function cambiarPaginaGestion(delta) {
    if (!gestionPdfDoc) return;
    const next = gestionPagina + delta;
    if (next < 1 || next > gestionTotal) return;
    gestionPagina = next;
    renderGestionPagina();
}

function posicionInicialGestion() {
    const rect = gestionCanvas.getBoundingClientRect();
    const boxW = Number(document.getElementById('gestionFirmaSize').value);
    gestionFirmaBox.style.width = boxW + 'px';
    gestionFirmaBox.style.left = Math.max(20, rect.width - boxW - 40) + 'px';
    gestionFirmaBox.style.top = Math.max(20, rect.height - 95) + 'px';
    actualizarFirmaGestion();
}

document.getElementById('gestionFirmaSize')?.addEventListener('input', function() {
    gestionFirmaBox.style.width = this.value + 'px';
    actualizarFirmaGestion();
});

gestionFirmaBox?.addEventListener('mousedown', function(e) {
    e.preventDefault();
    const startX = e.clientX;
    const startY = e.clientY;
    const startLeft = gestionFirmaBox.offsetLeft;
    const startTop = gestionFirmaBox.offsetTop;

    function mover(ev) {
        const rect = gestionCanvas.getBoundingClientRect();
        let left = startLeft + ev.clientX - startX;
        let top = startTop + ev.clientY - startY;
        left = Math.max(0, Math.min(left, rect.width - gestionFirmaBox.offsetWidth));
        top = Math.max(0, Math.min(top, rect.height - gestionFirmaBox.offsetHeight));
        gestionFirmaBox.style.left = left + 'px';
        gestionFirmaBox.style.top = top + 'px';
        actualizarFirmaGestion();
    }

    function soltar() {
        document.removeEventListener('mousemove', mover);
        document.removeEventListener('mouseup', soltar);
    }

    document.addEventListener('mousemove', mover);
    document.addEventListener('mouseup', soltar);
});

function actualizarFirmaGestion() {
    if (!gestionCanvas || !gestionCanvas.width) return;
    const rect = gestionCanvas.getBoundingClientRect();
    document.getElementById('firmaX').value = gestionFirmaBox.offsetLeft / rect.width;
    document.getElementById('firmaY').value = gestionFirmaBox.offsetTop / rect.height;
    document.getElementById('firmaW').value = gestionFirmaBox.offsetWidth / rect.width;
    document.getElementById('firmaPagina').value = gestionPagina;
}
</script>
</body>
</html>
