<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'includes/auth.php';
require_once 'includes/toast.php';

$error = '';
$info = '';
$step = $_SESSION['registro_pendiente'] ?? null;
$perfilesResponse = api_request('GET', '/perfiles');
$empresasResponse = api_request('GET', '/empresas');
$perfiles = $perfilesResponse['ok'] ? ($perfilesResponse['data']['perfiles'] ?? []) : [];
$empresas = $empresasResponse['ok'] ? ($empresasResponse['data']['empresas'] ?? []) : [];

function password_segura_error(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'La contrasena debe tener al menos 8 caracteres.';
    }
    if (preg_match('/\s/', $password)) {
        return 'La contrasena no debe contener espacios.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'La contrasena debe incluir una letra minuscula.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'La contrasena debe incluir una letra mayuscula.';
    }
    if (!preg_match('/\d/', $password)) {
        return 'La contrasena debe incluir un numero.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'La contrasena debe incluir un simbolo.';
    }
    return null;
}

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
    $dni = trim($_POST['dni'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $perfilId = (int) ($_POST['perfil_id'] ?? 0);
    $empresaId = (int) ($_POST['empresa_id'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } elseif ($apellido === '') {
        $error = 'El apellido es obligatorio.';
    } elseif (!preg_match('/^\d{8}$/', $dni)) {
        $error = 'El DNI debe tener 8 digitos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo invalido.';
    } elseif ($password !== $confirm) {
        $error = 'Las contrasenas no coinciden.';
    } elseif ($passwordError = password_segura_error($password)) {
        $error = $passwordError;
    } elseif ($perfilId <= 0 || $empresaId <= 0) {
        $error = 'Selecciona perfil y empresa.';
    } else {
        $payload = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'email' => $email,
            'password' => $password,
            'perfil_id' => $perfilId,
            'empresa_id' => $empresaId,
        ];
        $codigo = (string) random_int(100000, 999999);

        $response = api_request('POST', '/usuarios/enviar-codigo-verificacion', [
            'nombre' => $nombre,
            'dni' => $dni,
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

$toast = toast_message($error ?: $info, $error ? 'error' : 'success');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="css/estilos.css">
    <?php render_sweetalert_assets(); ?>
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
        .password-rules { margin: 8px 0 10px; padding: 10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; font-size:12px; color:#64748b; display:grid; gap:5px; }
        .password-rule { display:flex; align-items:center; gap:7px; }
        .password-rule::before { content:'x'; width:16px; height:16px; border-radius:50%; background:#e2e8f0; color:#64748b; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:900; }
        .password-rule.ok { color:#047857; font-weight:700; }
        .password-rule.ok::before { content:'OK'; background:#dcfce7; color:#047857; font-size:8px; }
        .password-strength { height:6px; border-radius:999px; overflow:hidden; background:#e2e8f0; margin-top:2px; }
        .password-strength span { display:block; height:100%; width:0%; background:#ef4444; transition:.2s ease; }
        .password-strength.medium span { width:60%; background:#f59e0b; }
        .password-strength.strong span { width:100%; background:#10b981; }
        #btnRegistro:disabled { opacity:.55; cursor:not-allowed; }
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

        <button type="submit">Verificar y crear cuenta</button>
    </form>
    <?php else: ?>
    <form method="POST">
        <div class="name-grid">
            <input name="nombre" placeholder="Nombre" required>
            <input name="apellido" placeholder="Apellido" required>
        </div>
        <input type="text" name="dni" placeholder="DNI" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" required>
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
        <div class="password-rules" id="passwordRules" aria-live="polite">
            <div class="password-strength" id="passwordStrength"><span></span></div>
            <div class="password-rule" data-rule="length">Minimo 8 caracteres</div>
            <div class="password-rule" data-rule="lower">Una letra minuscula</div>
            <div class="password-rule" data-rule="upper">Una letra mayuscula</div>
            <div class="password-rule" data-rule="number">Un numero</div>
            <div class="password-rule" data-rule="symbol">Un simbolo</div>
            <div class="password-rule" data-rule="space">Sin espacios</div>
        </div>

        <div class="input-group">
            <input type="password" name="confirm" id="confirm" placeholder="Confirmar contrasena" required>
            <span class="eye" id="toggleConfirm">Ver</span>
        </div>

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

const passwordInput = document.getElementById("password");
const confirmInput = document.getElementById("confirm");
const strengthBar = document.getElementById("passwordStrength");
const ruleItems = document.querySelectorAll(".password-rule");

function evaluarPassword(value) {
    return {
        length: value.length >= 8,
        lower: /[a-z]/.test(value),
        upper: /[A-Z]/.test(value),
        number: /\d/.test(value),
        symbol: /[^A-Za-z0-9]/.test(value),
        space: value !== "" && !/\s/.test(value)
    };
}

function passwordEsSegura(value) {
    const rules = evaluarPassword(value);
    return Object.values(rules).every(Boolean);
}

function actualizarSeguridadPassword() {
    if (!passwordInput || !strengthBar) return;
    const rules = evaluarPassword(passwordInput.value);
    const okCount = Object.values(rules).filter(Boolean).length;

    ruleItems.forEach((item) => {
        item.classList.toggle("ok", Boolean(rules[item.dataset.rule]));
    });

    strengthBar.classList.remove("medium", "strong");
    strengthBar.querySelector("span").style.width = passwordInput.value ? "30%" : "0%";
    if (okCount >= 4 && okCount < 6) strengthBar.classList.add("medium");
    if (okCount === 6) strengthBar.classList.add("strong");
}

passwordInput?.addEventListener("input", actualizarSeguridadPassword);
actualizarSeguridadPassword();

const btnRegistro = document.getElementById("btnRegistro");
if (btnRegistro) {
    btnRegistro.addEventListener("click", function(e){
        const pass = passwordInput;
        const confirm = confirmInput;
        if (!passwordEsSegura(pass.value)) {
            e.preventDefault();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Crea una contrasena segura antes de registrar.',
                showConfirmButton: false,
                timer: 3200,
                timerProgressBar: true,
                width: '360px'
            });
            return;
        }
        if (pass.value !== confirm.value && pass.value !== "") {
            e.preventDefault();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Las contrasenas no coinciden.',
                showConfirmButton: false,
                timer: 3200,
                timerProgressBar: true,
                width: '360px'
            });
        }
    });
}
</script>
<?php render_toast_script($toast); ?>

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
