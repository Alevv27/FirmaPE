<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
require_once 'includes/toast.php';
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
    $tipoDocumento = trim($_POST['tipo_documento'] ?? '');
    $firmaPagina = (int) ($_POST['firma_pagina'] ?? 1);
    $firmaModo = trim($_POST['firma_modo'] ?? 'actual');
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
                    'tipo_documento' => $tipoDocumento,
                    'nombre_archivo' => $nombreOriginal,
                    'ruta_archivo' => $rutaPublica,
                    'firma_pagina' => $firmaPagina,
                    'firma_modo' => $firmaModo,
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

$toast = toast_message($mensaje, $tipoMensaje === 'success' ? 'success' : 'error');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Documentos | FIRMAPE</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <?php render_sweetalert_assets(); ?>
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
        .process-form { display:grid; grid-template-columns: 1fr 1fr; gap:22px 28px; align-items:end; }
        .field label { display:block; font-size:12px; font-weight:800; margin-bottom:5px; color:#334155; }
        .field input, .field textarea, .field select { width:100%; padding:12px 10px; border:0; border-bottom:1px solid #94a3b8; box-sizing:border-box; outline:none; background:white; font-size:14px; }
        .field input:focus, .field textarea:focus, .field select:focus { border-bottom-color:#0f69b5; box-shadow:0 1px 0 #0f69b5; }
        .field textarea { min-height:72px; resize:vertical; }
        .firmante-picker { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center; border-bottom:1px solid #94a3b8; padding:4px 0 8px; min-height:49px; }
        .firmante-selected { min-width:0; color:#64748b; font-size:14px; }
        .firmante-selected-card { display:grid; grid-template-columns:36px 1fr; align-items:center; gap:10px; min-width:0; }
        .firmante-selected-avatar { width:36px; height:36px; border-radius:50%; background:#e0f2fe; color:#0f69b5; display:flex; align-items:center; justify-content:center; font-weight:900; overflow:hidden; flex:0 0 auto; }
        .firmante-selected-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .firmante-selected strong { display:block; color:#0f172a; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .firmante-selected span { display:block; color:#64748b; font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
        .btn-add-firmante { border:0; border-radius:8px; background:#0f69b5; color:white; padding:11px 14px; cursor:pointer; font-weight:900; white-space:nowrap; }
        .btn-add-firmante:hover { background:#0b5a9d; }
        .full { grid-column: 1 / -1; }
        .section-label { display:flex; align-items:center; gap:10px; font-size:22px; font-weight:900; margin:8px 0 10px; }
        .section-label .doc-icon { font-size:30px; line-height:1; }
        .drop-zone { grid-column:1 / -1; border:1px dashed #cbd5e1; min-height:118px; display:flex; align-items:center; justify-content:center; text-align:center; background:#fff; cursor:pointer; color:#0f172a; transition:.2s ease; }
        .drop-zone:hover, .drop-zone.dragover { border-color:#0f69b5; background:#f0f9ff; }
        .drop-zone strong { display:block; font-size:16px; margin-bottom:5px; }
        .drop-zone small { color:#64748b; }
        .drop-zone.has-file { border-color:#10b981; background:#f0fdf4; }
        .drop-zone input { display:none; }
        .document-row { display:none; grid-column:1 / -1; grid-template-columns: 1fr 220px auto auto auto; gap:14px; align-items:center; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; background:rgba(248,250,252,.95); }
        .document-row.show { display:grid; }
        .doc-main { display:flex; align-items:center; gap:12px; min-width:0; }
        .doc-pdf-icon { width:42px; height:42px; border-radius:10px; background:#fee2e2; color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:900; }
        .doc-name { font-weight:900; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .doc-size { color:#64748b; font-size:12px; margin-top:4px; }
        .doc-type-field { position:relative; }
        .doc-type-field label { position:absolute; top:-9px; left:12px; background:#f8fafc; color:#64748b; font-size:12px; padding:0 6px; }
        .doc-type-field select { width:100%; border:1px solid #dbe3ef; border-radius:6px; padding:12px 34px 12px 12px; background:#f8fafc; font-size:13px; color:#334155; outline:none; }
        .status-pill { border:0; border-radius:999px; padding:8px 14px; background:#dcfce7; color:#047857; font-weight:900; white-space:nowrap; }
        .icon-action { width:38px; height:38px; border:0; border-radius:8px; background:transparent; cursor:pointer; font-size:23px; line-height:1; display:flex; align-items:center; justify-content:center; }
        .icon-action:hover { background:#eef2ff; }
        .icon-action.delete { color:#ef4444; }
        .icon-action.config { color:#0f172a; }
        .actions-row { grid-column:1 / -1; display:flex; justify-content:flex-end; align-items:center; gap:12px; padding-top:4px; border-top:1px solid #e2e8f0; }
        .modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; padding:24px; z-index:1000; }
        .modal-overlay.show { display:flex; }
        .modal-card { width:min(1120px, 96vw); max-height:92vh; background:white; border-radius:12px; box-shadow:0 25px 60px rgba(15,23,42,.35); display:flex; flex-direction:column; overflow:hidden; }
        .modal-card.firmante-modal { width:min(920px, 96vw); }
        .modal-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid #e2e8f0; }
        .modal-head h3 { margin:0; color:#0f172a; }
        .modal-head p { margin:4px 0 0; color:#64748b; font-size:14px; }
        .modal-close { width:36px; height:36px; border:0; border-radius:8px; background:#f1f5f9; font-size:22px; cursor:pointer; }
        .modal-actions { display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #e2e8f0; }
        .btn-muted { background:#64748b; color:white; border:none; padding:12px 18px; border-radius:6px; cursor:pointer; font-weight:800; }
        .firmante-body { padding:18px 20px; display:grid; gap:14px; overflow:auto; }
        .search-box { position:relative; }
        .search-box input { width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:13px 14px; font-size:15px; outline:none; }
        .search-box input:focus { border-color:#0f69b5; box-shadow:0 0 0 3px rgba(15,105,181,.12); }
        .firmante-summary { display:flex; justify-content:space-between; align-items:center; gap:12px; background:#f1f5f9; border-radius:8px; padding:12px 14px; color:#334155; font-weight:800; font-size:13px; }
        .firmante-list { display:grid; gap:8px; max-height:440px; overflow:auto; padding-right:4px; }
        .firmante-item { width:100%; border:1px solid #e2e8f0; background:#fff; border-radius:10px; padding:12px; display:grid; grid-template-columns:44px 1fr auto; align-items:center; gap:12px; cursor:pointer; text-align:left; }
        .firmante-item:hover, .firmante-item.selected { border-color:#60a5fa; background:#eff6ff; }
        .firmante-avatar { width:44px; height:44px; border-radius:50%; background:#e0f2fe; color:#0f69b5; display:flex; align-items:center; justify-content:center; font-weight:900; overflow:hidden; flex:0 0 auto; }
        .firmante-avatar img { width:100%; height:100%; object-fit:cover; display:block; }
        .firmante-name { color:#0f172a; font-weight:900; margin-bottom:4px; }
        .firmante-meta { color:#64748b; font-size:13px; }
        .firmante-role { border-radius:999px; background:#dcfce7; color:#047857; padding:7px 10px; font-size:11px; font-weight:900; white-space:nowrap; }
        .firmante-empty { padding:22px; text-align:center; color:#64748b; background:#f8fafc; border-radius:10px; border:1px dashed #cbd5e1; }
        .preview-wrap { display:grid; gap:14px; grid-template-columns: 260px 1fr; align-items:start; padding:18px 20px; overflow:auto; }
        .preview-controls { display:grid; gap:10px; }
        .config-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .field-select { width:100%; padding:10px 11px; border:1px solid #cbd5e1; border-radius:10px; background:#fff; color:#1e293b; font-size:13px; font-weight:700; outline:none; }
        .field-select:focus { border-color:#6c5ce7; box-shadow:0 0 0 3px rgba(108,92,231,.14); }
        .page-custom-input { display:none; width:100%; margin-top:8px; padding:10px 11px; border:1px solid #cbd5e1; border-radius:10px; font-size:13px; font-weight:700; outline:none; }
        .page-custom-input.show { display:block; }
        .page-custom-input:focus { border-color:#6c5ce7; box-shadow:0 0 0 3px rgba(108,92,231,.14); }
        #gestionPdfStage { position:relative; width:max-content; max-width:100%; line-height:0; background:white; border:1px solid #cbd5e1; }
        #gestionPdfCanvas { display:block; max-width:100%; height:auto; }
        #gestionFirmaBox { position:absolute; left:30px; top:30px; width:140px; min-height:44px; border:2px solid #6c5ce7; background:rgba(241,245,249,.86); color:#4338ca; border-radius:6px; cursor:move; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:900; text-align:center; line-height:1.1; }
        @media (max-width: 900px) {
            .process-form { grid-template-columns:1fr; }
            .document-row { grid-template-columns:1fr; align-items:stretch; }
            .preview-wrap { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php render_firmape_topbar(); ?>

<?php render_firmape_sidebar('gestion'); ?>
<main class="module-content">
<div class="container">
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
                <input type="hidden" name="firmante_id" id="firmanteId" value="">
                <div class="firmante-picker">
                    <div id="firmanteSeleccionado" class="firmante-selected">
                        <strong>Sin firmante seleccionado</strong>
                        <span>Agrega un firmante para este proceso.</span>
                    </div>
                    <button type="button" class="btn-add-firmante" onclick="abrirFirmanteModal()">Agregar firmante</button>
                </div>
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

            <div id="documentRowProceso" class="document-row">
                <div class="doc-main">
                    <div class="doc-pdf-icon">PDF</div>
                    <div style="min-width:0;">
                        <div id="documentNameProceso" class="doc-name">Documento.pdf</div>
                        <div id="documentSizeProceso" class="doc-size">0 MB</div>
                    </div>
                </div>
                <div class="doc-type-field">
                    <label>Tipo de Archivo</label>
                    <select name="tipo_documento" id="tipoDocumentoProceso">
                        <option value="OTROS">OTROS</option>
                        <option value="CONTRATO">CONTRATO</option>
                        <option value="SOLICITUD">SOLICITUD</option>
                        <option value="DECLARACION">DECLARACION</option>
                        <option value="CONSTANCIA">CONSTANCIA</option>
                    </select>
                </div>
                <span class="status-pill">✓ Listo</span>
                <button type="button" class="icon-action delete" title="Quitar documento" onclick="quitarDocumentoGestion()">×</button>
                <button type="button" class="icon-action config" title="Configurar firma" onclick="abrirConfiguracionFirma()">⚙</button>
            </div>

            <input type="hidden" name="firma_pagina" id="firmaPagina" value="1">
            <input type="hidden" name="firma_modo" id="firmaModo" value="actual">
            <input type="hidden" name="firma_x" id="firmaX" value="">
            <input type="hidden" name="firma_y" id="firmaY" value="">
            <input type="hidden" name="firma_w" id="firmaW" value="">
            <div class="actions-row">
                <button type="submit" class="btn-primary">CREAR PROCESO</button>
            </div>
        </form>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>
<div id="firmanteModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card firmante-modal" role="dialog" aria-modal="true" aria-labelledby="firmanteModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="firmanteModalTitle">Agregar firmante</h3>
                <p>Busca por nombre, apellido, DNI o correo.</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarFirmanteModal()">×</button>
        </div>
        <div class="firmante-body">
            <div class="search-box">
                <input type="search" id="buscarFirmante" placeholder="Buscar firmante..." autocomplete="off">
            </div>
            <div class="firmante-summary">
                <span id="firmanteResumen">0 firmantes disponibles</span>
                <button type="button" class="btn-muted" onclick="limpiarFirmanteSeleccionado()">Limpiar seleccion</button>
            </div>
            <div id="firmanteLista" class="firmante-list"></div>
        </div>
    </div>
</div>
<div id="firmaConfigModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="firmaConfigTitle">
        <div class="modal-head">
            <h3 id="firmaConfigTitle">Configurar firma del documento</h3>
            <button type="button" class="modal-close" onclick="cerrarConfiguracionFirma()">×</button>
        </div>
        <div id="previewFirmaProceso" class="preview-wrap">
            <div class="preview-controls">
                <strong>Posicion de firma</strong>
                <small>Arrastra el recuadro al lugar donde debe firmar el usuario. Si no guardas esta configuracion, el firmante podra elegir la posicion.</small>
                <div class="config-grid">
                    <div>
                        <label>Posicion Firma</label>
                        <select id="gestionPosicionFirma" class="field-select">
                            <option value="manual">Manual</option>
                            <option value="superior_derecha">Superior Derecha</option>
                            <option value="superior_izquierda">Superior Izquierda</option>
                            <option value="superior_medio">Superior Medio</option>
                            <option value="medio_derecha">Medio Derecha</option>
                            <option value="medio_izquierda">Medio Izquierda</option>
                            <option value="medio_medio">Medio Medio</option>
                            <option value="inferior_derecha">Inferior Derecha</option>
                            <option value="inferior_izquierda">Inferior Izquierda</option>
                            <option value="inferior_medio">Inferior Medio</option>
                        </select>
                    </div>
                    <div>
                        <label>Pagina Firma</label>
                        <select id="gestionPaginaFirmaModo" class="field-select">
                            <option value="actual">Pagina visible</option>
                            <option value="primera">Primera pagina</option>
                            <option value="ultima">Ultima pagina</option>
                            <option value="personalizada">Personalizada</option>
                            <option value="todas">Todas las paginas</option>
                        </select>
                        <input type="number" id="gestionPaginaPersonalizada" class="page-custom-input" min="1" value="1" placeholder="Numero de pagina">
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center; margin-top:4px;">
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
        <div class="modal-actions">
            <button type="button" class="btn-muted" onclick="limpiarConfiguracionFirma()">Dejar que el firmante elija</button>
            <button type="button" class="btn-primary" onclick="guardarConfiguracionFirma()">Guardar configuracion</button>
        </div>
    </div>
</div>
<?php render_toast_script($toast); ?>
</div>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const firmantesDisponibles = <?= json_encode(array_values($firmantes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let firmanteSeleccionadoId = '';
let gestionPdfDoc = null;
let gestionPagina = 1;
let gestionTotal = 1;
let gestionFirmaConfigurada = false;
let gestionPaginaInputTimer = null;
const pdfInputGestion = document.getElementById('pdfDocumento');
const previewFirmaProceso = document.getElementById('previewFirmaProceso');
const gestionCanvas = document.getElementById('gestionPdfCanvas');
const gestionCtx = gestionCanvas ? gestionCanvas.getContext('2d') : null;
const gestionFirmaBox = document.getElementById('gestionFirmaBox');
const dropZoneProceso = document.getElementById('dropZoneProceso');
const dropZoneTitle = document.getElementById('dropZoneTitle');
const dropZoneSubtitle = document.getElementById('dropZoneSubtitle');
const documentRowProceso = document.getElementById('documentRowProceso');
const documentNameProceso = document.getElementById('documentNameProceso');
const documentSizeProceso = document.getElementById('documentSizeProceso');
const firmaConfigModal = document.getElementById('firmaConfigModal');
const firmanteModal = document.getElementById('firmanteModal');
const buscarFirmante = document.getElementById('buscarFirmante');
const firmanteLista = document.getElementById('firmanteLista');
const firmanteResumen = document.getElementById('firmanteResumen');
const firmanteSeleccionado = document.getElementById('firmanteSeleccionado');
const firmanteIdInput = document.getElementById('firmanteId');

function inicialesFirmante(firmante) {
    const nombre = `${firmante.nombre || ''} ${firmante.apellido || ''}`.trim();
    return nombre
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((parte) => parte.charAt(0).toUpperCase())
        .join('') || 'F';
}

function fotoFirmanteSrc(firmante) {
    const foto = String(firmante.fotoPerfil || firmante.foto_perfil || '').trim();
    if (!foto) return '';
    if (/^https?:\/\//i.test(foto)) return foto;
    return foto.replace(/^\/+/, '');
}

function avatarFirmanteHtml(firmante, clase = 'firmante-avatar') {
    const foto = fotoFirmanteSrc(firmante);
    if (foto) {
        return `<span class="${clase}"><img src="${escapeHtml(foto)}" alt="Foto de ${escapeHtml(nombreFirmante(firmante))}"></span>`;
    }
    return `<span class="${clase}">${inicialesFirmante(firmante)}</span>`;
}

function textoFirmante(firmante) {
    return `${firmante.nombre || ''} ${firmante.apellido || ''} ${firmante.dni || ''} ${firmante.email || ''}`.toLowerCase();
}

function nombreFirmante(firmante) {
    return `${firmante.nombre || ''} ${firmante.apellido || ''}`.trim() || 'Firmante';
}

function abrirFirmanteModal() {
    firmanteModal.classList.add('show');
    firmanteModal.setAttribute('aria-hidden', 'false');
    renderFirmantes();
    setTimeout(() => buscarFirmante?.focus(), 50);
}

function cerrarFirmanteModal() {
    firmanteModal.classList.remove('show');
    firmanteModal.setAttribute('aria-hidden', 'true');
}

function renderFirmantes() {
    const query = (buscarFirmante?.value || '').trim().toLowerCase();
    const filtrados = firmantesDisponibles.filter((firmante) => textoFirmante(firmante).includes(query));
    firmanteResumen.textContent = `${filtrados.length} firmante${filtrados.length === 1 ? '' : 's'} disponible${filtrados.length === 1 ? '' : 's'}`;

    if (!filtrados.length) {
        firmanteLista.innerHTML = '<div class="firmante-empty">No hay firmantes para la busqueda ingresada.</div>';
        return;
    }

    firmanteLista.innerHTML = filtrados.map((firmante) => {
        const id = String(firmante.id);
        const selected = id === String(firmanteSeleccionadoId) ? ' selected' : '';
        const dni = firmante.dni || 'Sin DNI';
        const email = firmante.email || 'Sin correo';
        return `
            <button type="button" class="firmante-item${selected}" data-id="${id}">
                ${avatarFirmanteHtml(firmante)}
                <span>
                    <span class="firmante-name">${escapeHtml(nombreFirmante(firmante))}</span>
                    <span class="firmante-meta">${escapeHtml(dni)} - ${escapeHtml(email)}</span>
                </span>
                <span class="firmante-role">FIRMANTE</span>
            </button>
        `;
    }).join('');
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
}

function seleccionarFirmante(id) {
    const firmante = firmantesDisponibles.find((item) => String(item.id) === String(id));
    if (!firmante) return;
    firmanteSeleccionadoId = String(firmante.id);
    firmanteIdInput.value = firmanteSeleccionadoId;
    firmanteSeleccionado.innerHTML = `
        <div class="firmante-selected-card">
            ${avatarFirmanteHtml(firmante, 'firmante-selected-avatar')}
            <div>
                <strong>${escapeHtml(nombreFirmante(firmante))}</strong>
                <span>${escapeHtml(firmante.dni || 'Sin DNI')} - ${escapeHtml(firmante.email || 'Sin correo')}</span>
            </div>
        </div>
    `;
    cerrarFirmanteModal();
    renderFirmantes();
}

function limpiarFirmanteSeleccionado() {
    firmanteSeleccionadoId = '';
    firmanteIdInput.value = '';
    firmanteSeleccionado.innerHTML = '<strong>Sin firmante seleccionado</strong><span>Agrega un firmante para este proceso.</span>';
    renderFirmantes();
}

buscarFirmante?.addEventListener('input', renderFirmantes);
firmanteLista?.addEventListener('click', (event) => {
    const item = event.target.closest('.firmante-item');
    if (item) seleccionarFirmante(item.dataset.id);
});

document.querySelector('.process-form')?.addEventListener('submit', (event) => {
    if (!firmanteIdInput.value) {
        event.preventDefault();
        Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Seleccione un firmante para crear el proceso.', showConfirmButton:false, timer:2800 });
        abrirFirmanteModal();
    }
});

if (pdfInputGestion) {
    pdfInputGestion.addEventListener('change', async function() {
        const file = this.files[0];
        if (!file) return;
        actualizarDropZone(file);
        const data = new Uint8Array(await file.arrayBuffer());
        gestionPdfDoc = await pdfjsLib.getDocument(data).promise;
        gestionTotal = gestionPdfDoc.numPages;
        gestionPagina = 1;
        gestionFirmaConfigurada = false;
        document.getElementById('firmaX').value = '';
        document.getElementById('firmaY').value = '';
        document.getElementById('firmaW').value = '';
        document.getElementById('firmaModo').value = 'actual';
        document.getElementById('firmaPagina').value = '1';
        document.getElementById('gestionPaginasTotal').textContent = gestionTotal;
        const paginaPersonalizada = document.getElementById('gestionPaginaPersonalizada');
        paginaPersonalizada.max = gestionTotal;
        paginaPersonalizada.value = 1;
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
    if (dropZoneProceso) dropZoneProceso.style.display = 'none';
    documentRowProceso?.classList.add('show');
    if (documentNameProceso) documentNameProceso.textContent = file.name;
    if (documentSizeProceso) documentSizeProceso.textContent = formatearTamano(file.size);
    if (dropZoneTitle) dropZoneTitle.textContent = file.name;
    if (dropZoneSubtitle) dropZoneSubtitle.textContent = 'PDF cargado.';
}

function formatearTamano(bytes) {
    if (!bytes) return '0 MB';
    return (bytes / (1024 * 1024)).toFixed(3).replace(/\.?0+$/, '') + ' MB';
}

async function abrirConfiguracionFirma() {
    if (!gestionPdfDoc) {
        Swal.fire({ icon: 'info', title: 'Cargue un PDF primero', timer: 1800, showConfirmButton: false });
        return;
    }
    firmaConfigModal.classList.add('show');
    firmaConfigModal.setAttribute('aria-hidden', 'false');
    await aplicarPaginaFirmaGestion();
    await renderGestionPagina();
}

function cerrarConfiguracionFirma() {
    firmaConfigModal.classList.remove('show');
    firmaConfigModal.setAttribute('aria-hidden', 'true');
}

function guardarConfiguracionFirma() {
    gestionFirmaConfigurada = true;
    actualizarFirmaGestion();
    cerrarConfiguracionFirma();
    Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Configuracion de firma guardada.', showConfirmButton:false, timer:2200 });
}

function limpiarConfiguracionFirma() {
    gestionFirmaConfigurada = false;
    document.getElementById('firmaX').value = '';
    document.getElementById('firmaY').value = '';
    document.getElementById('firmaW').value = '';
    document.getElementById('firmaModo').value = 'actual';
    document.getElementById('firmaPagina').value = '1';
    cerrarConfiguracionFirma();
    Swal.fire({ toast:true, position:'top-end', icon:'info', title:'El firmante elegira la posicion.', showConfirmButton:false, timer:2200 });
}

function quitarDocumentoGestion() {
    if (!pdfInputGestion) return;
    pdfInputGestion.value = '';
    gestionPdfDoc = null;
    gestionPagina = 1;
    gestionTotal = 1;
    gestionFirmaConfigurada = false;
    documentRowProceso?.classList.remove('show');
    if (dropZoneProceso) {
        dropZoneProceso.style.display = 'flex';
        dropZoneProceso.classList.remove('has-file');
    }
    if (dropZoneTitle) dropZoneTitle.textContent = 'Soltar un archivo PDF aqui';
    if (dropZoneSubtitle) dropZoneSubtitle.textContent = 'o haga clic para seleccionar el documento';
    document.getElementById('firmaX').value = '';
    document.getElementById('firmaY').value = '';
    document.getElementById('firmaW').value = '';
    document.getElementById('firmaModo').value = 'actual';
    document.getElementById('firmaPagina').value = '1';
    cerrarConfiguracionFirma();
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
    posicionarFirmaGestion();
}

function cambiarPaginaGestion(delta) {
    if (!gestionPdfDoc) return;
    const next = gestionPagina + delta;
    if (next < 1 || next > gestionTotal) return;
    gestionPagina = next;
    if (document.getElementById('gestionPaginaFirmaModo').value === 'personalizada') {
        document.getElementById('gestionPaginaPersonalizada').value = gestionPagina;
    }
    renderGestionPagina();
}

function posicionarFirmaGestion() {
    if (document.getElementById('gestionPosicionFirma').value !== 'manual') {
        aplicarPosicionFirmaGestion();
        return;
    }

    const rect = gestionCanvas.getBoundingClientRect();
    const boxW = Number(document.getElementById('gestionFirmaSize').value);
    gestionFirmaBox.style.width = boxW + 'px';
    gestionFirmaBox.style.left = Math.max(20, rect.width - boxW - 40) + 'px';
    gestionFirmaBox.style.top = Math.max(20, rect.height - 95) + 'px';
    if (gestionFirmaConfigurada) actualizarFirmaGestion();
}

function aplicarPosicionFirmaGestion() {
    if (!gestionCanvas || !gestionCanvas.width) return;
    const posicion = document.getElementById('gestionPosicionFirma').value;
    if (posicion === 'manual') {
        actualizarFirmaGestion();
        return;
    }

    const rect = gestionCanvas.getBoundingClientRect();
    const margin = 24;
    const xMap = {
        izquierda: margin,
        medio: Math.max(margin, (rect.width - gestionFirmaBox.offsetWidth) / 2),
        derecha: Math.max(margin, rect.width - gestionFirmaBox.offsetWidth - margin),
    };
    const yMap = {
        superior: margin,
        medio: Math.max(margin, (rect.height - gestionFirmaBox.offsetHeight) / 2),
        inferior: Math.max(margin, rect.height - gestionFirmaBox.offsetHeight - margin),
    };
    const [vertical, horizontal] = posicion.split('_');
    gestionFirmaBox.style.left = xMap[horizontal] + 'px';
    gestionFirmaBox.style.top = yMap[vertical] + 'px';
    actualizarFirmaGestion();
}

async function aplicarPaginaFirmaGestion() {
    const modo = document.getElementById('gestionPaginaFirmaModo').value;
    const inputPersonalizado = document.getElementById('gestionPaginaPersonalizada');
    document.getElementById('firmaModo').value = modo;
    inputPersonalizado.max = gestionTotal;
    inputPersonalizado.classList.toggle('show', modo === 'personalizada');

    if (!gestionPdfDoc) return;
    if (modo === 'primera') {
        gestionPagina = 1;
        await renderGestionPagina();
    } else if (modo === 'ultima') {
        gestionPagina = gestionTotal;
        await renderGestionPagina();
    } else if (modo === 'personalizada') {
        const pagina = Math.max(1, Math.min(Number(inputPersonalizado.value || 1), gestionTotal));
        inputPersonalizado.value = pagina;
        gestionPagina = pagina;
        await renderGestionPagina();
    } else {
        document.getElementById('firmaPagina').value = gestionPagina;
        actualizarFirmaGestion();
    }
}

document.getElementById('gestionFirmaSize')?.addEventListener('input', function() {
    gestionFirmaBox.style.width = this.value + 'px';
    aplicarPosicionFirmaGestion();
});

document.getElementById('gestionPosicionFirma')?.addEventListener('change', aplicarPosicionFirmaGestion);
document.getElementById('gestionPaginaFirmaModo')?.addEventListener('change', aplicarPaginaFirmaGestion);
document.getElementById('gestionPaginaPersonalizada')?.addEventListener('change', aplicarPaginaFirmaGestion);
document.getElementById('gestionPaginaPersonalizada')?.addEventListener('input', function() {
    if (document.getElementById('gestionPaginaFirmaModo').value !== 'personalizada') return;
    if (this.value === '') return;
    const pagina = Math.max(1, Math.min(Number(this.value), gestionTotal));
    document.getElementById('firmaPagina').value = pagina;
    clearTimeout(gestionPaginaInputTimer);
    gestionPaginaInputTimer = setTimeout(aplicarPaginaFirmaGestion, 250);
});

gestionFirmaBox?.addEventListener('mousedown', function(e) {
    e.preventDefault();
    document.getElementById('gestionPosicionFirma').value = 'manual';
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
    if (!gestionFirmaConfigurada) return;
    const rect = gestionCanvas.getBoundingClientRect();
    document.getElementById('firmaX').value = gestionFirmaBox.offsetLeft / rect.width;
    document.getElementById('firmaY').value = gestionFirmaBox.offsetTop / rect.height;
    document.getElementById('firmaW').value = gestionFirmaBox.offsetWidth / rect.width;
    document.getElementById('firmaPagina').value = gestionPagina;
    document.getElementById('firmaModo').value = document.getElementById('gestionPaginaFirmaModo').value;
}
</script>
</body>
</html>
