<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/conexion.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $dni = trim($_POST['dni']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // VALIDACIONES (1 ERROR ACTIVO)
    if (!preg_match('/^[0-9]{8}$/', $dni)) {
        $error = "dni";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "email";

    } elseif ($password !== $confirm) {
        $error = "password";

    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W]).{8,}$/', $password)) {
        $error = "weak";

    } else {

        $check = $conexion->prepare("SELECT id FROM usuarios WHERE dni=? OR correo=?");
        $check->bind_param("ss", $dni, $correo);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "exists";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conexion->prepare("INSERT INTO usuarios (dni, correo, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $dni, $correo, $hash);

            if ($stmt->execute()) {
                header("Location: register.php?success=1");
                exit;
            } else {
                $error = "db";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Registro PRO</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">

<h2>Registro</h2>

<form method="POST">

<input name="dni" placeholder="DNI (8 dígitos)" required>
<input name="correo" placeholder="Correo" required>

<!-- PASSWORD -->
<div class="input-group">
<input type="password" name="password" id="password" placeholder="Contraseña" required>
<span class="eye" id="togglePass">👁</span>
</div>

<!-- CONFIRM -->
<div class="input-group">
<input type="password" name="confirm" id="confirm" placeholder="Confirmar contraseña" required>
<span class="eye" id="toggleConfirm">👁</span>
</div>

<!-- ERROR ÚNICO -->
<?php if (!empty($error)): ?>
<div class="alert-error show">

<?php
if ($error == "dni") echo "DNI debe tener 8 dígitos";
elseif ($error == "email") echo "Correo inválido";
elseif ($error == "password") echo "Las contraseñas no coinciden";
elseif ($error == "weak") echo "Contraseña insegura";
elseif ($error == "exists") echo "Usuario ya existe";
elseif ($error == "db") echo "Error al registrar";
?>

</div>
<?php endif; ?>

<!-- REQUISITOS -->
<div class="requisitos">
  <div><span id="min" class="circulo"></span> 8 caracteres</div>
  <div><span id="mayus" class="circulo"></span> Mayúscula</div>
  <div><span id="minus" class="circulo"></span> Minúscula</div>
  <div><span id="num" class="circulo"></span> Número</div>
  <div><span id="simbolo" class="circulo"></span> Símbolo</div>
</div>

<!-- BARRA -->
<div class="barra">
  <div id="fuerza"></div>
</div>

<p id="nivel"></p>

<button id="btnRegistro" type="submit">Registrar</button>

</form>

<!-- CHECK -->
<div id="overlayCheck">
  <div class="check">✔</div>
  <p>¡Registrado correctamente!</p>
</div>

</div>

<script>
const pass = document.getElementById("password");
const confirm = document.getElementById("confirm");
const btn = document.getElementById("btnRegistro");
const barra = document.getElementById("fuerza");
const nivel = document.getElementById("nivel");

// OJOS
document.getElementById("togglePass").onclick = () => {
  pass.type = pass.type === "password" ? "text" : "password";
};

document.getElementById("toggleConfirm").onclick = () => {
  confirm.type = confirm.type === "password" ? "text" : "password";
};

// VALIDACIÓN
function validar() {

  let val = pass.value;
  let score = 0;

  const min = document.getElementById("min");
  const mayus = document.getElementById("mayus");
  const minus = document.getElementById("minus");
  const num = document.getElementById("num");
  const simbolo = document.getElementById("simbolo");

  function check(cond, el){
    if(cond){
      el.classList.add("ok");
      score++;
    } else {
      el.classList.remove("ok");
    }
  }

  check(val.length >= 8, min);
  check(/[A-Z]/.test(val), mayus);
  check(/[a-z]/.test(val), minus);
  check(/[0-9]/.test(val), num);
  check(/[\W]/.test(val), simbolo);

  let porcentaje = (score / 5) * 100;
  barra.style.width = porcentaje + "%";

  if (score <= 2) {
    barra.style.background = "#ff4d4d";
    nivel.textContent = "Débil";
  } else if (score <= 4) {
    barra.style.background = "#ffa500";
    nivel.textContent = "Media";
  } else {
    barra.style.background = "#00cc66";
    nivel.textContent = "Muy segura";
  }
}

pass.addEventListener("keyup", validar);
confirm.addEventListener("keyup", validar);

// SOLO VALIDACIÓN VISUAL (NO BLOQUEA CLICK)
btn.addEventListener("click", function(e){

  if (pass.value !== confirm.value) {

    e.preventDefault();

    const alerta = document.querySelector(".alert-error");

    if (alerta) {
      alerta.innerHTML = "Las contraseñas no coinciden";
      alerta.classList.add("show");
    }

    btn.classList.add("shake");
    setTimeout(() => btn.classList.remove("shake"), 300);
  }
});
</script>

<?php if (isset($_GET['success'])): ?>
<script>
window.onload = () => {
  const overlay = document.getElementById("overlayCheck");
  overlay.classList.add("show");

  setTimeout(() => {
    overlay.classList.remove("show");
    window.history.replaceState({}, document.title, "register.php");
  }, 2500);
};
</script>
<?php endif; ?>

</body>
</html>