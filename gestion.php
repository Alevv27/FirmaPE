<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Documentos | FIRMAPE</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)), url("imagenes/fondope.png"); background-size: cover; background-attachment: fixed; }
        <?php render_firmape_topbar_styles(); ?>
        <?php render_firmape_sidebar_styles(); ?>
        .container { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
        .card { background: white; border-radius: 14px; padding: 0; box-shadow: 0 14px 35px rgba(15,23,42,0.14); overflow:hidden; }
        .card-title { background:#0f69b5; color:white; padding:18px 22px; display:flex; align-items:center; justify-content:space-between; gap:14px; }
        .card-title h2 { margin:0; border:0; padding:0; font-size:20px; color:white; }
        .card-title .help { width:22px; height:22px; border-radius:50%; background:white; color:#0f69b5; display:inline-flex; align-items:center; justify-content:center; font-weight:900; }
        .form-shell { padding:26px 22px 24px; }
        h2 { border-bottom: 3px solid #4db8ff; padding-bottom: 10px; font-size: 1.2rem; color: #333; margin-top: 0; }
        .btn-primary { background:#0f69b5; color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-weight:800; box-shadow:0 5px 12px rgba(15,105,181,.24); }
        .btn-primary:hover { background:#0b5a9d; }
        .alert { padding:12px; border-radius:8px; margin-bottom:15px; font-weight:700; }
        .alert-error { background:#fee2e2; color:#991b1b; }
        .alert-success { background:#d1fae5; color:#065f46; }
        .process-form { display:grid; grid-template-columns: 1fr 1fr; gap:22px 28px; align-items:end; }
        .field label { display:block; font-size:12px; font-weight:800; margin-bottom:5px; color:#334155; }
        .field input, .field textarea, .field select { width:100%; padding:12px 10px; border:0; border-bottom:1px solid #94a3b8; box-sizing:border-box; outline:none; background:white; font-size:14px; }
        .field input:focus, .field textarea:focus, .field select:focus { border-bottom-color:#0f69b5; box-shadow:0 1px 0 #0f69b5; }
        .field textarea { min-height:72px; resize:vertical; }
        .full { grid-column: 1 / -1; }
        .section-label { display:flex; align-items:center; gap:10px; font-size:22px; font-weight:900; margin:8px 0 10px; }
        .section-label .doc-icon { font-size:30px; line-height:1; }
        .drop-zone { grid-column:1 / -1; border:1px dashed #cbd5e1; min-height:118px; display:flex; align-items:center; justify-content:center; text-align:center; background:#fff; cursor:pointer; color:#0f172a; transition:.2s ease; }
        .drop-zone:hover, .drop-zone.dragover { border-color:#0f69b5; background:#f0f9ff; }
        .drop-zone strong { display:block; font-size:16px; margin-bottom:5px; }
        .drop-zone small { color:#64748b; }
        .drop-zone.has-file { border-color:#10b981; background:#f0fdf4; }
        .drop-zone input { display:none; }
        .actions-row { grid-column:1 / -1; display:flex; justify-content:flex-end; align-items:center; gap:12px; padding-top:4px; border-top:1px solid #e2e8f0; }
        .preview-wrap { display:none; grid-column: 1 / -1; gap:14px; grid-template-columns: 260px 1fr; align-items:start; padding:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
        .preview-controls { display:grid; gap:10px; }
        #gestionPdfStage { position:relative; width:max-content; max-width:100%; line-height:0; background:white; border:1px solid #cbd5e1; }
        #gestionPdfCanvas { display:block; max-width:100%; height:auto; }
        #gestionFirmaBox { position:absolute; left:30px; top:30px; width:140px; min-height:44px; border:2px solid #6c5ce7; background:rgba(241,245,249,.86); color:#4338ca; border-radius:6px; cursor:move; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; text-align:center; line-height:1.1; }
    </style>
</head>
<body>
<?php render_firmape_topbar(); ?>

<?php render_firmape_sidebar('gestion'); ?>
<main class="module-content">
<div class="container">
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje === 'success' ? 'alert-success' : 'alert-error' ?>"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($canUpload): ?>
    <div class="card">
        <div class="card-title">
            <h2>Registrar Proceso</h2>
            <span class="help" title="Complete los datos, cargue un PDF y ubique la firma.">?</span>
        </div>
        <div class="form-shell">
        <form method="POST" enctype="multipart/form-data" class="process-form">
            <div class="field">
                <label>Titulo</label>
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
                <label>Descripcion</label>
                <textarea name="descripcion" placeholder="Describe el documento o instrucciones para el firmante..."></textarea>
            </div>

            <div class="full section-label">
                <span>Documentos</span>
                <span class="doc-icon">&#128196;</span>
            </div>
            <label id="dropZoneProceso" class="drop-zone">
                <input type="file" id="pdfDocumento" name="pdf_documento" accept="application/pdf" required>
                <span>
                    <strong id="dropZoneTitle">Soltar un archivo PDF aqui</strong>
                    <small id="dropZoneSubtitle">o haga clic para seleccionar el documento</small>
                </span>
            </label>

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
            <div class="actions-row">
                <button type="submit" class="btn-primary">CREAR PROCESO</button>
            </div>
        </form>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>
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
const dropZoneProceso = document.getElementById('dropZoneProceso');
const dropZoneTitle = document.getElementById('dropZoneTitle');
const dropZoneSubtitle = document.getElementById('dropZoneSubtitle');

if (pdfInputGestion) {
    pdfInputGestion.addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;
        actualizarDropZone(file);
        const data = new Uint8Array(await file.arrayBuffer());
        gestionPdfDoc = await pdfjsLib.getDocument(data).promise;
        gestionTotal = gestionPdfDoc.numPages;
        gestionPagina = 1;
        document.getElementById('gestionPaginasTotal').textContent = gestionTotal;
        previewFirmaProceso.style.display = 'grid';
        await renderGestionPagina();
    });
}

if (dropZoneProceso && pdfInputGestion) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZoneProceso.addEventListener(eventName, function(e) {
            e.preventDefault();
            dropZoneProceso.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZoneProceso.addEventListener(eventName, function(e) {
            e.preventDefault();
            dropZoneProceso.classList.remove('dragover');
        });
    });

    dropZoneProceso.addEventListener('drop', function(e) {
        const file = e.dataTransfer.files[0];
        if (!file || file.type !== 'application/pdf') {
            dropZoneTitle.textContent = 'Seleccione un archivo PDF valido';
            dropZoneSubtitle.textContent = 'El documento debe tener extension .pdf';
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        pdfInputGestion.files = transfer.files;
        pdfInputGestion.dispatchEvent(new Event('change'));
    });
}

function actualizarDropZone(file) {
    dropZoneProceso?.classList.add('has-file');
    if (dropZoneTitle) dropZoneTitle.textContent = file.name;
    if (dropZoneSubtitle) dropZoneSubtitle.textContent = 'PDF cargado. Puede ajustar la posicion de firma abajo.';
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
