<?php
header('Content-Type: application/json');

$folder = "uploads/";
if (!file_exists($folder)) {
    mkdir($folder, 0777, true);
}

if (isset($_FILES['pdf_archivo'])) {
    $cleanName = preg_replace("/[^a-zA-Z0-9\._]/", "_", $_FILES['pdf_archivo']['name']);
    $fileName = time() . "_" . $cleanName;
    $target = $folder . $fileName;

    if (move_uploaded_file($_FILES['pdf_archivo']['tmp_name'], $target)) {
        echo json_encode(['success' => true, 'filename' => $fileName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo en uploads/']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo.']);
}
?>