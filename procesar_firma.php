<?php
session_start();
require_once 'includes/auth.php';

require_module('FIRMAR');

ob_start();

// Asegúrate de que estas rutas sean correctas
require __DIR__ . '/fpdf/fpdf.php';
require __DIR__ . '/fpdi/src/autoload.php';

use setasign\Fpdi\Fpdi;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdfFile = $_FILES['pdf']['tmp_name'];
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
    $pageCount = $pdf->setSourceFile($pdfFile);

    for ($i = 1; $i <= $pageCount; $i++) {
        $tpl = $pdf->importPage($i);
        $size = $pdf->getTemplateSize($tpl);

        // Crear página con las dimensiones reales del PDF original
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tpl);

        // 3. Estampar firma en la última página
        if ($i == $pageCount) {
            // Calculamos la posición real basada en el porcentaje enviado
            $realX = $pX * $size['width'];
            $realY = $pY * $size['height'];

            // Ajustamos el tamaño de la firma en el PDF (40 unidades de ancho)
            // FPDF mantiene la proporción del alto automáticamente
            $pdf->Image($imgFile, $realX, $realY, 40);
        }
    }

    // 4. Salida del PDF
    ob_end_clean();
    $pdf->Output("I", "documento_firmado.pdf");

    // Borrar archivo temporal
    if (file_exists($imgFile)) unlink($imgFile);
    exit;
}
