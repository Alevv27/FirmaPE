<?php
session_start();
include 'config/conexion.php'; 

/**
 * CONFIGURACIÓN DE RUTAS ABSOLUTAS
 * Ajustado a tu carpeta FIRMAK y tu carpeta de librería tcdpf
 */
$base_path = "C:/xampp/htdocs/FIRMAK/";

// 1. Cargar TCPDF (usando tu nombre de carpeta tcdpf)
if (file_exists($base_path . "tcdpf/tcpdf.php")) {
    require_once($base_path . "tcdpf/tcpdf.php");
} else {
    die("ERROR CRÍTICO: No se encuentra tcpdf.php en: " . $base_path . "tcdpf/tcpdf.php. Revisa el nombre de la carpeta.");
}

// 2. Cargar FPDI
if (file_exists($base_path . "fpdi/src/autoload.php")) {
    require_once($base_path . "fpdi/src/autoload.php");
} else {
    die("ERROR CRÍTICO: No se encuentra fpdi/src/autoload.php");
}

use setasign\Fpdi\TcpdfFpdi;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['nombre_temp'])) {
    
    $dni_usuario = $_SESSION['user'] ?? '00000000';
    $id_doc = $_POST['id_doc'] ?? null;
    $ruta_recibida = $_POST['nombre_temp'];

    // 3. DETERMINAR LA RUTA REAL DEL ARCHIVO (Limpieza de slashes)
    $ruta_recibida = str_replace('\\', '/', $ruta_recibida);
    
    // Si la ruta ya incluye carpetas, la usamos, si no, asumimos uploads/
    if (strpos($ruta_recibida, 'documentos/') !== false || strpos($ruta_recibida, 'uploads/') !== false) {
        $archivo_final = $base_path . $ruta_recibida;
    } else {
        $archivo_final = $base_path . 'uploads/' . $ruta_recibida;
    }

    if (!file_exists($archivo_final)) {
        die("Error: El archivo PDF no existe en: " . $archivo_final);
    }

    // 4. OBTENER DATOS REALES DEL USUARIO DE LA DB
    $nombre_firmante = "USUARIO DEL SISTEMA";
    if (isset($conexion)) {
        $stmt = $conexion->prepare("SELECT nombre FROM usuarios WHERE dni = ?");
        $stmt->bind_param("s", $dni_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($user = $res->fetch_assoc()) {
            $nombre_firmante = strtoupper($user['nombre']);
        }
    }

    // 5. PROCESO DE FIRMA PDF
    try {
        $pdf = new TcpdfFpdi();
        
        // Quitar cabeceras y pies de página por defecto de TCPDF
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pages = $pdf->setSourceFile($archivo_final);

        for ($i = 1; $i <= $pages; $i++) {
            $tpl = $pdf->importPage($i);
            $pdf->AddPage();
            $pdf->useTemplate($tpl);

            // SOLO PONER EL SELLO EN LA ÚLTIMA PÁGINA
            if ($i == $pages) {
                // Coordenadas esquina inferior derecha
                $w = 75; $h = 35;
                $x = 125; $y = 245; 

                // Dibujar Rectángulo del Sello
                $pdf->SetFillColor(241, 245, 249); // Fondo gris muy claro
                $pdf->SetDrawColor(108, 92, 231);  // Borde Morado Digital
                $pdf->SetLineWidth(0.5);
                $pdf->RoundedRect($x, $y, $w, $h, 2, '1111', 'DF');

                // Título del Sello
                $pdf->SetTextColor(108, 92, 231);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetXY($x, $y + 3);
                $pdf->Cell($w, 5, 'FIRMADO ELECTRONICAMENTE', 0, 1, 'C');

                // Datos del Firmante
                $pdf->SetTextColor(30, 41, 59);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->SetXY($x + 4, $y + 11);
                
                $txt = "FIRMANTE: " . $nombre_firmante . "\n" .
                       "DNI: " . $dni_usuario . "\n" .
                       "FECHA: " . date('d/m/Y H:i:s') . "\n" .
                       "MOTIVO: Aprobación de documento\n" .
                       "UBICACIÓN: LIMA, PERÚ";

                $pdf->MultiCell($w - 8, 4, $txt, 0, 'L');
            }
        }

        // 6. ACTUALIZAR ESTADO EN LA BASE DE DATOS
        if ($id_doc && isset($conexion)) {
            $stmt_upd = $conexion->prepare("UPDATE documentos SET estado = 'Firmado' WHERE id = ?");
            $stmt_upd->bind_param("i", $id_doc);
            $stmt_upd->execute();
        }

        // 7. LIMPIAR BUFFER Y DESCARGAR
        if (ob_get_contents()) ob_end_clean();
        
        $pdf->Output('DOCUMENTO_FIRMADO_' . date('His') . '.pdf', 'D');
        exit();

    } catch (Exception $e) {
        die("Error procesando el PDF: " . $e->getMessage());
    }
} else {
    die("Solicitud inválida.");
}