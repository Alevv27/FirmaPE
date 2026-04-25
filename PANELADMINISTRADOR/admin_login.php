<?php
session_start();
require_once '../config/conexion.php'; 

$mensaje = "";
$success = false; 

$frases = [
    "El control total en tus manos.",
    "Bienvenido al centro de mando.",
    "Seguridad y eficiencia en un solo lugar.",
    "Gestiona con precisión, lidera con éxito."
];
$frase_dia = $frases[array_rand($frases)];

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
            if (trim($res['rol']) === 'admin') {
                $_SESSION['admin_id'] = $res['id'];
                $_SESSION['user'] = $dni;
                $success = true;
            } else {
                $mensaje = "Acceso denegado: No eres administrador.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --card-bg: rgba(255, 255, 255, 0.95); /* Fondo blanco con ligera transparencia */
            --text-main: #1e293b;
            --text-muted: #64748b;
            --accent: #6366f1;
            --border: #e2e8f0;
        }

        body { 
            margin: 0; 
            font-family: 'Inter', sans-serif; 
            /* CONFIGURACIÓN DE LA IMAGEN DE FONDO */
            background-image: url('../imagenes/fondopanel.png'); 
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat;
            background-attachment: fixed;
            /* ------------------------------------- */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            color: var(--text-main);
        }

        /* Capa oscura opcional sobre la imagen para que el cuadro resalte más */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.3); /* Oscurece el fondo un 30% */
            z-index: -1;
        }

        .container { 
            background: var(--card-bg); 
            padding: 45px; 
            border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            width: 100%; 
            max-width: 380px; 
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px); /* Efecto de desenfoque detrás del cuadro */
            position: relative;
        }

        .header-area h2 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
        .header-area p { color: var(--text-muted); font-size: 14px; margin-top: 8px; margin-bottom: 30px; }

        .input-group { text-align: left; margin-bottom: 20px; position: relative; }
        label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }

        input { 
            width: 100%; padding: 14px 16px; border: 1.5px solid var(--border); border-radius: 12px; 
            box-sizing: border-box; font-size: 15px; transition: all 0.2s; background: #fff;
        }

        input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .password-wrapper { position: relative; }
        .toggle-btn { 
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%); 
            cursor: pointer; font-size: 11px; font-weight: 700; color: var(--accent); 
            text-transform: uppercase; user-select: none;
        }

        button { 
            width: 100%; padding: 15px; background: #1f2937; color: white; border: none; 
            border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 16px; 
            transition: all 0.3s; margin-top: 10px;
        }
        button:hover { background: #111827; transform: scale(1.02); }

        .alert-error { 
            background: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 10px; 
            margin-top: 20px; font-size: 13px; border: 1px solid #fee2e2;
        }

        #overlayBienvenida { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: #fff; display: flex; flex-direction: column; 
            justify-content: center; align-items: center; z-index: 1000; 
            visibility: hidden; opacity: 0; transition: 0.6s; 
        }
        #overlayBienvenida.show { visibility: visible; opacity: 1; }
        
        .loader { width: 48px; height: 48px; border: 5px solid #f3f3f3; border-top: 5px solid #1f2937; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <h2>Panel de Control</h2>
        <p><?= $frase_dia ?></p>
    </div>

    <form method="POST">
        <div class="input-group">
            <label>Identificación</label>
            <input type="text" name="dni" placeholder="Ingresa tu DNI" required maxlength="8">
        </div>

        <div class="input-group">
            <label>Contraseña</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="••••••••" required>
                <span class="toggle-btn" id="btnToggle">Ver</span>
            </div>
        </div>

        <button type="submit">Iniciar Sesión</button>
    </form>

    <?php if (!empty($mensaje)): ?>
        <div class="alert-error"><?= $mensaje ?></div>
    <?php endif; ?>
</div>

<div id="overlayBienvenida">
    <div class="loader"></div>
    <h2 style="font-size: 24px;">Autenticación exitosa</h2>
    <p>Cargando panel administrativo...</p>
</div>

<script>
    const btnToggle = document.getElementById('btnToggle');
    const inputPass = document.getElementById('password');

    btnToggle.addEventListener('click', () => {
        const type = inputPass.getAttribute('type') === 'password' ? 'text' : 'password';
        inputPass.setAttribute('type', type);
        btnToggle.textContent = type === 'password' ? 'Ver' : 'Ocultar';
    });

    <?php if ($success): ?>
    window.onload = () => {
        document.getElementById("overlayBienvenida").classList.add("show");
        setTimeout(() => { window.location.href = "admin_panel.php"; }, 1800);
    };
    <?php endif; ?>
</script>

</body>
</html>