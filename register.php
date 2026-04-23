<?php
session_start();
require_once 'includes/auth.php';

$error = '';
$success = '';

$empresasResponse = api_request('GET', '/empresas');
$perfilesResponse = api_request('GET', '/perfiles');

$empresas = $empresasResponse['ok'] ? ($empresasResponse['data']['empresas'] ?? []) : [];
$perfiles = $perfilesResponse['ok'] ? ($perfilesResponse['data']['perfiles'] ?? []) : [];

$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$perfilId = $_POST['perfil_id'] ?? '';
$empresaId = $_POST['empresa_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($nombre === '') {
        $error = 'nombre';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'email';
    } elseif ($password !== $confirm) {
        $error = 'password';
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W]).{8,}$/', $password)) {
        $error = 'weak';
    } elseif ($perfilId === '' || $empresaId === '') {
        $error = 'select';
    } else {
        $response = api_request('POST', '/usuarios', [
            'nombre' => $nombre,
            'email' => strtolower($correo),
            'password' => $password,
            'perfil_id' => (int) $perfilId,
            'empresa_id' => (int) $empresaId,
        ]);

        if ($response['ok']) {
            $success = 'Usuario registrado correctamente.';
            $nombre = '';
            $correo = '';
            $perfilId = '';
            $empresaId = '';
        } else {
            $apiError = strtolower((string) ($response['error'] ?? ''));
            $error = strpos($apiError, 'email') !== false ? 'exists' : 'db';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<div class="container">
    <h2>Registro</h2>

    <form method="POST">
        <input name="nombre" placeholder="Nombre completo" value="<?= e($nombre) ?>" required>
        <input name="correo" type="email" placeholder="Correo" value="<?= e($correo) ?>" required>

        <select name="perfil_id" required>
            <option value="">Selecciona perfil</option>
            <?php foreach ($perfiles as $perfil): ?>
            <option value="<?= e((string) $perfil['id']) ?>" <?= (string) $perfilId === (string) $perfil['id'] ? 'selected' : '' ?>>
                <?= e($perfil['codigo']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="empresa_id" required>
            <option value="">Selecciona empresa</option>
            <?php foreach ($empresas as $empresa): ?>
            <option value="<?= e((string) $empresa['id']) ?>" <?= (string) $empresaId === (string) $empresa['id'] ? 'selected' : '' ?>>
                <?= e($empresa['nombre']) ?>
            </option>
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

        <?php if ($error !== ''): ?>
        <div class="alert-error show">
            <?php
            if ($error === 'nombre') echo 'El nombre es obligatorio';
            elseif ($error === 'email') echo 'Correo invalido';
            elseif ($error === 'password') echo 'Las contrasenas no coinciden';
            elseif ($error === 'weak') echo 'Contrasena insegura';
            elseif ($error === 'select') echo 'Selecciona perfil y empresa';
            elseif ($error === 'exists') echo 'El correo ya esta registrado';
            else echo 'Error al registrar contra el backend';
            ?>
        </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
        <div class="alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="requisitos">
            <div><span id="min" class="circulo"></span> 8 caracteres</div>
            <div><span id="mayus" class="circulo"></span> Mayuscula</div>
            <div><span id="minus" class="circulo"></span> Minuscula</div>
            <div><span id="num" class="circulo"></span> Numero</div>
            <div><span id="simbolo" class="circulo"></span> Simbolo</div>
        </div>

        <div class="barra">
            <div id="fuerza"></div>
        </div>

        <p id="nivel"></p>

        <button id="btnRegistro" type="submit">Registrar</button>
    </form>

    <div class="links">
        <a href="principal.php">Volver al panel</a>
        <a href="login.php">Ir al login</a>
    </div>
</div>

<script>
const pass = document.getElementById('password');
const confirm = document.getElementById('confirm');
const btn = document.getElementById('btnRegistro');
const barra = document.getElementById('fuerza');
const nivel = document.getElementById('nivel');

document.getElementById('togglePass').onclick = () => {
  pass.type = pass.type === 'password' ? 'text' : 'password';
};

document.getElementById('toggleConfirm').onclick = () => {
  confirm.type = confirm.type === 'password' ? 'text' : 'password';
};

function validar() {
  let val = pass.value;
  let score = 0;

  const min = document.getElementById('min');
  const mayus = document.getElementById('mayus');
  const minus = document.getElementById('minus');
  const num = document.getElementById('num');
  const simbolo = document.getElementById('simbolo');

  function check(cond, el) {
    if (cond) {
      el.classList.add('ok');
      score++;
    } else {
      el.classList.remove('ok');
    }
  }

  check(val.length >= 8, min);
  check(/[A-Z]/.test(val), mayus);
  check(/[a-z]/.test(val), minus);
  check(/[0-9]/.test(val), num);
  check(/[\W]/.test(val), simbolo);

  let porcentaje = (score / 5) * 100;
  barra.style.width = porcentaje + '%';

  if (score <= 2) {
    barra.style.background = '#ff4d4d';
    nivel.textContent = 'Debil';
  } else if (score <= 4) {
    barra.style.background = '#ffa500';
    nivel.textContent = 'Media';
  } else {
    barra.style.background = '#00cc66';
    nivel.textContent = 'Muy segura';
  }
}

pass.addEventListener('keyup', validar);
confirm.addEventListener('keyup', validar);

btn.addEventListener('click', function(e) {
  if (pass.value !== confirm.value) {
    e.preventDefault();

    const alerta = document.querySelector('.alert-error');
    if (alerta) {
      alerta.innerHTML = 'Las contrasenas no coinciden';
      alerta.classList.add('show');
    }

    btn.classList.add('shake');
    setTimeout(() => btn.classList.remove('shake'), 300);
  }
});
</script>

</body>
</html>
