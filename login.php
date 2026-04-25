<?php
session_start();
include 'config/conexion.php';

$mensaje = "";
$success = false; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dni = trim($_POST['dni']);
    $pass = $_POST['password'];

    if (!preg_match('/^[0-9]{8}$/', $dni)) {
        $mensaje = "El DNI debe tener 8 dígitos";
    } else {
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE dni=?");
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res) {
            $mensaje = "El DNI no se encuentra registrado";
        } elseif (!password_verify($pass, $res['password'])) {
            $mensaje = "La contraseña es incorrecta";
        } else {
            $_SESSION['user'] = $dni;
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="icon" href="imagenes/favicon.png">
    <style>
        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input {
            width: 100%;
            padding-right: 60px; /* Más espacio para la palabra "OCULTAR" */
            box-sizing: border-box;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #4db8ff; /* Color temático */
            font-size: 11px; /* Tamaño tipo etiqueta */
            font-weight: bold;
            text-transform: uppercase;
            user-select: none;
            z-index: 10;
            letter-spacing: 1px;
        }

        .toggle-password:hover {
            color: #1a8cff;
        }

        /* --- OVERLAY DE BIENVENIDA --- */
        #overlayBienvenida {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.98);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            z-index: 1000;
            visibility: hidden; opacity: 0;
            transition: 0.5s ease-in-out;
        }
        #overlayBienvenida.show {
            visibility: visible; opacity: 1;
        }

        .welcome-logo-container {
            width: 150px; 
            margin-bottom: 20px;
            animation: popIn 0.6s cubic-bezier(0.17, 0.67, 0.83, 0.67);
        }

        .welcome-logo-container img {
            width: 100%;
            height: auto;
        }

        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        h2.welcome-text {
            color: #333;
            font-size: 28px;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        p.welcome-subtext {
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-logo">
        <img src="imagenes/firmape.png" alt="Logo Empresa">
    </div>

    <h2>Login</h2>

    <form method="POST">
        <input type="text" name="dni" placeholder="DNI" required maxlength="8">
        
        <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Contraseña" required>
            <span class="toggle-password" id="togglePass">Ver</span>
        </div>

        <button type="submit">Ingresar</button>
    </form>

    <?php if (!empty($mensaje)): ?>
        <div class="alert-error show">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <div class="links">
        <a href="register.php">Crear cuenta</a>
        <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
    </div>
</div>

<div id="overlayBienvenida">
    <div class="welcome-logo-container">
        <img src="imagenes/firmape.png" alt="Logo Empresa">
    </div>
    <h2 class="welcome-text">¡Bienvenido!</h2>
    <p class="welcome-subtext">Accediendo al panel de control...</p>
</div>

<script>
// Lógica para el botón VER/OCULTAR
const togglePass = document.getElementById('togglePass');
const passwordInput = document.getElementById('password');

togglePass.addEventListener('click', () => {
    const isPassword = passwordInput.getAttribute('type') === 'password';
    
    // Cambiar tipo de input
    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
    
    // Cambiar texto
    togglePass.textContent = isPassword ? 'Ocultar' : 'Ver';
});

<?php if ($success): ?>
window.onload = () => {
    const overlay = document.getElementById("overlayBienvenida");
    overlay.classList.add("show");

    setTimeout(() => {
        window.location.href = "principal.php";
    }, 2000);
};
<?php endif; ?>
</script>

</body>
</html>