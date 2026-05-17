<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'includes/auth.php';

$error = '';
$info = '';
$step = $_SESSION['registro_pendiente'] ?? null;
$perfilesResponse = api_request('GET', '/perfiles');
$empresasResponse = api_request('GET', '/empresas');
$perfiles = $perfilesResponse['ok'] ? ($perfilesResponse['data']['perfiles'] ?? []) : [];
$empresas = $empresasResponse['ok'] ? ($empresasResponse['data']['empresas'] ?? []) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'verificar') {
    $codigoIngresado = trim($_POST['codigo'] ?? '');
    $pendiente = $_SESSION['registro_pendiente'] ?? null;

    if (!$pendiente) {
        $error = 'No hay registro pendiente. Vuelve a completar el formulario.';
    } elseif (time() > (int) ($pendiente['expira'] ?? 0)) {
        unset($_SESSION['registro_pendiente']);
        $step = null;
        $error = 'El codigo expiro. Solicita uno nuevo.';
    } elseif ($codigoIngresado !== (string) ($pendiente['codigo'] ?? '')) {
        $step = $pendiente;
        $error = 'Codigo incorrecto.';
    } else {
        $response = api_request('POST', '/usuarios', $pendiente['payload']);

        if ($response['ok']) {
            unset($_SESSION['registro_pendiente']);
            header('Location: register.php?success=1');
            exit;
        }

        $step = $pendiente;
        $error = $response['error'] ?: 'No se pudo registrar el usuario.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $perfilId = (int) ($_POST['perfil_id'] ?? 0);
    $empresaId = (int) ($_POST['empresa_id'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } elseif ($apellido === '') {
        $error = 'El apellido es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo invalido.';
    } elseif ($password !== $confirm) {
        $error = 'Las contrasenas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contrasena debe tener al menos 6 caracteres.';
    } elseif ($perfilId <= 0 || $empresaId <= 0) {
        $error = 'Selecciona perfil y empresa.';
    } else {
        $payload = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'password' => $password,
            'perfil_id' => $perfilId,
            'empresa_id' => $empresaId,
        ];
        $codigo = (string) random_int(100000, 999999);

        $response = api_request('POST', '/usuarios/enviar-codigo-verificacion', [
            'nombre' => $nombre,
            'email' => $email,
            'codigo' => $codigo,
        ]);

        if ($response['ok']) {
            $_SESSION['registro_pendiente'] = [
                'payload' => $payload,
                'codigo' => $codigo,
                'expira' => time() + 600,
            ];
            $step = $_SESSION['registro_pendiente'];
            $info = 'Enviamos un codigo de verificacion a tu correo.';
        } else {
            $error = $response['error'] ?: 'No se pudo enviar el codigo de verificacion.';
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
            font-size: 11px !important; font-weight: bold; text-transform: uppercase;
            color: #4db8ff; cursor: pointer; user-select: none; width: 60px; text-align: right;
        }
        select {
            width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd;
            border-radius: 8px; box-sizing: border-box;
        }
        .verify-box { background:#eff6ff; color:#0f172a; padding:14px; border-radius:10px; margin-bottom:14px; font-size:13px; font-weight:700; text-align:center; }
        .code-input { text-align:center; font-size:22px; letter-spacing:8px; font-weight:800; }
        .name-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .name-grid input { width:100%; }
        @media (max-width: 560px) {
            .name-grid { grid-template-columns:1fr; gap:0; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Crear Cuenta</h2>

    <?php if ($step): ?>
    <div class="verify-box">
        Ingresa el codigo enviado a <?= e($step['payload']['email'] ?? 'tu correo') ?>.
    </div>

    <form method="POST">
        <input type="hidden" name="accion" value="verificar">
        <input class="code-input" name="codigo" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>

        <?php if ($info): ?>
            <div class="alert-success show"><?= e($info) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-error show"><?= e($error) ?></div>
        <?php endif; ?>

        <button type="submit">Verificar y crear cuenta</button>
    </form>
    <?php else: ?>
    <form method="POST">
        <div class="name-grid">
            <input name="nombre" placeholder="Nombre" required>
            <input name="apellido" placeholder="Apellido" required>
        </div>
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
    <?php endif; ?>

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
    const toggle = document.getElementById(toggleId);
    const input = document.getElementById(inputId);
    if (!toggle || !input) return;

    toggle.onclick = function() {
        input.type = input.type === "password" ? "text" : "password";
        this.textContent = input.type === "password" ? "Ver" : "Ocultar";
    };
}
bindToggle("togglePass", "password");
bindToggle("toggleConfirm", "confirm");

const btnRegistro = document.getElementById("btnRegistro");
if (btnRegistro) {
    btnRegistro.addEventListener("click", function(e){
        const pass = document.getElementById("password");
        const confirm = document.getElementById("confirm");
        if (pass.value !== confirm.value && pass.value !== "") {
            e.preventDefault();
            alert("Las contrasenas no coinciden.");
        }
    });
}
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
