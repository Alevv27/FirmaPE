<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit(); }
require_once '../config/conexion.php';

$id = $_GET['id'];
$msg = ""; $error = "";

// 1. Obtener datos actuales
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre']; 
    $dni = $_POST['dni']; 
    $correo = $_POST['correo']; 
    $rol = $_POST['rol']; 
    $new_p = $_POST['password'];
    $valid = true;

    if (!empty($new_p)) {
        if (strlen($new_p) < 8 || !preg_match('/[A-Za-z]/', $new_p) || !preg_match('/[0-9]/', $new_p)) {
            $error = "La nueva contraseña debe tener mín. 8 caracteres e incluir letras y números.";
            $valid = false;
        }
    }

    if ($valid) {
        $up = $conexion->prepare("UPDATE usuarios SET dni=?, correo=?, rol=?, nombre=? WHERE id=?");
        $up->bind_param("ssssi", $dni, $correo, $rol, $nombre, $id);
        
        if ($up->execute()) {
            $msg = "Datos actualizados correctamente.";
            $u['nombre'] = $nombre; 
            $u['dni'] = $dni;
            $u['correo'] = $correo;
            $u['rol'] = $rol;
        }

        if (!empty($new_p)) {
            $hash = password_hash($new_p, PASSWORD_DEFAULT);
            $up_p = $conexion->prepare("UPDATE usuarios SET password=? WHERE id=?");
            $up_p->bind_param("si", $hash, $id);
            $up_p->execute();
            $msg = "Datos y contraseña actualizados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85); 
            --glass-border: rgba(255, 255, 255, 0.5);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --accent: #6366f1;
        }

        body { 
            margin: 0; 
            font-family: 'Inter', sans-serif; 
            min-height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            color: var(--text-main);
        }

        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.6) 100%),
                url('../imagenes/fondopanel.png');
            background-size: cover;
            background-position: center;
            z-index: -1;
            opacity: 0.7;
        }

        .card { 
            background: var(--glass-bg); 
            width: 100%; 
            max-width: 420px; 
            padding: 40px; 
            border-radius: 28px; 
            border: 1px solid var(--glass-border); 
            backdrop-filter: blur(20px); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        h2 { text-align: center; font-size: 22px; font-weight: 800; margin-bottom: 25px; letter-spacing: -0.5px; }
        
        label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        input, select { 
            width: 100%; padding: 14px 16px; margin-bottom: 20px; 
            border: 1.5px solid rgba(0,0,0,0.08); border-radius: 12px; 
            box-sizing: border-box; font-size: 14px; background: rgba(255,255,255,0.6);
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus { outline: none; border-color: var(--accent); background: #fff; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .pass-wrapper { 
            position: relative; 
            margin-bottom: 20px; 
        }

        .pass-wrapper input {
            margin-bottom: 0 !important;
            padding-right: 80px;
        }

        .toggle-pass { 
            position: absolute; 
            right: 12px; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            font-size: 10px; 
            font-weight: 800; 
            color: var(--accent); 
            background: rgba(99, 102, 241, 0.1); 
            padding: 6px 10px; 
            border-radius: 8px; 
            user-select: none;
            transition: 0.2s;
        }
        
        .toggle-pass:hover { background: rgba(99, 102, 241, 0.2); }

        .btn-up { 
            width: 100%; padding: 15px; background: #1e293b; color: white; border: none; 
            border-radius: 14px; font-weight: 700; cursor: pointer; margin-top: 10px; transition: 0.3s;
        }
        .btn-up:hover { background: #000; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }

        .error-msg { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 12px; font-size: 12px; margin-bottom: 15px; border: 1px solid #fee2e2; }
        .success-msg { background: #dcfce7; color: #166534; padding: 12px; border-radius: 12px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 15px; border: 1px solid #bbf7d0; }
        
        .back-link { display: block; text-align: center; margin-top: 25px; font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 600; }
        .back-link:hover { color: var(--text-main); }
    </style>
</head>
<body>

<div class="bg-overlay"></div>

<div class="card">
    <h2>Editar Usuario</h2>
    
    <?php if($msg) echo "<div class='success-msg'>$msg</div>"; ?>
    <?php if($error) echo "<div class='error-msg'>$error</div>"; ?>
    
    <form method="POST">
        <label>Nombre Completo</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($u['nombre'] ?? '') ?>" placeholder="Ej. Juan Pérez" required>

        <label>DNI</label>
        <input type="text" name="dni" value="<?= $u['dni'] ?>" required maxlength="8">
        
        <label>Correo Electrónico</label>
        <input type="email" name="correo" value="<?= $u['correo'] ?>" required>

        <label>Rol de Usuario</label>
        <select name="rol">
            <option value="usuario" <?= $u['rol']=='usuario'?'selected':'' ?>>Usuario (Subir Documentos)</option>
            <option value="firmante" <?= $u['rol']=='firmante'?'selected':'' ?>>Firmante (Acceso Total)</option>
            <option value="admin" <?= $u['rol']=='admin'?'selected':'' ?>>Administrador (Gestión)</option>
        </select>

        <label style="color:#ef4444;">Seguridad (Opcional)</label>
        <div class="pass-wrapper">
            <input type="password" name="password" id="password_edit" placeholder="Nueva contraseña">
            <span class="toggle-pass" onclick="toggleView('password_edit')">VER</span>
        </div>
        
        <div id="s-container" style="display:none; margin-top: 10px; margin-bottom: 20px;">
            <div style="height: 5px; background: rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden;">
                <div id="s-bar" style="height: 100%; width: 0%; transition: 0.4s ease;"></div>
            </div>
            <p id="s-text" style="font-size: 11px; color: #71717a; margin: 8px 0 0 0;">Fortaleza: <span>Incompleto</span></p>
        </div>

        <button type="submit" class="btn-up">GUARDAR CAMBIOS</button>
    </form>
    
    <a href="admin_panel.php" class="back-link">← Cancelar y volver al panel</a>
</div>

<script>
    function toggleView(id) {
        const input = document.getElementById(id);
        const btn = event.target;
        if (input.type === "password") { 
            input.type = "text"; 
            btn.innerText = "OCULTAR"; 
        } else { 
            input.type = "password"; 
            btn.innerText = "VER"; 
        }
    }

    const pIn = document.getElementById('password_edit');
    const sC = document.getElementById('s-container');
    const sB = document.getElementById('s-bar');
    const sT = document.getElementById('s-text').querySelector('span');

    pIn.addEventListener('input', () => {
        let val = pIn.value;
        if(!val) { sC.style.display="none"; return; }
        sC.style.display="block";

        let hasL = /[A-Za-z]/.test(val);
        let hasN = /[0-9]/.test(val);
        let isL = val.length >= 8;

        if (isL && hasL && hasN) {
            update(100, 'Aceptable', '#10b981');
        } else {
            update(40, 'No cumple requisitos', '#ef4444');
        }
    });

    function update(w, t, c) { 
        sB.style.width = w+'%'; 
        sB.style.background = c; 
        sT.innerText = t; 
        sT.style.color = c; 
    }
</script>
</body>
</html>