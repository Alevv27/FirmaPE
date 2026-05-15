<?php
session_start();
require_once 'includes/auth.php';
require_module('FIRMAR');
require_profile('FIRMANTE', 'ADMIN');

$usuario = current_user();
$usuarioId = (int) ($usuario['id'] ?? 0);
$perfil = current_profile();
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $procesoId = (int) ($_POST['proceso_id'] ?? 0);

    if ($procesoId > 0 && in_array($accion, ['rechazar', 'eliminar'], true)) {
        $endpoint = $accion === 'rechazar'
            ? '/procesos/' . $procesoId . '/rechazado'
            : '/procesos/' . $procesoId . '/eliminado';

        $response = api_request('PATCH', $endpoint, []);
        if ($response['ok']) {
            $mensaje = $accion === 'rechazar' ? 'Proceso rechazado.' : 'Proceso eliminado.';
            $tipoMensaje = 'success';
        } else {
            $mensaje = $response['error'] ?: 'No se pudo actualizar el proceso.';
            $tipoMensaje = 'error';
        }
    }
}

$estadoFiltro = strtoupper(trim($_GET['estado'] ?? ''));
$fechaDesde = trim($_GET['fecha_desde'] ?? '');
$fechaHasta = trim($_GET['fecha_hasta'] ?? '');
$page = max((int) ($_GET['page'] ?? 1), 1);
$path = $perfil === 'ADMIN' ? '/procesos' : '/procesos?firmante_id=' . $usuarioId;
if ($estadoFiltro !== '') {
    $path .= (str_contains($path, '?') ? '&' : '?') . 'estado=' . urlencode($estadoFiltro);
}
if ($fechaDesde !== '') {
    $path .= (str_contains($path, '?') ? '&' : '?') . 'fecha_desde=' . urlencode($fechaDesde);
}
if ($fechaHasta !== '') {
    $path .= (str_contains($path, '?') ? '&' : '?') . 'fecha_hasta=' . urlencode($fechaHasta);
}
$path .= (str_contains($path, '?') ? '&' : '?') . 'page=' . $page . '&per_page=10';

$procesosResponse = api_request('GET', $path);
$procesos = $procesosResponse['ok'] ? ($procesosResponse['data']['procesos'] ?? []) : [];
$pagination = $procesosResponse['ok'] ? ($procesosResponse['data']['pagination'] ?? ['page' => 1, 'pages' => 1, 'total' => count($procesos)]) : ['page' => 1, 'pages' => 1, 'total' => 0];
if (!$procesosResponse['ok']) {
    $mensaje = $procesosResponse['error'] ?: 'No se pudieron cargar los procesos.';
    $tipoMensaje = 'error';
}

function estado_class(string $estado): string
{
    return match (strtoupper($estado)) {
        'PENDIENTE' => 'status-pendiente',
        'FIRMADO' => 'status-firmado',
        'RECHAZADO' => 'status-rechazado',
        'ELIMINADO' => 'status-eliminado',
        default => 'status-default',
    };
}

