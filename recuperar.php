<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/conexion.php';
include 'includes/mail.php';

$error = "";
$success = false;

$dni = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $dni = trim($_POST['dni']);
    $codigo = rand(100000,999999);

    // 🔴 VALIDAR DNI
    if (!preg_match('/^\d{8}$/', $dni)) {
        $error = "El DNI debe tener 8 dígitos";

    } else {

        $stmt = $conexion->prepare("SELECT correo FROM usuarios WHERE dni=?");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {

            $row = $res->fetch_assoc();
            $correo = $row['correo'];

            // guardar código
            $stmt = $conexion->prepare("UPDATE usuarios SET codigo=? WHERE dni=?");
            $stmt->bind_param("ss", $codigo, $dni);
            $stmt->execute();

            if (enviarCodigo($correo, $codigo)) {
                $success = true;
                $dni = "";
            } else {
                $error = "Error al enviar correo";
            }

        } else {
            $error = "DNI no registrado";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Recuperar contraseña</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">

<h2>Recuperar contraseña</h2>

<form method="POST">

<input name="dni" placeholder="DNI (8 dígitos)" required value="<?= $dni ?>">

<!-- 🔴 ERROR -->
<?php if (!empty($error)): ?>
<div class="alert-error show">
    <?= $error ?>
</div>
<?php endif; ?>

<button type="submit">Enviar código</button>

</form>

<form action="cambiar.php" method="GET">
    <button type="submit" class="btn-secundario">Ya tengo código</button>
</form>

<div class="links" style="margin-top: 15px; text-align: center;">
    <a href="login.php">← Regresar</a>

</div>

<!-- ✅ OVERLAY ÉXITO -->
<div id="overlayCheck">
  <div class="check">✔</div>
  <p>Código enviado al correo</p>
</div>

<script>

// ✅ Éxito estilo pantalla completa
<?php if ($success): ?>
window.onload = () => {
    const overlay = document.getElementById("overlayCheck");
    overlay.classList.add("show");

    setTimeout(() => {
        overlay.classList.remove("show");
        window.location.href = "cambiar.php"; // 🔥 redirige automático
    }, 2500);
};
<?php endif; ?>

</script>

</body>
</html>