<?php
session_start();
include 'config/conexion.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$archivo_automatico = isset($_GET['archivo']) ? $_GET['archivo'] : "";
$id_doc = isset($_GET['id_doc']) ? $_GET['id_doc'] : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmar documento | FIRMAPE</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body {
            margin: 0; font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)), url("imagenes/fondope.png");
            background-size: cover; background-attachment: fixed;
            display: flex; justify-content: center; min-height: 100vh; padding: 20px;
        }

        .container {
            width: 480px; background: white; padding: 25px; border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2); text-align: center;
        }

        #pdfContainer {
            position: relative; margin-top: 20px; display: inline-block;
            line-height: 0; background: transparent;
        }
        #pdfCanvas { max-width: 100%; height: auto; display: block; border: none; }

        /* Contenedor de la firma con botón de borrar */
        #wrapperFirma {
            position: absolute; width: 120px; cursor: move; display: none; z-index: 100;
        }
        #firmaImg { width: 100%; filter: contrast(1.2) brightness(1.1); display: block; }
        
        .btn-borrar-firma {
            position: absolute; top: -12px; right: -12px; background: #ff4d4d;
            color: white; border-radius: 50%; width: 24px; height: 24px;
            line-height: 22px; text-align: center; font-size: 16px; font-weight: bold;
            cursor: pointer; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
        }

        #canvasFirma { border: 2px dashed #4db8ff; background: #fff; border-radius: 8px; cursor: crosshair; }

        button {
            width: 100%; margin: 8px 0; padding: 12px; background: #4db8ff;
            border: none; color: white; border-radius: 8px; cursor: pointer;
            font-weight: bold; transition: 0.3s;
        }
        button:hover { background: #1a8cff; }
        
        .btn-aux { background: #64748b; width: 48%; display: inline-block; }
        .btn-finalizar { background: #10b981; font-size: 16px; margin-top: 15px; }

        .nav-links {
            margin-top: 20px; display: flex; justify-content: center; gap: 20px;
            border-top: 1px solid #eee; padding-top: 15px;
        }
        .nav-links a { text-decoration: none; color: #64748b; font-size: 13px; font-weight: bold; }
        .nav-links a:hover { color: #4db8ff; }

        .upload-area {
            background: #f1f5f9; border: 1px solid #cbd5e1;
            padding: 15px; border-radius: 8px; margin: 15px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>✍️ Firmar Documento</h2>

    <form id="formFirma" method="POST" action="procesar_firma.php" enctype="multipart/form-data" onsubmit="return confirmarFirma();">
        <input type="hidden" name="id_doc" value="<?= $id_doc ?>">

        <div style="text-align: left; margin-bottom: 10px;">
            <p style="font-size: 13px; font-weight: bold; margin-bottom:5px;">1. Archivo seleccionado:</p>
            <input type="file" id="inputPdf" name="pdf" accept="application/pdf" style="width:100%;" <?= $archivo_automatico ? "" : "required" ?>>
            <?php if($archivo_automatico): ?>
                <p style="color: #10b981; font-size: 12px; font-weight: bold; margin: 5px 0;">✅ Archivo cargado desde gestión</p>
                <input type="hidden" id="pdfAutoRuta" name="pdf_ruta_auto" value="<?= $archivo_automatico ?>">
            <?php endif; ?>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 13px; font-weight: bold; text-align: left;">2. Firma (Dibuja o Sube Foto):</p>
        <canvas id="canvasFirma" width="350" height="150"></canvas>
        
        <div style="display: flex; justify-content: space-between;">
            <button type="button" class="btn-aux" onclick="limpiarDibujo()">Limpiar</button>
            <button type="button" class="btn-aux" onclick="usarDibujo()">Usar dibujo</button>
        </div>

        <div class="upload-area">
            <b style="font-size: 12px; color: #475569;">¿Tienes la firma en foto?</b><br>
            <input type="file" id="inputFoto" accept="image/*" style="margin:10px 0;">
            <button type="button" onclick="procesarImagenFirma()" style="background:#0ea5e9; font-size:12px;">Quitar fondo y Usar</button>
        </div>

        <p style="font-size: 13px; font-weight: bold; text-align: left;">3. Ubica la firma en el documento:</p>
        <div id="pdfContainer">
            <canvas id="pdfCanvas"></canvas>
            <div id="wrapperFirma">
                <div class="btn-borrar-firma" onclick="borrarFirmaColocada()">×</div>
                <img id="firmaImg" src="">
            </div>
        </div>

        <input type="hidden" name="pX" id="pX" value="0">
        <input type="hidden" name="pY" id="pY" value="0">
        <input type="hidden" name="firmaBase64" id="firmaBase64">

        <button type="submit" class="btn-finalizar">FINALIZAR Y GUARDAR</button>
    </form>

    <div class="nav-links">
        <a href="gestion.php">⬅ Volver a Gestión</a>
        <a href="principal.php">🏠 Volver al Panel</a>
    </div>
</div>

<canvas id="canvasOculto" style="display:none;"></canvas>

<script>
const pdfCanvas = document.getElementById("pdfCanvas");
const ctxPDF = pdfCanvas.getContext("2d");
const inputPdf = document.getElementById("inputPdf");
const wrapperFirma = document.getElementById("wrapperFirma");
const fImg = document.getElementById("firmaImg");
const canvasFirma = document.getElementById("canvasFirma");
const ctxF = canvasFirma.getContext("2d");
let pdfCargado = false;
let dibujando = false;

// --- FUNCIÓN DE CONFIRMACIÓN NUEVA ---
function confirmarFirma() {
    if(!document.getElementById("firmaBase64").value) {
        alert("Debe colocar la firma en el documento antes de finalizar.");
        return false;
    }
    return confirm("¿Está seguro de que desea guardar el documento firmado?");
}

// --- RENDERIZAR PDF AUTOMÁTICO ---
const urlAuto = "<?= $archivo_automatico ?>";

function cargarPDFdesdeURL(url) {
    pdfjsLib.getDocument(url).promise.then(pdf => {
        pdf.getPage(1).then(page => {
            const viewport = page.getViewport({ scale: 0.7 });
            pdfCanvas.width = viewport.width;
            pdfCanvas.height = viewport.height;
            pdfCanvas.style.border = "1px solid #ccc"; 
            page.render({ canvasContext: ctxPDF, viewport });
            pdfCargado = true;
        });
    }).catch(err => console.error("Error cargando PDF:", err));
}

window.onload = () => { if (urlAuto !== "") cargarPDFdesdeURL(urlAuto); };

// --- LÓGICA DE DIBUJO ---
canvasFirma.onmousedown = () => dibujando = true;
canvasFirma.onmouseup = () => { dibujando = false; ctxF.beginPath(); };
canvasFirma.onmousemove = (e) => {
    if (!dibujando) return;
    ctxF.lineWidth = 3; ctxF.lineCap = "round"; ctxF.strokeStyle = "#000";
    ctxF.lineTo(e.offsetX, e.offsetY); ctxF.stroke();
    ctxF.beginPath(); ctxF.moveTo(e.offsetX, e.offsetY);
};

function limpiarDibujo() { ctxF.clearRect(0, 0, canvasFirma.width, canvasFirma.height); }

function borrarFirmaColocada() {
    wrapperFirma.style.display = "none";
    fImg.src = "";
    document.getElementById("firmaBase64").value = "";
}

function usarDibujo() {
    if (!pdfCargado) return alert("⚠️ No hay ningún PDF cargado");
    posicionarFirma(canvasFirma.toDataURL("image/png"));
}

function procesarImagenFirma() {
    if (!pdfCargado) return alert("⚠️ No hay ningún PDF cargado");
    const file = document.getElementById('inputFoto').files[0];
    if (!file) return alert("Selecciona la foto de tu firma");

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const oCanvas = document.getElementById('canvasOculto');
            const oCtx = oCanvas.getContext('2d');
            const maxW = 800;
            const ratio = maxW / img.width;
            oCanvas.width = maxW; oCanvas.height = img.height * ratio;
            oCtx.drawImage(img, 0, 0, oCanvas.width, oCanvas.height);
            
            const imageData = oCtx.getImageData(0, 0, oCanvas.width, oCanvas.height);
            const data = imageData.data;
            let sum = 0;
            for(let i=0; i<data.length; i+=4) sum += (data[i]+data[i+1]+data[i+2])/3;
            let avg = sum / (data.length/4);
            let limit = avg * 0.85;

            for (let i = 0; i < data.length; i += 4) {
                const lum = (data[i] + data[i+1] + data[i+2]) / 3;
                if (lum > limit) data[i+3] = 0; 
                else { data[i]=0; data[i+1]=0; data[i+2]=0; }
            }
            oCtx.putImageData(imageData, 0, 0);
            posicionarFirma(oCanvas.toDataURL("image/png"));
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function posicionarFirma(url) {
    document.getElementById("firmaBase64").value = url;
    fImg.src = url;
    wrapperFirma.style.display = "block";
    const rect = pdfCanvas.getBoundingClientRect();
    let x = (rect.width / 2) - 60;
    let y = (rect.height / 2) - 30;
    wrapperFirma.style.left = x + "px";
    wrapperFirma.style.top = y + "px";
    
    document.getElementById("pX").value = x / rect.width;
    document.getElementById("pY").value = y / rect.height;
}

// CARGA MANUAL
inputPdf.addEventListener("change", function(e) {
    const file = e.target.files[0];
    if(!file) return;
    const reader = new FileReader();
    reader.onload = function() {
        const typed = new Uint8Array(this.result);
        pdfjsLib.getDocument(typed).promise.then(pdf => {
            pdf.getPage(1).then(page => {
                const viewport = page.getViewport({ scale: 0.7 });
                pdfCanvas.width = viewport.width;
                pdfCanvas.height = viewport.height;
                pdfCanvas.style.border = "1px solid #ccc"; 
                page.render({ canvasContext: ctxPDF, viewport });
                pdfCargado = true;
            });
        });
    };
    reader.readAsArrayBuffer(file);
});

// ARRASTRAR FIRMA
wrapperFirma.onmousedown = function(e) {
    if(e.target.classList.contains('btn-borrar-firma')) return;
    e.preventDefault();
    function mover(ev) {
        const rect = pdfCanvas.getBoundingClientRect();
        let x = ev.clientX - rect.left - (wrapperFirma.offsetWidth / 2);
        let y = ev.clientY - rect.top - (wrapperFirma.offsetHeight / 2);
        x = Math.max(0, Math.min(x, rect.width - wrapperFirma.offsetWidth));
        y = Math.max(0, Math.min(y, rect.height - wrapperFirma.offsetHeight));
        wrapperFirma.style.left = x + "px"; wrapperFirma.style.top = y + "px";
        document.getElementById("pX").value = x / rect.width;
        document.getElementById("pY").value = y / rect.height;
    }
    document.addEventListener("mousemove", mover);
    document.onmouseup = () => document.removeEventListener("mousemove", mover);
};
</script>
</body>
</html>