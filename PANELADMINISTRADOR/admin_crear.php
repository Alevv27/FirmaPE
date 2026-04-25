<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: admin_login.php"); exit(); }
require_once '../config/conexion.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $correo = trim($_POST['correo']);
    $rol = $_POST['rol'];
    $password = $_POST['password'];

    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "La contraseña debe tener al menos 8 caracteres e incluir letras y números.";
    } else {
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE dni = ?");
        $check->bind_param("s", $dni);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "El DNI ya existe.";
        } else {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, dni, correo, password, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $dni, $correo, $pass_hash, $rol);
            
            if ($stmt->execute()) { 
                header("Location: admin_panel.php?res=creado"); 
                exit(); 
            } else { $error = "Error al guardar."; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .bg-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.6)), url('../imagenes/fondopanel.png'); background-size: cover; z-index: -1; }
        .card { background: rgba(255,255,255,0.85); width: 100%; max-width: 400px; padding: 40px; border-radius: 28px; border: 1px solid #fff; backdrop-filter: blur(20px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        h2 { text-align: center; font-weight: 800; margin-bottom: 25px; }
        label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; }
        input, select { width: 100%; padding: 14px; margin-bottom: 20px; border: 1.5px solid rgba(0,0,0,0.08); border-radius: 12px; box-sizing: border-box; }
        .btn-add { width: 100%; padding: 15px; background: #000; color: white; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 10px; font-size: 12px; margin-bottom: 15px; text-align: center; }
        .back { display: block; text-align: center; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <div class="card">
        <h2>Nuevo Usuario</h2>
        <?php if($error) echo "<div class='error-msg'>$error</div>"; ?>
        <form method="POST">
            <label>Nombre</label><input type="text" name="nombre" required>
            <label>DNI</label><input type="text" name="dni" maxlength="8" required>
            <label>Correo</label><input type="email" name="correo" required>
            <label>Contraseña</label><input type="password" name="password" required>
            <label>Rol</label>
            <select name="rol">
                <option value="usuario">Usuario Estándar</option>
                <option value="admin">Administrador</option>
                <option value="firmante">Firmante</option>
            </select>
            <button type="submit" class="btn-add">CREAR USUARIO</button>
        </form>
        <a href="admin_panel.php" class="back">← Volver al panel</a>
    </div>
</body>
</html>