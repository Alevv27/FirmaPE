<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/auth.php';

$error = '';
$perfilesResponse = api_request('GET', '/perfiles');
$empresasResponse = api_request('GET', '/empresas');
$perfiles = $perfilesResponse['ok'] ? ($perfilesResponse['data']['perfiles'] ?? []) : [];
$empresas = $empresasResponse['ok'] ? ($empresasResponse['data']['empresas'] ?? []) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $perfilId = (int) ($_POST['perfil_id'] ?? 0);
    $empresaId = (int) ($_POST['empresa_id'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo invalido.';
    } elseif ($password !== $confirm) {
        $error = 'Las contrasenas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contrasena debe tener al menos 6 caracteres.';
    } elseif ($perfilId <= 0 || $empresaId <= 0) {
        $error = 'Selecciona perfil y empresa.';
    } else {
        $response = api_request('POST', '/usuarios', [
            'nombre' => $nombre,
            'email' => $email,
            'password' => $password,
            'perfil_id' => $perfilId,
            'empresa_id' => $empresaId,
        ]);

        if ($response['ok']) {
            header('Location: register.php?success=1');
            exit;
        }

        $error = $response['error'] ?: 'No se pudo registrar el usuario.';
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
            font-size: 11px !important; font-weight: bold; text-transform: uppercase;
            color: #4db8ff; cursor: pointer; user-select: none; width: 60px; text-align: right;
        }
        select {
            width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd;
            border-radius: 8px; box-sizing: border-box;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Crear Cuenta</h2>

    <form method="POST">
        <input name="nombre" placeholder="Nombre completo" required>
        <input type="email" name="email" placeholder="Correo electronico" required>

        <select name="perfil_id" required>
            <option value="">Perfil</option>
            <?php foreach ($perfiles as $perfil): ?>
                <option value="<?= (int) $perfil['id'] ?>"><?= e($perfil['codigo']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="empresa_id" required>
            <option value="">Empresa</option>
            <?php foreach ($empresas as $empresa): ?>
                <option value="<?= (int) $empresa['id'] ?>"><?= e($empresa['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="input-group">
            <input type="password" name="password" id="password" placeholder="Contrasena" required>
            <span class="eye" id="togglePass">Ver</span>
        </div>

        <div class="input-group">
            <input type="password" name="confirm" id="confirm" placeholder="Confirmar contrasena" required>
            <span class="eye" id="toggleConfirm">Ver</span>
        </div>

        <?php if ($error): ?>
            <div class="alert-error show"><?= e($error) ?></div>
        <?php endif; ?>

        <button id="btnRegistro" type="submit">Registrar</button>
    </form>

    <div class="links" style="margin-top: 15px; text-align: center;">
        <a href="index.php">Volver al Login</a>
    </div>

    <div id="overlayCheck">
        <div class="check">OK</div>
        <p>Registrado correctamente.</p>
        <p style="font-size: 14px; opacity: 0.8;">Redirigiendo al login...</p>
    </div>
</div>

<script>
function bindToggle(toggleId, inputId) {
    document.getElementById(toggleId).onclick = function() {
        const input = document.getElementById(inputId);
        input.type = input.type === "password" ? "text" : "password";
        this.textContent = input.type === "password" ? "Ver" : "Ocultar";
    };
}
bindToggle("togglePass", "password");
bindToggle("toggleConfirm", "confirm");

document.getElementById("btnRegistro").addEventListener("click", function(e){
    const pass = document.getElementById("password");
    const confirm = document.getElementById("confirm");
    if (pass.value !== confirm.value && pass.value !== "") {
        e.preventDefault();
        alert("Las contrasenas no coinciden.");
    }
});
</script>

<?php if (isset($_GET['success'])): ?>
<script>
window.onload = () => {
    const overlay = document.getElementById("overlayCheck");
    overlay.classList.add("show");
    setTimeout(() => { window.location.href = "index.php"; }, 1600);
};
</script>
<?php endif; ?>

</body>
</html>