function query_link(array $params): string
{
    $base = [
        'estado' => $_GET['estado'] ?? '',
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    ];
    $query = array_filter(array_merge($base, $params), fn($v) => $v !== '' && $v !== null);
    return 'firmante_documentos.php' . ($query ? '?' . http_build_query($query) : '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firmar documentos | FIRMAPE</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(to right, rgba(204,231,240,.7), rgba(126,200,227,.7)), url("imagenes/fondope.png"); background-size: cover; background-attachment: fixed; }
        .header { background: white; padding: 12px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .container { max-width: 1180px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,.1); }
        h2 { margin: 0 0 18px; border-bottom: 3px solid #4db8ff; padding-bottom: 10px; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .filters a { text-decoration: none; padding: 9px 14px; border-radius: 8px; background: #f1f5f9; color: #334155; font-size: 13px; font-weight: 700; }
        .filters a.active { background: #4db8ff; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 10px; font-size: 11px; color: #64748b; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        td { padding: 14px 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; vertical-align: middle; }
        .status { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; display: inline-block; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-firmado { background: #d1fae5; color: #065f46; }
        .status-rechazado { background: #fee2e2; color: #991b1b; }
        .status-eliminado { background: #e5e7eb; color: #374151; }
        .status-default { background: #e0f2fe; color: #075985; }
        .btn { border: 0; border-radius: 6px; padding: 8px 10px; color: white; font-size: 11px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-firmar { background: #6c5ce7; }
        .btn-rechazar { background: #ef4444; }
        .btn-eliminar { background: #64748b; }
        .actions { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 700; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .muted { color: #94a3b8; text-align: center; padding: 45px; }
        .date-filter { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; padding: 14px; border-radius: 10px; background: #f8fafc; margin-bottom: 18px; }
        .date-filter label { display:block; font-size: 11px; font-weight: 800; color:#475569; margin-bottom: 5px; }
        .date-filter input { padding: 9px; border: 1px solid #cbd5e1; border-radius: 7px; }
        .pager { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; color:#475569; font-size: 13px; font-weight: 700; }
        .pager-links { display: flex; gap: 8px; }
        .pager a, .pager span.disabled { padding: 8px 12px; border-radius: 8px; text-decoration: none; background: #f1f5f9; color: #334155; }
        .pager span.disabled { opacity: .45; }
    </style>
</head>
<body>
<header class="header">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="imagenes/favicon.png" width="30" alt="logo">
        <h3 style="margin:0;">FIRMAR DOCUMENTOS</h3>
    </div>
    <a href="principal.php" style="text-decoration:none; color:#4db8ff; font-weight:bold;">VOLVER</a>
</header>

<div class="container">
    <?php if ($mensaje): ?>
        <div class="alert <?= $tipoMensaje === 'success' ? 'alert-success' : 'alert-error' ?>"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Mis procesos de firma</h2>

        <div class="filters">
            <a href="<?= e(query_link(['estado' => '', 'page' => 1])) ?>" class="<?= $estadoFiltro === '' ? 'active' : '' ?>">Todos</a>
            <a href="<?= e(query_link(['estado' => 'PENDIENTE', 'page' => 1])) ?>" class="<?= $estadoFiltro === 'PENDIENTE' ? 'active' : '' ?>">Pendientes</a>
            <a href="<?= e(query_link(['estado' => 'FIRMADO', 'page' => 1])) ?>" class="<?= $estadoFiltro === 'FIRMADO' ? 'active' : '' ?>">Firmados</a>
            <a href="<?= e(query_link(['estado' => 'RECHAZADO', 'page' => 1])) ?>" class="<?= $estadoFiltro === 'RECHAZADO' ? 'active' : '' ?>">Rechazados</a>
            <a href="<?= e(query_link(['estado' => 'ELIMINADO', 'page' => 1])) ?>" class="<?= $estadoFiltro === 'ELIMINADO' ? 'active' : '' ?>">Eliminados</a>
        </div>

        <form method="GET" class="date-filter">
            <?php if ($estadoFiltro !== ''): ?>
                <input type="hidden" name="estado" value="<?= e($estadoFiltro) ?>">
            <?php endif; ?>
            <div>
                <label>Desde</label>
                <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>">
            </div>
            <div>
                <label>Hasta</label>
                <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>">
            </div>
            <button class="btn btn-firmar" type="submit">FILTRAR</button>
            <a class="btn btn-eliminar" href="<?= e(query_link(['fecha_desde' => '', 'fecha_hasta' => '', 'page' => 1])) ?>">LIMPIAR FECHAS</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <?php if ($perfil === 'ADMIN'): ?><th>Horario</th><?php endif; ?>
                    <th>Documento</th>
                    <th>Remitente</th>
                    <th>Estado</th>
                    <th style="text-align:center;">Accion</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($procesos): ?>
                <?php foreach ($procesos as $p): ?>
                    <?php
                        $estado = strtoupper((string) ($p['estado'] ?? ''));
                        $token = (string) ($p['token'] ?? '');
                        $creador = $p['creador']['nombre'] ?? 'S/N';
                        $fechaTs = $p['fecha_creacion'] ? strtotime($p['fecha_creacion']) : null;
                    ?>
                    <tr>
                        <td><?= e($fechaTs ? date('d/m/Y', $fechaTs) : '') ?></td>
                        <?php if ($perfil === 'ADMIN'): ?><td><?= e($fechaTs ? date('H:i:s', $fechaTs) : '') ?></td><?php endif; ?>
                        <td>
                            <strong><?= e($p['nombre_proceso'] ?: ($p['nombre_archivo'] ?? 'Documento')) ?></strong><br>
                            <?php if (!empty($p['descripcion'])): ?><small><?= e($p['descripcion']) ?></small><br><?php endif; ?>
                            <small><?= e($p['ruta_archivo'] ?? '') ?></small>
                        </td>
                        <td><?= e($creador) ?></td>
                        <td><span class="status <?= estado_class($estado) ?>"><?= e($estado) ?></span></td>
                        <td>
                            <div class="actions">
                                <?php if ($estado === 'PENDIENTE' && $token): ?>
                                    <a class="btn btn-firmar" href="firmar_documento.php?token=<?= urlencode($token) ?>">FIRMAR</a>
                                    <form method="POST" onsubmit="return confirm('Rechazar este proceso?');">
                                        <input type="hidden" name="accion" value="rechazar">
                                        <input type="hidden" name="proceso_id" value="<?= (int) $p['id'] ?>">
                                        <button class="btn btn-rechazar" type="submit">RECHAZAR</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Eliminar este proceso de tu bandeja?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="proceso_id" value="<?= (int) $p['id'] ?>">
                                        <button class="btn btn-eliminar" type="submit">ELIMINAR</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-weight:700;">Sin acciones</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="<?= $perfil === 'ADMIN' ? '6' : '5' ?>" class="muted">No hay procesos para mostrar.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="pager">
            <div>Mostrando maximo 10 por pagina. Total: <?= (int) ($pagination['total'] ?? 0) ?></div>
            <div class="pager-links">
                <?php if (($pagination['page'] ?? 1) > 1): ?>
                    <a href="<?= e(query_link(['page' => (int) $pagination['page'] - 1])) ?>">Anterior</a>
                <?php else: ?>
                    <span class="disabled">Anterior</span>
                <?php endif; ?>

                <span>Pagina <?= (int) ($pagination['page'] ?? 1) ?> / <?= (int) ($pagination['pages'] ?? 1) ?></span>

                <?php if (($pagination['page'] ?? 1) < ($pagination['pages'] ?? 1)): ?>
                    <a href="<?= e(query_link(['page' => (int) $pagination['page'] + 1])) ?>">Siguiente</a>
                <?php else: ?>
                    <span class="disabled">Siguiente</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
