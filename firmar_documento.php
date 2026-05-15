<?php
session_start();
require_once 'includes/auth.php';

$token = $_GET['token'] ?? '';
$error_token = '';
$archivo_pre_cargado = '';
$id_doc = '';

if ($token !== '') {
    $tokenResponse = api_request('GET', '/firma/token/' . rawurlencode($token));

    if ($tokenResponse['ok']) {
        $dataToken = $tokenResponse['data'];
        $documento = $dataToken['documento'] ?? [];
        $archivo_pre_cargado = (string) ($documento['rutaArchivo'] ?? '');
        $id_doc = (string) ($dataToken['procesoId'] ?? '');
    } else {
        $error_token = $tokenResponse['error'] ?: 'El enlace de firma no es valido.';
    }
} else {
    require_module('FIRMAR');
    require_profile('FIRMANTE', 'ADMIN');
    $archivo_pre_cargado = isset($_GET['archivo_existente']) ? $_GET['archivo_existente'] : '';
    $id_doc = isset($_GET['id_doc']) ? $_GET['id_doc'] : '';
}

$esta_listo = !empty($archivo_pre_cargado) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firma Digital | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        :root { --accent: #6c5ce7; --dark: #1e293b; --muted: #64748b; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)), url("imagenes/fondope.png");
            background-size: cover;
            background-attachment: fixed;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
            align-items: center;
        }
        .container { width: 100%; max-width: 1180px; display: grid; grid-template-columns: 360px 1fr; gap: 25px; }
        .card { background: white; padding: 28px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); height: fit-content; }
        h2 { margin:0 0 18px; color: var(--dark); font-size: 22px; }
        .upload-area { border: 2px dashed #cbd5e1; padding: 18px; border-radius: 14px; text-align: center; cursor: pointer; margin-bottom: 12px; background: #f8fafc; }
        .upload-area:hover { border-color: var(--accent); background: #f1f5f9; }
        .btn { width: 100%; padding: 13px; border-radius: 11px; font-weight: 700; border: none; cursor: pointer; transition: .2s; margin-bottom: 10px; font-size: 14px; text-decoration: none; display: block; text-align: center; }
        .btn-upload { background: var(--dark); color: white; }
        .btn-sign { background: var(--accent); color: white; font-size: 15px; box-shadow: 0 4px 15px rgba(108,92,231,.3); }
        .btn-sign:disabled { opacity: .55; cursor: not-allowed; }
        .btn-muted { background: var(--muted); color: white; }
        .btn-nav { background: #f1f5f9; color: #475569; margin-bottom: 0; }
        .btn-mode { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .btn-mode.active { background: var(--accent); color: white; border-color: var(--accent); }
        .submode { display: none; }
        .submode.active { display: block; }
        .btn-small { padding: 10px; font-size: 12px; border-radius: 9px; }
        .nav-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px; }
        .info-carga { background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 12px; color: #065f46; display: flex; align-items: center; gap: 8px; }
        .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 700; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 16px; font-size: 13px; font-weight: 700; display: none; }
        .signature-tools { display: grid; gap: 10px; margin-bottom: 14px; }
        .field-label { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; }
        #canvasFirma { width: 100%; height: 120px; border: 2px dashed #93c5fd; border-radius: 12px; background: white; cursor: crosshair; }
        .tool-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .range { width: 100%; }
        .page-controls { display: grid; grid-template-columns: 44px 1fr 44px; gap: 8px; align-items: center; }
        .page-indicator { text-align: center; font-size: 12px; font-weight: 700; color: #475569; }
        #preview-container {
            background: rgba(255,255,255,.95);
            border-radius: 22px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            height: 760px;
            overflow: auto;
            border: 1px solid rgba(255,255,255,.3);
            box-shadow: 0 20px 40px rgba(0,0,0,.1);
            backdrop-filter: blur(10px);
            padding: 22px;
        }
        #pdfStage { position: relative; line-height: 0; width: fit-content; margin: 0 auto; }
        #pdfCanvas { display: none; border: 1px solid #cbd5e1; background: white; }
        #firmaBox { position: absolute; left: 40px; top: 40px; width: 150px; display: none; cursor: move; z-index: 10; }
        #firmaPreview { width: 100%; display: block; filter: contrast(1.15) brightness(1.05); }
        #serverStamp { display: none; width: 100%; background: #f8fafc; border: 2px solid #6c5ce7; border-radius: 8px; color: #1e293b; padding: 10px; line-height: 1.25; font-size: 11px; }
        #serverStamp strong { display: block; color: #6c5ce7; text-align: center; font-size: 13px; margin-bottom: 7px; }
        .firma-remove { position: absolute; right: -10px; top: -10px; width: 22px; height: 22px; border-radius: 999px; background: #ef4444; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; cursor: pointer; border: 2px solid white; line-height: 1; }
        .no-preview { color: #94a3b8; font-weight: 600; text-align: center; font-size: 15px; line-height: 1.4; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2>Firma Digital</h2>

        <?php if ($error_token): ?>
            <div class="alert-error"><?= e($error_token) ?></div>
        <?php endif; ?>

        <div id="firmadoMsg" class="alert-success">
            Documento firmado correctamente. Este proceso ya fue cerrado y el enlace no estara disponible para volver a firmar.
        </div>

        <form id="formSubida" style="<?= $token !== '' ? 'display:none;' : '' ?>">
            <div class="upload-area" onclick="document.getElementById('inputPdf').click()">
                <div style="font-size: 24px; margin-bottom: 5px;">PDF</div>
                <span id="fileName" style="font-size: 12px; font-weight: 600; color: #64748b;">Subir nuevo documento</span>
                <input type="file" id="inputPdf" name="pdf_archivo" accept="application/pdf" style="display:none">
            </div>
            <button type="submit" class="btn btn-upload">CARGAR ARCHIVO</button>
        </form>

        <?php if ($archivo_pre_cargado): ?>
            <div class="info-carga">
                <span>OK</span>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <strong>Listo para firmar:</strong><br>
                    <?= e(basename($archivo_pre_cargado)) ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="signature-tools">
            <div class="tool-row">
                <button type="button" id="modoNormalBtn" class="btn btn-small btn-mode active" onclick="cambiarModoFirma('normal')">MANUSCRITA / IMAGEN</button>
                <button type="button" id="modoServidorBtn" class="btn btn-small btn-mode" onclick="cambiarModoFirma('servidor')">FIRMA SERVIDOR</button>
            </div>
            <div id="normalOptions">
                <div class="tool-row">
                    <button type="button" id="modoDibujoBtn" class="btn btn-small btn-mode active" onclick="cambiarSubmodoNormal('dibujo')">DIBUJAR</button>
                    <button type="button" id="modoImagenBtn" class="btn btn-small btn-mode" onclick="cambiarSubmodoNormal('imagen')">SUBIR IMAGEN</button>
                </div>
                <div id="submodoDibujo" class="submode active">
                    <div class="field-label">Firma manuscrita</div>
                    <canvas id="canvasFirma" width="300" height="120"></canvas>
                    <div class="tool-row" style="margin-top:10px;">
                        <button type="button" class="btn btn-small btn-muted" onclick="limpiarDibujo()">LIMPIAR</button>
                        <button type="button" class="btn btn-small btn-muted" onclick="usarDibujo()">USAR DIBUJO</button>
                    </div>
                </div>
                <div id="submodoImagen" class="submode">
                    <div class="field-label">Imagen de firma</div>
                    <input type="file" id="inputFirmaImagen" accept="image/*" style="width:100%;">
                    <button type="button" class="btn btn-small btn-upload" style="margin-top:10px;" onclick="usarImagenFirma()">USAR IMAGEN</button>
                </div>
            </div>
            <div>
                <div class="field-label">Tamaño del estampado</div>
                <input class="range" type="range" id="firmaSize" min="80" max="260" value="150">
            </div>
            <div class="page-controls">
                <button type="button" class="btn btn-small btn-nav" onclick="cambiarPagina(-1)">‹</button>
                <div class="page-indicator">Pagina <span id="paginaActual">1</span> / <span id="paginasTotal">1</span></div>
                <button type="button" class="btn btn-small btn-nav" onclick="cambiarPagina(1)">›</button>
            </div>
        </div>

        <form action="procesar_firma.php" method="POST" id="formFirma">
            <input type="hidden" name="id_doc" value="<?= e($id_doc) ?>">
            <input type="hidden" name="nombre_temp" id="nombre_temp" value="<?= e($archivo_pre_cargado) ?>">
            <input type="hidden" name="pdf_ruta_auto" id="pdf_ruta_auto" value="<?= e($archivo_pre_cargado) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="hidden" name="pX" id="pX" value="0">
            <input type="hidden" name="pY" id="pY" value="0">
            <input type="hidden" name="pW" id="pW" value="0">
            <input type="hidden" name="paginaFirma" id="paginaFirma" value="1">
            <input type="hidden" name="tipoFirma" id="tipoFirma" value="normal">
            <input type="hidden" name="firmaBase64" id="firmaBase64">
            <button type="button" id="btnFirmar" onclick="intentarFirmar()" class="btn btn-sign" <?= $error_token ? 'disabled' : '' ?>>FIRMAR DOCUMENTO</button>
        </form>

        <div class="nav-group">
            <a href="gestion.php" class="btn btn-nav">Gestion</a>
            <a href="principal.php" class="btn btn-nav">Panel</a>
        </div>
    </div>

    <div id="preview-container">
        <div id="pdfStage">
            <div class="no-preview" id="msgNoPreview">
                <div style="font-size: 40px; margin-bottom: 10px;">PDF</div>
                Esperando documento para visualizar
            </div>
            <canvas id="pdfCanvas"></canvas>
            <div id="firmaBox">
                <div class="firma-remove" onclick="quitarFirma()">x</div>
                <img id="firmaPreview" alt="Firma">
                <div id="serverStamp">
                    <strong>FIRMADO ELECTRONICAMENTE</strong>
                    FIRMANTE: <?= e(strtoupper(current_user()['nombre'] ?? 'USUARIO DEL SISTEMA')) ?><br>
                    EMAIL: <?= e(current_user()['email'] ?? '') ?><br>
                    FECHA: <?= e(date('d/m/Y H:i:s')) ?><br>
                    MOTIVO: Aprobacion de documento<br>
                    UBICACION: LIMA, PERU
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let listo = <?= $esta_listo ?>;
const rutaArchivo = <?= json_encode($archivo_pre_cargado, JSON_UNESCAPED_SLASHES) ?>;
let pdfDoc = null;
let paginaActual = 1;
let paginasTotal = 1;
let firmaLista = false;
let modoFirma = 'normal';
let submodoNormal = 'dibujo';

pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const pdfCanvas = document.getElementById('pdfCanvas');
const pdfCtx = pdfCanvas.getContext('2d');
const firmaBox = document.getElementById('firmaBox');
const firmaPreview = document.getElementById('firmaPreview');
const canvasFirma = document.getElementById('canvasFirma');
const firmaCtx = canvasFirma.getContext('2d');
let dibujando = false;

document.getElementById('inputPdf').onchange = function() {
    if (this.files[0]) document.getElementById('fileName').innerText = this.files[0].name;
};

document.getElementById('formSubida').onsubmit = async (e) => {
    e.preventDefault();
    const fileInput = document.getElementById('inputPdf');
    if (!fileInput.files[0]) {
        Swal.fire('Aviso', 'Seleccione un archivo PDF.', 'info');
        return;
    }

    const formData = new FormData();
    formData.append('pdf_archivo', fileInput.files[0]);

    try {
        const response = await fetch('upload_handler.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            const ruta = 'uploads/' + data.filename;
            document.getElementById('nombre_temp').value = ruta;
            document.getElementById('pdf_ruta_auto').value = ruta;
            listo = true;
            await cargarPdf(ruta);
            Swal.fire('Exito', 'Archivo cargado correctamente.', 'success');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Error de comunicacion con el servidor.', 'error');
    }
};

window.onload = function() {
    if (rutaArchivo !== '') cargarPdf(rutaArchivo);
};

async function cargarPdf(url) {
    try {
        pdfDoc = await pdfjsLib.getDocument(url).promise;
        paginasTotal = pdfDoc.numPages;
        paginaActual = 1;
        document.getElementById('paginasTotal').textContent = paginasTotal;
        await renderPagina();
        listo = true;
    } catch (error) {
        Swal.fire('Error', 'No se pudo visualizar el PDF.', 'error');
    }
}

async function renderPagina() {
    if (!pdfDoc) return;
    const page = await pdfDoc.getPage(paginaActual);
    const baseViewport = page.getViewport({ scale: 1 });
    const panel = document.getElementById('preview-container');
    const maxWidth = Math.max(420, panel.clientWidth - 60);
    const scale = Math.min(1.25, maxWidth / baseViewport.width);
    const viewport = page.getViewport({ scale });
    pdfCanvas.width = viewport.width;
    pdfCanvas.height = viewport.height;
    pdfCanvas.style.width = viewport.width + 'px';
    pdfCanvas.style.height = viewport.height + 'px';
    pdfCanvas.style.display = 'block';
    document.getElementById('msgNoPreview').style.display = 'none';
    await page.render({ canvasContext: pdfCtx, viewport }).promise;
    document.getElementById('paginaActual').textContent = paginaActual;
    document.getElementById('paginaFirma').value = paginaActual;
    if (firmaLista) posicionarFirmaInicial();
}

function cambiarPagina(delta) {
    if (!pdfDoc) return;
    const siguiente = paginaActual + delta;
    if (siguiente < 1 || siguiente > paginasTotal) return;
    paginaActual = siguiente;
    renderPagina();
}

canvasFirma.addEventListener('mousedown', (e) => {
    dibujando = true;
    firmaCtx.beginPath();
    firmaCtx.moveTo(e.offsetX, e.offsetY);
});
canvasFirma.addEventListener('mouseup', () => dibujando = false);
canvasFirma.addEventListener('mouseleave', () => dibujando = false);
canvasFirma.addEventListener('mousemove', (e) => {
    if (!dibujando) return;
    firmaCtx.lineWidth = 3;
    firmaCtx.lineCap = 'round';
    firmaCtx.strokeStyle = '#111827';
    firmaCtx.lineTo(e.offsetX, e.offsetY);
    firmaCtx.stroke();
});

function limpiarDibujo() {
    firmaCtx.clearRect(0, 0, canvasFirma.width, canvasFirma.height);
}

function usarDibujo() {
    if (!listo) return Swal.fire('Atencion', 'Primero cargue el PDF.', 'warning');
    colocarFirma(canvasConFondoBlanco(canvasFirma));
}

function usarImagenFirma() {
    if (!listo) return Swal.fire('Atencion', 'Primero cargue el PDF.', 'warning');
    const file = document.getElementById('inputFirmaImagen').files[0];
    if (!file) return Swal.fire('Atencion', 'Seleccione una imagen de firma.', 'warning');
    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => colocarFirma(imagenConFondoBlanco(img));
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function canvasConFondoBlanco(sourceCanvas) {
    const out = document.createElement('canvas');
    out.width = sourceCanvas.width;
    out.height = sourceCanvas.height;
    const ctx = out.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, out.width, out.height);
    ctx.drawImage(sourceCanvas, 0, 0);
    return out.toDataURL('image/jpeg', 0.95);
}

function imagenConFondoBlanco(img) {
    const maxW = 900;
    const ratio = Math.min(1, maxW / img.width);
    const out = document.createElement('canvas');
    out.width = Math.max(1, Math.round(img.width * ratio));
    out.height = Math.max(1, Math.round(img.height * ratio));
    const ctx = out.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, out.width, out.height);
    ctx.drawImage(img, 0, 0, out.width, out.height);
    return out.toDataURL('image/jpeg', 0.95);
}

function colocarFirma(src) {
    modoFirma = 'normal';
    document.getElementById('tipoFirma').value = 'normal';
    document.getElementById('modoNormalBtn').classList.add('active');
    document.getElementById('modoServidorBtn').classList.remove('active');
    document.getElementById('firmaBase64').value = src;
    firmaPreview.src = src;
    firmaPreview.style.display = 'block';
    document.getElementById('serverStamp').style.display = 'none';
    firmaLista = true;
    firmaBox.style.display = 'block';
    firmaBox.style.width = document.getElementById('firmaSize').value + 'px';
    posicionarFirmaInicial();
}

function cambiarModoFirma(modo) {
    if (!listo) return Swal.fire('Atencion', 'Primero cargue el PDF.', 'warning');
    modoFirma = modo;
    document.getElementById('tipoFirma').value = modo;
    document.getElementById('modoNormalBtn').classList.toggle('active', modo === 'normal');
    document.getElementById('modoServidorBtn').classList.toggle('active', modo === 'servidor');

    if (modo === 'servidor') {
        document.getElementById('normalOptions').style.display = 'none';
        document.getElementById('firmaBase64').value = '';
        firmaPreview.style.display = 'none';
        document.getElementById('serverStamp').style.display = 'block';
        firmaLista = true;
        firmaBox.style.display = 'block';
        firmaBox.style.width = Math.max(230, Number(document.getElementById('firmaSize').value)) + 'px';
        posicionarFirmaInicial();
        return;
    }

    document.getElementById('normalOptions').style.display = 'block';
    document.getElementById('serverStamp').style.display = 'none';
    firmaPreview.style.display = 'block';
    if (!document.getElementById('firmaBase64').value) {
        quitarFirma();
    }
}

function cambiarSubmodoNormal(submodo) {
    submodoNormal = submodo;
    document.getElementById('modoDibujoBtn').classList.toggle('active', submodo === 'dibujo');
    document.getElementById('modoImagenBtn').classList.toggle('active', submodo === 'imagen');
    document.getElementById('submodoDibujo').classList.toggle('active', submodo === 'dibujo');
    document.getElementById('submodoImagen').classList.toggle('active', submodo === 'imagen');
    quitarFirma();
}

function posicionarFirmaInicial() {
    const rect = pdfCanvas.getBoundingClientRect();
    firmaBox.style.left = Math.max(20, (rect.width / 2) - (firmaBox.offsetWidth / 2)) + 'px';
    firmaBox.style.top = Math.max(20, rect.height - 120) + 'px';
    actualizarCoordenadas();
}

function quitarFirma() {
    firmaLista = false;
    firmaBox.style.display = 'none';
    document.getElementById('firmaBase64').value = '';
}

document.getElementById('firmaSize').addEventListener('input', function() {
    firmaBox.style.width = this.value + 'px';
    actualizarCoordenadas();
});

firmaBox.addEventListener('mousedown', function(e) {
    if (e.target.classList.contains('firma-remove')) return;
    e.preventDefault();
    const startX = e.clientX;
    const startY = e.clientY;
    const startLeft = firmaBox.offsetLeft;
    const startTop = firmaBox.offsetTop;

    function mover(ev) {
        const rect = pdfCanvas.getBoundingClientRect();
        let left = startLeft + ev.clientX - startX;
        let top = startTop + ev.clientY - startY;
        left = Math.max(0, Math.min(left, rect.width - firmaBox.offsetWidth));
        top = Math.max(0, Math.min(top, rect.height - firmaBox.offsetHeight));
        firmaBox.style.left = left + 'px';
        firmaBox.style.top = top + 'px';
        actualizarCoordenadas();
    }

    function soltar() {
        document.removeEventListener('mousemove', mover);
        document.removeEventListener('mouseup', soltar);
    }

    document.addEventListener('mousemove', mover);
    document.addEventListener('mouseup', soltar);
});

function actualizarCoordenadas() {
    if (!pdfCanvas.width) return;
    const rect = pdfCanvas.getBoundingClientRect();
    document.getElementById('pX').value = firmaBox.offsetLeft / rect.width;
    document.getElementById('pY').value = firmaBox.offsetTop / rect.height;
    document.getElementById('pW').value = firmaBox.offsetWidth / rect.width;
    document.getElementById('paginaFirma').value = paginaActual;
}

function intentarFirmar() {
    if (!listo) {
        Swal.fire('Atencion', 'No hay ningun documento cargado para firmar.', 'warning');
        return;
    }
    if (modoFirma !== 'servidor' && !document.getElementById('firmaBase64').value) {
        Swal.fire('Atencion', 'Debe dibujar o subir una firma y colocarla en el documento.', 'warning');
        return;
    }

    actualizarCoordenadas();
    Swal.fire({
        title: 'Confirmar firma',
        text: 'Se estampara la firma en la posicion seleccionada.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6c5ce7',
        confirmButtonText: 'Si, firmar ahora',
        cancelButtonText: 'Revisar'
    }).then((result) => {
        if (result.isConfirmed) enviarFirma();
    });
}

async function enviarFirma() {
    const btn = document.getElementById('btnFirmar');
    const form = document.getElementById('formFirma');
    btn.disabled = true;
    btn.textContent = 'FIRMANDO...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        });

        const contentType = response.headers.get('content-type') || '';
        if (!response.ok || !contentType.includes('application/pdf')) {
            const errorText = await response.text();
            throw new Error(errorText || 'No se pudo firmar el documento.');
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'documento_firmado_' + Date.now() + '.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);

        cerrarFlujoFirmado();
        Swal.fire('Documento firmado', 'El proceso fue cerrado correctamente.', 'success');
    } catch (error) {
        btn.disabled = false;
        btn.textContent = 'FIRMAR DOCUMENTO';
        Swal.fire('Error', limpiarErrorFirma(error.message), 'error');
    }
}

function cerrarFlujoFirmado() {
    document.getElementById('firmadoMsg').style.display = 'block';
    document.querySelector('.signature-tools').style.display = 'none';
    document.getElementById('formFirma').style.display = 'none';
    quitarFirma();
}

function limpiarErrorFirma(message) {
    return String(message)
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 500) || 'No se pudo firmar el documento.';
}
</script>
</body>
</html>
