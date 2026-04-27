<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/conexion.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']); // NUEVO: Captura de nombre
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // VALIDACIONES
    if (!preg_match('/^[0-9]{8}$/', $dni)) {
        $error = "dni";
    } elseif (empty($nombre)) { // NUEVO: Validación de nombre vacío
        $error = "nombre_vacio";
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
            // MODIFICADO: Se agrega el campo nombre al INSERT
            $stmt = $conexion->prepare("INSERT INTO usuarios (dni, nombre, correo, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $dni, $nombre, $correo, $hash);

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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
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
    <h2>Crear Cuenta</h2>

    <form method="POST">
        <input name="dni" placeholder="DNI (8 dígitos)" required maxlength="8">
        <input name="nombre" placeholder="Nombre completo" required> 
        <input name="correo" placeholder="Correo electrónico" required>

        <div class="input-group">
            <input type="password" name="password" id="password" placeholder="Contraseña" required>
            <span class="eye" id="togglePass">Ver</span>
        </div>

        <div class="input-group">
            <input type="password" name="confirm" id="confirm" placeholder="Confirmar contraseña" required>
            <span class="eye" id="toggleConfirm">Ver</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error show">
                <?php
                if ($error == "dni") echo "DNI debe tener 8 dígitos";
                elseif ($error == "nombre_vacio") echo "El nombre es obligatorio"; // NUEVO
                elseif ($error == "email") echo "Correo inválido";
                elseif ($error == "password") echo "Las contraseñas no coinciden";
                elseif ($error == "weak") echo "Contraseña insegura";
                elseif ($error == "exists") echo "El DNI o Correo ya están registrados";
                elseif ($error == "db") echo "Error técnico en la base de datos";
                ?>
            </div>
        <?php endif; ?>

        <div class="requisitos">
            <div><span id="min" class="circulo"></span> 8 caracteres</div>
            <div><span id="mayus" class="circulo"></span> Mayúscula</div>
            <div><span id="minus" class="circulo"></span> Minúscula</div>
            <div><span id="num" class="circulo"></span> Número</div>
            <div><span id="simbolo" class="circulo"></span> Símbolo</div>
        </div>

        <div class="barra">
            <div id="fuerza"></div>
        </div>
        <p id="nivel"></p>

        <button id="btnRegistro" type="submit">Registrar</button>
    </form>

    <div class="links" style="margin-top: 15px; text-align: center;">
        <a href="index.php">← Volver al Login</a>
    </div>

    <div id="overlayCheck">
        <div class="check">✔</div>
        <p>¡Registrado correctamente!</p>
        <p style="font-size: 14px; opacity: 0.8;">Redirigiendo al login...</p>
    </div>
</div>

<script>
const pass = document.getElementById("password");
const confirm = document.getElementById("confirm");
const btn = document.getElementById("btnRegistro");
const barra = document.getElementById("fuerza");
const nivel = document.getElementById("nivel");

document.getElementById("togglePass").onclick = function() {
    if (pass.type === "password") {
        pass.type = "text";
        this.textContent = "Ocultar";
    } else {
        pass.type = "password";
        this.textContent = "Ver";
    }
};

document.getElementById("toggleConfirm").onclick = function() {
    if (confirm.type === "password") {
        confirm.type = "text";
        this.textContent = "Ocultar";
    } else {
        confirm.type = "password";
        this.textContent = "Ver";
    }
};

function validar() {
    let val = pass.value;
    let score = 0;
    const min = document.getElementById("min");
    const mayus = document.getElementById("mayus");
    const minus = document.getElementById("minus");
    const num = document.getElementById("num");
    const simbolo = document.getElementById("simbolo");

    function check(cond, el){
        if(cond){ el.classList.add("ok"); score++; } 
        else { el.classList.remove("ok"); }
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

btn.addEventListener("click", function(e){
    if (pass.value !== confirm.value && pass.value !== "") {
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
        window.location.href = "index.php";
    }, 2500);
};
</script>
<?php endif; ?>

</body>
</html>