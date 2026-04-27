<?php
session_start();
require_once 'includes/auth.php';
require_module('FIRMAR');

// Detectamos si viene un archivo desde la tabla de gestión
$archivo_pre_cargado = isset($_GET['archivo_existente']) ? $_GET['archivo_existente'] : '';
$id_doc = isset($_GET['id_doc']) ? $_GET['id_doc'] : '';

// Variable para el JS: si hay archivo pre-cargado, ya está "listo"
$esta_listo = !empty($archivo_pre_cargado) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firma Digital | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --accent: #6c5ce7; --dark: #1e293b; --success: #10b981; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(to right, rgba(204,231,240,0.7), rgba(126,200,227,0.7)), 
                        url("imagenes/fondope.png");
            background-size: cover;
            background-attachment: fixed;
            margin: 0; 
            padding: 20px; 
            display: flex; 
            justify-content: center; 
            min-height: 100vh;
            align-items: center;
        }

        .container { width: 100%; max-width: 1100px; display: grid; grid-template-columns: 350px 1fr; gap: 25px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); height: fit-content; }
        
        /* Áreas de carga */
        .upload-area { border: 2px dashed #cbd5e1; padding: 20px; border-radius: 16px; text-align: center; cursor: pointer; margin-bottom: 15px; transition: 0.3s; background: #f8fafc; }
        .upload-area:hover { border-color: var(--accent); background: #f1f5f9; }

        /* Botones Base */
        .btn { width: 100%; padding: 14px; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; transition: 0.3s; margin-bottom: 12px; font-size: 14px; text-decoration: none; display: block; text-align: center; box-sizing: border-box; }
        
        /* Botones Principales */
        .btn-upload { background: var(--dark); color: white; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.2); }
        .btn-upload:hover { background: #0f172a; transform: translateY(-2px); }
        
        .btn-sign { background: var(--accent); color: white; font-size: 15px; box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3); }
        .btn-sign:hover { background: #5a4bcf; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(108, 92, 231, 0.4); }

        /* Grupo de Navegación Inferior */
        .nav-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px; }
        .btn-nav { background: #f1f5f9; color: #475569; padding: 12px; font-size: 13px; margin-bottom: 0; }
        .btn-nav:hover { background: #e2e8f0; color: var(--dark); }

        /* Previsualización */
        #preview-container { 
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 24px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 750px; 
            overflow: hidden; 
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        iframe { width: 100%; height: 100%; border: none; display: none; }
        .no-preview { color: #94a3b8; font-weight: 600; text-align: center; font-size: 15px; }
        
        /* Info Carga */
        .info-carga { background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 12px; color: #065f46; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2 style="margin:0 0 20px 0; color: var(--dark); font-size: 22px; letter-spacing: -0.5px;">Firma Digital</h2>
        
        <form id="formSubida">
            <div class="upload-area" onclick="document.getElementById('inputPdf').click()">
                <div style="font-size: 24px; margin-bottom: 5px;">📤</div>
                <span id="fileName" style="font-size: 12px; font-weight: 600; color: #64748b;">Subir nuevo documento</span>
                <input type="file" id="inputPdf" name="pdf_archivo" accept="application/pdf" style="display:none">
            </div>
            <button type="submit" class="btn btn-upload">CARGAR ARCHIVO</button>
        </form>

        <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0;">

        <?php if ($archivo_pre_cargado): ?>
            <div class="info-carga">
                <span>✅</span>
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <strong>Listo para firmar:</strong><br>
                    <?= basename($archivo_pre_cargado) ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="ejecutar_firma.php" method="POST" id="formFirma">
            <input type="hidden" name="id_doc" value="<?= $id_doc ?>">
            <input type="hidden" name="nombre_temp" id="nombre_temp" value="<?= $archivo_pre_cargado ?>">
            
            <button type="button" onclick="intentarFirmar()" class="btn btn-sign">FIRMAR DOCUMENTO</button>
        </form>
        
        <div class="nav-group">
            <a href="gestion.php" class="btn btn-nav">Gestión</a>
            <a href="principal.php" class="btn btn-nav">Panel</a>
        </div>
    </div>

    <div id="preview-container">
        <div class="no-preview" id="msgNoPreview">
            <div style="font-size: 40px; margin-bottom: 10px;">📄</div>
            Esperando documento para visualizar
        </div>
        <iframe id="pdfPreview"></iframe>
    </div>
</div>

<script>
    let listo = <?= $esta_listo ?>;
    const rutaArchivo = "<?= $archivo_pre_cargado ?>";

    document.getElementById('inputPdf').onchange = function() {
        if(this.files[0]) document.getElementById('fileName').innerText = this.files[0].name;
    };

    document.getElementById('formSubida').onsubmit = async (e) => {
        e.preventDefault();
        const fileInput = document.getElementById('inputPdf');
        if(!fileInput.files[0]) { Swal.fire('Aviso', 'Por favor, selecciona un archivo PDF.', 'info'); return; }

        const formData = new FormData();
        formData.append('pdf_archivo', fileInput.files[0]);

        try {
            const response = await fetch('upload_handler.php', { method: 'POST', body: formData });
            const data = await response.json();

            if(data.success) {
                document.getElementById('nombre_temp').value = 'uploads/' + data.filename;
                listo = true;
                document.getElementById('msgNoPreview').style.display = 'none';
                const preview = document.getElementById('pdfPreview');
                preview.src = 'uploads/' + data.filename;
                preview.style.display = 'block';
                Swal.fire('¡Éxito!', 'Archivo cargado correctamente.', 'success');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
        }
    };

    window.onload = function() {
        if (rutaArchivo !== "") {
            const preview = document.getElementById('pdfPreview');
            const msg = document.getElementById('msgNoPreview');
            preview.src = rutaArchivo; 
            preview.style.display = 'block';
            msg.style.display = 'none';
        }
    };

    function intentarFirmar() {
        if(!listo) {
            Swal.fire('Atención', 'No hay ningún documento cargado para firmar.', 'warning');
        } else {
            Swal.fire({
                title: '¿Confirmar proceso?',
                text: "Se aplicará la firma electrónica al documento actual.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6c5ce7',
                confirmButtonText: 'Sí, firmar ahora',
                cancelButtonText: 'Revisar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formFirma').submit();
                }
            });
        }
    }
</script>
</body>
</html>
