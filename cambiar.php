<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/conexion.php';

$error = "";
$success = false;

$dni = "";
$codigo = "";
$nueva = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $dni = $_POST['dni'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $nueva = $_POST['nueva'] ?? '';

    // 🔴 DNI
    if (!preg_match('/^\d{8}$/', $dni)) {
        $error = "El DNI debe tener 8 dígitos";

    } elseif ($codigo == "" || $nueva == "") {
        $error = "Completa todos los campos";

    } else {

        // 🔴 VALIDACIÓN CONTRASEÑA
        $faltantes = [];

        if (!preg_match('/[A-Z]/', $nueva)) $faltantes[] = "una MAYÚSCULA";
        if (!preg_match('/[a-z]/', $nueva)) $faltantes[] = "una minúscula";
        if (!preg_match('/[0-9]/', $nueva)) $faltantes[] = "un número";
        if (!preg_match('/[\W]/', $nueva)) $faltantes[] = "un símbolo";
        if (strlen($nueva) < 8) $faltantes[] = "mínimo 8 caracteres";

        if (!empty($faltantes)) {
            $error = "<div style='text-align:center'>
            <b>La contraseña necesita:</b><br>
            • " . implode("<br>• ", $faltantes) . "
            </div>";

        } else {

            // 🔴 VALIDAR CÓDIGO EN BD
            $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE dni=? AND codigo=?");
            $stmt->bind_param("ss", $dni, $codigo);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {

                $hash = password_hash($nueva, PASSWORD_DEFAULT);

                $up = $conexion->prepare("UPDATE usuarios SET password=?, codigo=NULL WHERE dni=?");
                $up->bind_param("ss", $hash, $dni);
                $up->execute();

                $success = true;

                $dni = "";
                $codigo = "";
                $nueva = "";

            } else {
                $error = "Código incorrecto o no coincide con el DNI";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar contraseña</title>
<link rel="stylesheet" href="css/estilos.css">
<style>
    /* Estilo para que el texto Ver/Ocultar se vea bien */
    .eye {
        font-size: 11px !important;
        font-weight: bold;
        text-transform: uppercase;
        color: #4db8ff;
        cursor: pointer;
        user-select: none;
        width: 50px;
        text-align: right;
    }
</style>
</head>
<body>

<div class="container">

<h2>Cambiar contraseña</h2>

<form method="POST">

<input name="dni" placeholder="DNI (8 dígitos)" required value="<?= htmlspecialchars($dni) ?>">
<input name="codigo" placeholder="Código enviado al correo" required value="<?= htmlspecialchars($codigo) ?>">

<div class="input-group">
    <input type="password" name="nueva" id="password" placeholder="Nueva contraseña" required>
    <span class="eye" id="togglePass">Ver</span>
</div>

<?php if (!empty($error)): ?>
<div class="alert-error show">
    <?= $error ?>
</div>
<?php endif; ?>

<button type="submit">Cambiar Contraseña</button>

</form>

<div class="links" style="margin-top: 15px; text-align: center;">
    <a href="recuperar.php">← Regresar</a>
</div>

</div>

<div id="overlayCheck">
  <div class="check">✔</div>
  <p>Contraseña cambiada correctamente</p>
  <p style="font-size: 14px; opacity: 0.8;">Redirigiendo al login...</p>
</div>

<script>
// 👁 Lógica Ver/Ocultar Texto
document.getElementById("togglePass").onclick = function() {
    const p = document.getElementById("password");
    if (p.type === "password") {
        p.type = "text";
        this.textContent = "Ocultar";
    } else {
        p.type = "password";
        this.textContent = "Ver";
    }
};

// ✔ éxito y redirección al login
<?php if ($success): ?>
window.onload = () => {
    const overlay = document.getElementById("overlayCheck");
    overlay.classList.add("show");

    setTimeout(() => {
        window.location.href = "login.php";
    }, 2500);
};
<?php endif; ?>
</script>

</body>
</html>