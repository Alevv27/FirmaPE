<?php
ob_start();
session_start();

// Asegúrate de que estas rutas sean correctas
require __DIR__ . '/fpdf/fpdf.php';
require __DIR__ . '/fpdi/src/autoload.php';
require_once __DIR__ . '/includes/auth.php';
require_module('FIRMAR');

use setasign\Fpdi\Fpdi;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- LÓGICA PARA DETECTAR EL ORIGEN DEL PDF ---
    $pdfFile = "";
    $id_doc = isset($_POST['id_doc']) ? $_POST['id_doc'] : null;

    if (isset($_FILES['pdf']) && $_FILES['pdf']['size'] > 0) {
        $pdfFile = $_FILES['pdf']['tmp_name'];
    } elseif (isset($_POST['pdf_ruta_auto']) && !empty($_POST['pdf_ruta_auto'])) {
        $pdfFile = $_POST['pdf_ruta_auto'];
    }

    if (empty($pdfFile)) {
        die("Error: No se recibió ningún archivo PDF para procesar.");
    }

    $pX = floatval($_POST['pX']); 
    $pY = floatval($_POST['pY']); 
    $base64 = $_POST['firmaBase64'];

    if (empty($base64)) {
        die("Error: No se ha dibujado la firma.");
    }

    // 1. Guardar la firma temporalmente
    $imgData = str_replace(['data:image/png;base64,', ' '], ['', '+'], $base64);
    $imgFile = "temp_firma_" . time() . ".png";
    file_put_contents($imgFile, base64_decode($imgData));

    // 2. Iniciar FPDI
    $pdf = new Fpdi();
    
    try {
        $pageCount = $pdf->setSourceFile($pdfFile);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tpl = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tpl);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl);

            // 3. Estampar firma en la última página
            if ($i == $pageCount) {
                $realX = $pX * $size['width'];
                $realY = $pY * $size['height'];
                $pdf->Image($imgFile, $realX, $realY, 40);
            }
        }

        // 4. Salida del PDF
        ob_end_clean();
        // Borrar archivo temporal antes de la salida
        if (file_exists($imgFile)) unlink($imgFile);
        
        $pdf->Output("D", "documento_firmado_" . time() . ".pdf");

    } catch (Exception $e) {
        if (file_exists($imgFile)) unlink($imgFile);
        ob_end_clean();
        die("Error al procesar el PDF: " . $e->getMessage());
    }

    exit;
}
