<?php
session_start();
require_once 'includes/auth.php';
require_module('FIRMAR');

// Ruta base dinámica (funciona en local y Render)
$base_path = __DIR__ . "/";

// 1. Cargar TCPDF (CORRECTO)
if (file_exists($base_path . "tcpdf/tcpdf.php")) {
    require_once($base_path . "tcpdf/tcpdf.php");
} else {
    die("ERROR CRÍTICO: No se encuentra tcpdf.php en: " . $base_path . "tcpdf/tcpdf.php");
}

// 2. Cargar FPDI
if (file_exists($base_path . "fpdi/src/autoload.php")) {
    require_once($base_path . "fpdi/src/autoload.php");
} else {
    die("ERROR CRÍTICO: No se encuentra fpdi/src/autoload.php");
}

use setasign\Fpdi\TcpdfFpdi;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['nombre_temp'])) {
    
    $id_doc = $_POST['id_doc'] ?? null;
    $ruta_recibida = $_POST['nombre_temp'];

    // Limpiar ruta
    $ruta_recibida = str_replace('\\', '/', $ruta_recibida);

    // 3. Ruta real del archivo (CORREGIDO)
    if (strpos($ruta_recibida, 'documentos/') !== false || strpos($ruta_recibida, 'uploads/') !== false) {
        $archivo_final = $base_path . $ruta_recibida;
    } else {
        $archivo_final = $base_path . 'uploads/' . basename($ruta_recibida);
    }

    if (!file_exists($archivo_final)) {
        die("Error: El archivo PDF no existe en: " . $archivo_final);
    }

    // 4. Obtener datos del usuario desde la sesion del backend Flask
    $nombre_firmante = strtoupper(current_user()['nombre'] ?? 'USUARIO DEL SISTEMA');

    // 5. PROCESO DE FIRMA
    try {
        $pdf = new TcpdfFpdi();

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pages = $pdf->setSourceFile($archivo_final);

        for ($i = 1; $i <= $pages; $i++) {
            $tpl = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tpl);

            if ($i == $pages) {
                $w = 75; $h = 35;
                $x = 125; $y = 245;

                $pdf->SetFillColor(241, 245, 249);
                $pdf->SetDrawColor(108, 92, 231);
                $pdf->SetLineWidth(0.5);
                $pdf->RoundedRect($x, $y, $w, $h, 2, '1111', 'DF');

                $pdf->SetTextColor(108, 92, 231);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetXY($x, $y + 3);
                $pdf->Cell($w, 5, 'FIRMADO ELECTRONICAMENTE', 0, 1, 'C');

                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->SetXY($x + 4, $y + 11);

                $txt = "FIRMANTE: " . $nombre_firmante . "\n" .
                       "EMAIL: " . (current_user()['email'] ?? '') . "\n" .
                       "FECHA: " . date('d/m/Y H:i:s') . "\n" .
                       "MOTIVO: Aprobación de documento\n" .
                       "UBICACIÓN: LIMA, PERÚ";

                $pdf->MultiCell($w - 8, 4, $txt, 0, 'L');
            }
        }

        // 6. Actualizar estado en BD
        if ($id_doc && isset($conexion)) {
            $stmt_upd = $conexion->prepare("UPDATE documentos SET estado = 'Firmado' WHERE id = ?");
            $stmt_upd->bind_param("i", $id_doc);
            $stmt_upd->execute();
        }

        // 7. Limpiar buffer y descargar
        if (ob_get_contents()) ob_end_clean();

        $pdf->Output('DOCUMENTO_FIRMADO_' . date('His') . '.pdf', 'D');
        exit();

    } catch (Exception $e) {
        die("Error procesando el PDF: " . $e->getMessage());
    }

} else {
    die("Solicitud inválida.");
}
