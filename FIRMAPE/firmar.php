<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmar Documento</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        /* --- ESTILO CORPORATIVO INTEGRADO --- */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)),
                        url("imagenes/fondope.png");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 450px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            text-align: center;
        }

        h2 { color: #333; margin-top: 0; }

        /* --- ZONA DE FIRMA --- */
        #canvasFirma {
            border: 2px dashed #4db8ff;
            background: #fff;
            border-radius: 8px;
            cursor: crosshair;
            margin: 10px 0;
            max-width: 100%;
        }

        .upload-area {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 15px;
            border-radius: 8px;
            margin: 15px auto;
            width: 92%;
        }

        /* --- VISOR DE PDF --- */
        #pdfContainer {
            position: relative;
            margin-top: 20px;
            display: inline-block;
            border: 1px solid #ccc;
            background: white;
            line-height: 0;
            overflow: hidden;
        }

        #pdfCanvas {
            max-width: 100%;
            height: auto;
            display: block;
        }

        #firmaImg {
            position: absolute;
            width: 120px; /* Tamaño inicial de la firma en el PDF */
            cursor: move;
            display: none;
            z-index: 100;
            filter: contrast(1.1) brightness(1.1);
        }

        /* --- BOTONES --- */
        button {
            width: 92%;
            margin: 8px auto;
            display: block;
            padding: 12px;
            background: #4db8ff;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        button:hover { background: #1a8cff; }

        .btn-aux {
            background: #64748b;
            width: 46%;
            display: inline-block;
        }

        input[type="file"] {
            margin: 10px 0;
            width: 100%;
            font-size: 13px;
        }

        .links a {
            text-decoration: none;
            color: #4db8ff;
            font-weight: 500;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Firmar Documento</h2>

    <form method="POST" action="procesar_firma.php" enctype="multipart/form-data">
        
        <p style="font-size: 13px; font-weight: bold;">1. Sube el documento PDF:</p>
        <input type="file" name="pdf" accept="application/pdf" required>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 13px; font-weight: bold;">2. Tu firma (Dibuja o Sube foto):</p>
        <canvas id="canvasFirma" width="350" height="150"></canvas>
        
        <div style="display: flex; justify-content: space-between; width: 92%; margin: 0 auto;">
            <button type="button" class="btn-aux" onclick="limpiarCanvas()">Limpiar</button>
            <button type="button" class="btn-aux" onclick="usarDibujo()">Usar dibujo</button>
        </div>

        <div class="upload-area">
            <span style="font-size: 12px; color: #475569;">¿Tienes la firma en foto? súbela aquí:</span>
            <input type="file" id="inputFoto" accept="image/*">
            <button type="button" onclick="procesarImagenFirma()" style="background:#0ea5e9; width: 100%; font-size:12px;">Quitar fondo y colocar</button>
        </div>

        <p style="font-size: 13px; font-weight: bold; margin-top: 20px;">3. Arrastra la firma a su posición:</p>
        <div id="pdfContainer">
            <canvas id="pdfCanvas"></canvas>
            <img id="firmaImg" alt="Firma">
        </div>

        <input type="hidden" name="pX" id="pX" value="0">
        <input type="hidden" name="pY" id="pY" value="0">
        <input type="hidden" name="firmaBase64" id="firmaBase64">

        <button type="submit" style="background: #10b981; font-size: 16px; margin-top: 25px;">GENERAR PDF FIRMADO</button>
    </form>

    <div class="links" style="margin-top: 20px;">
        <a href="principal.php">⬅ Volver al Panel</a>
    </div>
</div>

<canvas id="canvasOculto" style="display:none;"></canvas>

<script>
// --- CONFIGURACIÓN DE DIBUJO ---
const canvas = document.getElementById("canvasFirma");
const ctx = canvas.getContext("2d");
let dibujando = false;

canvas.onmousedown = () => dibujando = true;
canvas.onmouseup = () => { dibujando = false; ctx.beginPath(); };
canvas.onmousemove = (e) => {
    if (!dibujando) return;
    ctx.lineWidth = 3;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
};

function limpiarCanvas() { ctx.clearRect(0, 0, canvas.width, canvas.height); }

function usarDibujo() {
    posicionarFirma(canvas.toDataURL("image/png"));
}

// --- PROCESAMIENTO DE IMAGEN (QUITA FONDOS GRISES/FOTOS) ---
function procesarImagenFirma() {
    const file = document.getElementById('inputFoto').files[0];
    if (!file) { alert("Por favor, selecciona una imagen"); return; }

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const oCanvas = document.getElementById('canvasOculto');
            const oCtx = oCanvas.getContext('2d', { willReadFrequently: true });
            
            // Redimensionar para procesar
            const scale = 800 / img.width;
            oCanvas.width = 800;
            oCanvas.height = img.height * scale;
            oCtx.drawImage(img, 0, 0, oCanvas.width, oCanvas.height);

            const imageData = oCtx.getImageData(0, 0, oCanvas.width, oCanvas.height);
            const data = imageData.data;

            // Algoritmo Adaptativo (Busca el promedio de brillo de la foto)
            let totalLum = 0;
            for (let i = 0; i < data.length; i += 4) {
                totalLum += (data[i] + data[i+1] + data[i+2]) / 3;
            }
            const avgLum = totalLum / (data.length / 4);
            
            // Umbral: Lo que sea más oscuro que el 70% del promedio es trazo
            const threshold = avgLum * 0.8;

            for (let i = 0; i < data.length; i += 4) {
                const brightness = (data[i] + data[i+1] + data[i+2]) / 3;
                if (brightness > threshold) {
                    data[i+3] = 0; // Transparente (Fondo)
                } else {
                    data[i] = 0; data[i+1] = 0; data[i+2] = 0; // Negro puro (Trazo)
                }
            }
            oCtx.putImageData(imageData, 0, 0);
            posicionarFirma(oCanvas.toDataURL("image/png"));
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// --- POSICIONAR AL CENTRO DEL PDF ---
function posicionarFirma(url) {
    document.getElementById("firmaBase64").value = url;
    const fImg = document.getElementById("firmaImg");
    fImg.src = url;
    fImg.style.display = "block";

    const rect = pdfCanvas.getBoundingClientRect();
    if(rect.width > 0) {
        // Calcular centro
        let x = (rect.width / 2) - (fImg.width / 2);
        let y = (rect.height / 2) - (fImg.height / 2);

        fImg.style.left = x + "px";
        fImg.style.top = y + "px";

        // Guardar porcentajes
        document.getElementById("pX").value = x / rect.width;
        document.getElementById("pY").value = y / rect.height;
    }
}

// --- VISOR DE PDF (PDF.JS) ---
const pdfCanvas = document.getElementById("pdfCanvas");
const ctxPDF = pdfCanvas.getContext("2d");

document.querySelector('input[name="pdf"]').addEventListener("change", function(e) {
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
                page.render({ canvasContext: ctxPDF, viewport });
            });
        });
    };
    reader.readAsArrayBuffer(file);
});

// --- MOVIMIENTO DE LA FIRMA (DRAG AND DROP) ---
const fImg = document.getElementById("firmaImg");
fImg.onmousedown = function(e) {
    e.preventDefault();
    function mover(ev) {
        const rect = pdfCanvas.getBoundingClientRect();
        let x = ev.clientX - rect.left - (fImg.width / 2);
        let y = ev.clientY - rect.top - (fImg.height / 2);
        
        // Limites
        x = Math.max(0, Math.min(x, rect.width - fImg.width));
        y = Math.max(0, Math.min(y, rect.height - fImg.height));
        
        fImg.style.left = x + "px";
        fImg.style.top = y + "px";
        
        document.getElementById("pX").value = x / rect.width;
        document.getElementById("pY").value = y / rect.height;
    }
    document.addEventListener("mousemove", mover);
    document.onmouseup = () => document.removeEventListener("mousemove", mover);
};
</script>

</body>
</html>