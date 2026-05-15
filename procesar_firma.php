<?php
ob_start();
session_start();

require __DIR__ . '/tcpdf/tcpdf.php';
require __DIR__ . '/fpdi/src/autoload.php';
require_once __DIR__ . '/includes/auth.php';
require_module('FIRMAR');

use setasign\Fpdi\TcpdfFpdi;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Solicitud invalida.");
}

$pdfFile = "";
$id_doc = $_POST['id_doc'] ?? null;

if (isset($_FILES['pdf']) && $_FILES['pdf']['size'] > 0) {
    $pdfFile = $_FILES['pdf']['tmp_name'];
} elseif (!empty($_POST['pdf_ruta_auto'])) {
    $rutaAuto = str_replace('\\', '/', $_POST['pdf_ruta_auto']);
    if (strpos($rutaAuto, 'uploads/') === 0 || strpos($rutaAuto, 'documentos/') === 0) {
        $pdfFile = __DIR__ . '/' . $rutaAuto;
    } else {
        $pdfFile = $rutaAuto;
    }
}

if (empty($pdfFile) || !file_exists($pdfFile)) {
    die("Error: No se recibio ningun archivo PDF valido para procesar.");
}

$tipoFirma = $_POST['tipoFirma'] ?? 'normal';
$token = $_POST['token'] ?? '';
$pX = isset($_POST['pX']) ? floatval($_POST['pX']) : 0;
$pY = isset($_POST['pY']) ? floatval($_POST['pY']) : 0;
$pW = isset($_POST['pW']) ? floatval($_POST['pW']) : 0;
$paginaFirma = isset($_POST['paginaFirma']) ? (int) $_POST['paginaFirma'] : 0;
$base64 = $_POST['firmaBase64'] ?? '';
$imgFile = null;

if ($tipoFirma !== 'servidor') {
    if (empty($base64)) {
        die("Error: No se ha dibujado o subido la firma.");
    }

    $imgExt = str_starts_with($base64, 'data:image/jpeg') || str_starts_with($base64, 'data:image/jpg') ? 'jpg' : 'png';
    $imgData = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
    $imgData = str_replace(' ', '+', $imgData);
    $imgFile = __DIR__ . "/temp_firma_" . time() . "." . $imgExt;
    file_put_contents($imgFile, base64_decode($imgData));
}

$pdf = new TcpdfFpdi();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

try {
    $pageCount = $pdf->setSourceFile($pdfFile);
    if ($paginaFirma < 1 || $paginaFirma > $pageCount) {
        $paginaFirma = $pageCount;
    }

    for ($i = 1; $i <= $pageCount; $i++) {
        $tpl = $pdf->importPage($i);
        $size = $pdf->getTemplateSize($tpl);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tpl);

        if ($i !== $paginaFirma) {
            continue;
        }

        $realX = max(0, min($pX * $size['width'], $size['width']));
        $realY = max(0, min($pY * $size['height'], $size['height']));
        $realW = $pW > 0 ? ($pW * $size['width']) : 45;
        $realW = max(25, min($realW, $size['width'] * 0.75));

        if ($tipoFirma === 'servidor') {
            $realH = max(28, $realW * 0.42);
            if ($realX + $realW > $size['width']) {
                $realX = $size['width'] - $realW;
            }
            if ($realY + $realH > $size['height']) {
                $realY = $size['height'] - $realH;
            }

            $usuario = current_user() ?: [];
            $nombre = strtoupper($usuario['nombre'] ?? 'USUARIO DEL SISTEMA');
            $email = $usuario['email'] ?? '';

            $pdf->SetFillColor(241, 245, 249);
            $pdf->SetDrawColor(108, 92, 231);
            $pdf->SetLineWidth(0.6);
            $pdf->Rect($realX, $realY, $realW, $realH, 'DF');

            $pdf->SetTextColor(108, 92, 231);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($realX, $realY + 3);
            $pdf->Cell($realW, 4, 'FIRMADO ELECTRONICAMENTE', 0, 1, 'C');

            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetXY($realX + 4, $realY + 10);
            $txt = "FIRMANTE: " . $nombre . "\n" .
                "EMAIL: " . $email . "\n" .
                "FECHA: " . date('d/m/Y H:i:s') . "\n" .
                "MOTIVO: Aprobacion de documento\n" .
                "UBICACION: LIMA, PERU";
            $pdf->MultiCell($realW - 8, 3.5, $txt, 0, 'L');
        } else {
            if ($realX + $realW > $size['width']) {
                $realX = $size['width'] - $realW;
            }
            $pdf->Image($imgFile, $realX, $realY, $realW);
        }
    }

    if (ob_get_contents()) {
        ob_end_clean();
    }

    if ($imgFile && file_exists($imgFile)) {
        unlink($imgFile);
    }

    if (!empty($id_doc) && !empty($token)) {
        $cierreResponse = api_request('PATCH', '/procesos/' . (int) $id_doc . '/firmado', [
            'token' => $token,
            'tipo_firma' => $tipoFirma,
        ]);

        if (!$cierreResponse['ok']) {
            die("El PDF fue generado, pero no se pudo cerrar el proceso: " . ($cierreResponse['error'] ?? 'Error desconocido'));
        }
    }

    $pdf->Output("documento_firmado_" . time() . ".pdf", "D");
    exit;
} catch (Exception $e) {
    if ($imgFile && file_exists($imgFile)) {
        unlink($imgFile);
    }
    if (ob_get_contents()) {
        ob_end_clean();
    }
    die("Error al procesar el PDF: " . $e->getMessage());
}
