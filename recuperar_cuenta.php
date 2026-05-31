<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/toast.php';

$error = '';
$info = '';
$step = $_SESSION['recuperacion_cuenta'] ?? null;

function password_segura_error(string $password): ?string
{
    if (strlen($password) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
    if (preg_match('/\s/', $password)) return 'La contraseña no debe contener espacios.';
    if (!preg_match('/[a-z]/', $password)) return 'La contraseña debe incluir una letra minuscula.';
    if (!preg_match('/[A-Z]/', $password)) return 'La contraseña debe incluir una letra mayuscula.';
    if (!preg_match('/\d/', $password)) return 'La contraseña debe incluir un numero.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return 'La contraseña debe incluir un simbolo.';
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'solicitar') {
    $identificador = trim($_POST['identificador'] ?? '');

    if ($identificador === '') {
        $error = 'Ingresa tu DNI o correo registrado.';
    } else {
        $response = api_request('POST', '/usuarios/recuperar-cuenta/enviar-codigo', [
            'identificador' => $identificador,
        ]);

        if ($response['ok']) {
            $_SESSION['recuperacion_cuenta'] = [
                'reset_token' => $response['data']['reset_token'] ?? '',
                'email' => $response['data']['email'] ?? 'tu correo',
            ];
            $step = $_SESSION['recuperacion_cuenta'];
            $info = 'Enviamos un codigo de recuperacion a tu correo.';
        } else {
            $error = $response['error'] ?: 'No se pudo enviar el codigo de recuperacion.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'confirmar') {
    $step = $_SESSION['recuperacion_cuenta'] ?? null;
    $codigo = trim($_POST['codigo'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$step || empty($step['reset_token'])) {
        $error = 'Solicita un nuevo codigo de recuperacion.';
        $step = null;
    } elseif ($codigo === '') {
        $error = 'Ingresa el codigo enviado a tu correo.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($passwordError = password_segura_error($password)) {
        $error = $passwordError;
    } else {
        $response = api_request('POST', '/usuarios/recuperar-cuenta/confirmar', [
            'reset_token' => $step['reset_token'],
            'codigo' => $codigo,
            'password' => $password,
        ]);

        if ($response['ok']) {
            unset($_SESSION['recuperacion_cuenta']);
            header('Location: recuperar_cuenta.php?success=1');
            exit;
        }

        $error = $response['error'] ?: 'No se pudo restablecer la contraseña.';
    }
}

$toast = toast_message($error ?: $info, $error ? 'error' : 'success');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña | FIRMAPE</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="icon" href="imagenes/favicon.png">
    <?php render_sweetalert_assets(); ?>
    <style>
        .container { max-width: 420px; padding: 36px 34px; }
        h2 { margin:0 0 10px; text-align:center; color:#0f172a; }
        .subtitle { margin:0 0 20px; text-align:center; color:#64748b; font-size:14px; }
        .verify-box { background:#eff6ff; color:#0f172a; padding:14px; border-radius:10px; margin-bottom:14px; font-size:13px; font-weight:700; text-align:center; }
        .code-input { text-align:center; font-size:22px; letter-spacing:8px; font-weight:800; }
        .password-container { position:relative; }
        .password-container input { padding-right:70px; }
        .toggle-password { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:#4db8ff; font-size:11px; font-weight:bold; text-transform:uppercase; user-select:none; }
        .password-rules { margin: 8px 0 10px; padding: 10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:12px; color:#64748b; display:grid; gap:5px; }
        .password-rule { display:flex; align-items:center; gap:7px; }
        .password-rule::before { content:'x'; width:16px; height:16px; border-radius:50%; background:#e2e8f0; color:#64748b; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:900; }
        .password-rule.ok { color:#047857; font-weight:700; }
        .password-rule.ok::before { content:'OK'; background:#dcfce7; color:#047857; font-size:8px; }
        .links { display:flex; justify-content:center; gap:18px; margin-top:15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Recuperar contraseña</h2>
    <p class="subtitle">Restablece tu contraseña con un codigo enviado a tu correo.</p>

    <?php if ($step): ?>
        <div class="verify-box">Ingresa el codigo enviado a <?= e($step['email'] ?? 'tu correo') ?>.</div>
        <form method="POST" id="formReset">
            <input type="hidden" name="accion" value="confirmar">
            <input class="code-input" name="codigo" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>

            <div class="password-container">
                <input type="password" name="password" id="password" placeholder="Nueva contraseña" required>
                <span class="toggle-password" data-target="password">Ver</span>
            </div>
            <div class="password-rules" aria-live="polite">
                <div class="password-rule" data-rule="length">Minimo 8 caracteres</div>
                <div class="password-rule" data-rule="lower">Una letra minuscula</div>
                <div class="password-rule" data-rule="upper">Una letra mayuscula</div>
                <div class="password-rule" data-rule="number">Un numero</div>
                <div class="password-rule" data-rule="symbol">Un simbolo</div>
                <div class="password-rule" data-rule="space">Sin espacios</div>
            </div>

            <div class="password-container">
                <input type="password" name="confirm" id="confirm" placeholder="Confirmar contraseña" required>
                <span class="toggle-password" data-target="confirm">Ver</span>
            </div>

            <button type="submit">Guardar nueva contraseña</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="accion" value="solicitar">
            <input type="text" name="identificador" placeholder="DNI o correo electronico" required>
            <button type="submit">Enviar codigo</button>
        </form>
    <?php endif; ?>

    <div class="links">
        <a href="index.php">Volver al login</a>
        <a href="register.php">Crear cuenta</a>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const input = document.getElementById(toggle.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        toggle.textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
    });
});

const passwordInput = document.getElementById('password');
const resetForm = document.getElementById('formReset');
const ruleItems = document.querySelectorAll('.password-rule');

function evaluarPassword(value) {
    return {
        length: value.length >= 8,
        lower: /[a-z]/.test(value),
        upper: /[A-Z]/.test(value),
        number: /\d/.test(value),
        symbol: /[^A-Za-z0-9]/.test(value),
        space: value !== '' && !/\s/.test(value)
    };
}

function passwordEsSegura(value) {
    return Object.values(evaluarPassword(value)).every(Boolean);
}

function actualizarReglas() {
    if (!passwordInput) return;
    const rules = evaluarPassword(passwordInput.value);
    ruleItems.forEach((item) => item.classList.toggle('ok', Boolean(rules[item.dataset.rule])));
}

passwordInput?.addEventListener('input', actualizarReglas);
actualizarReglas();

resetForm?.addEventListener('submit', (event) => {
    const confirm = document.getElementById('confirm');
    if (!passwordEsSegura(passwordInput.value)) {
        event.preventDefault();
        Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Crea una contraseña segura.', showConfirmButton:false, timer:3200 });
        return;
    }
    if (passwordInput.value !== confirm.value) {
        event.preventDefault();
        Swal.fire({ toast:true, position:'top-end', icon:'error', title:'Las contraseñas no coinciden.', showConfirmButton:false, timer:3200 });
    }
});
</script>
<?php render_toast_script($toast); ?>

<?php if (isset($_GET['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Contraseña actualizada',
    text: 'Ya puedes iniciar sesion con tu nueva contraseña.',
    confirmButtonText: 'Ir al login'
}).then(() => window.location.href = 'index.php');
</script>
<?php endif; ?>
</body>
</html>
