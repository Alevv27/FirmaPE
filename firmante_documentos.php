<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
require_once 'includes/toast.php';
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
$path .= (str_contains($path, '?') ? '&' : '?') . 'page=' . $page . '&per_page=5';

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

$toast = toast_message($mensaje, $tipoMensaje === 'success' ? 'success' : 'error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firmar documentos | FIRMAPE</title>
    <?php render_sweetalert_assets(); ?>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(to right, rgba(204,231,240,.7), rgba(126,200,227,.7)), url("imagenes/fondope.png"); background-size: cover; background-attachment: fixed; }
        <?php render_firmape_topbar_styles(); ?>
        <?php render_firmape_sidebar_styles(); ?>
        .container { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
        .card { background: rgba(255,255,255,.96); border-radius: 16px; padding: 0; box-shadow: 0 18px 42px rgba(15,23,42,.13); overflow:hidden; border:1px solid rgba(226,232,240,.9); }
        .card-head { padding: 22px 24px 16px; display:flex; align-items:flex-start; justify-content:space-between; gap:18px; border-bottom:1px solid #e2e8f0; }
        .card-head h2 { margin:0 0 6px; font-size:26px; }
        .card-head p { margin:0; color:#64748b; font-weight:700; font-size:13px; }
        .count-pill { min-width:96px; padding:12px 14px; border-radius:12px; background:#eff6ff; color:#075985; text-align:center; font-weight:900; }
        .count-pill strong { display:block; font-size:24px; line-height:1; color:#0f172a; }
        .panel-body { padding:18px 24px 22px; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .filters a { text-decoration: none; padding: 10px 15px; border-radius: 9px; background: #f1f5f9; color: #334155; font-size: 13px; font-weight: 900; transition:.2s ease; }
        .filters a:hover { background:#e0f2fe; color:#0369a1; }
        .filters a.active { background: #4db8ff; color: white; }
        .table-shell { border:1px solid #e2e8f0; border-radius:14px; overflow:auto; scrollbar-width:none; }
        .table-shell::-webkit-scrollbar { width:0; height:0; }
        table { width: 100%; border-collapse: collapse; min-width:900px; }
        th { text-align: left; padding: 13px 14px; font-size: 11px; color: #64748b; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid #e2e8f0; letter-spacing:.4px; }
        td { padding: 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; vertical-align: middle; }
        tbody tr { transition:.18s ease; }
        tbody tr:hover { background:#f8fafc; }
        tbody tr:last-child td { border-bottom:0; }
        .doc-title { font-size:14px; font-weight:900; color:#0f172a; }
        .doc-desc { color:#475569; font-size:12px; margin-top:3px; }
        .doc-path { display:inline-block; margin-top:4px; color:#64748b; font-size:11px; max-width:310px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; vertical-align:bottom; }
        .status { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; display: inline-block; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-firmado { background: #d1fae5; color: #065f46; }
        .status-rechazado { background: #fee2e2; color: #991b1b; }
        .status-eliminado { background: #e5e7eb; color: #374151; }
        .status-default { background: #e0f2fe; color: #075985; }
        .btn { border: 0; border-radius: 8px; padding: 8px 11px; color: white; font-size: 11px; font-weight: 900; cursor: pointer; text-decoration: none; display: inline-flex; align-items:center; justify-content:center; min-height:32px; }
        .actions .btn { width: 78px; height: 38px; padding: 0; text-align:center; }
        .btn-ver { background: #0ea5e9; }
        .btn-firmar { background: #6c5ce7; }
        .btn-rechazar { background: #ef4444; }
        .btn-eliminar { background: #64748b; }
        .actions { display: flex; gap: 7px; justify-content: flex-start; flex-wrap: wrap; min-width:250px; }
        .muted { color: #94a3b8; text-align: center; padding: 45px; }
        .date-filter { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; padding: 14px; border-radius: 12px; background: #f8fafc; margin-bottom: 18px; border:1px solid #edf2f7; }
        .date-filter label { display:block; font-size: 11px; font-weight: 800; color:#475569; margin-bottom: 5px; }
        .date-filter input { padding: 9px; border: 1px solid #cbd5e1; border-radius: 7px; }
        .date-filter .btn { width: 114px; height: 38px; padding: 0; }
        .pager { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; color:#475569; font-size: 13px; font-weight: 800; }
        .pager-links { display: flex; gap: 8px; }
        .pager a, .pager span.disabled { padding: 8px 12px; border-radius: 8px; text-decoration: none; background: #f1f5f9; color: #334155; }
        .pager span.disabled { opacity: .45; }
    </style>
</head>
<body>
<?php render_firmape_topbar(); ?>

<?php render_firmape_sidebar('firmar'); ?>
<main class="module-content">
<div class="container">
    <div class="card">
        <div class="card-head">
            <div>
                <h2>Mis procesos de firma</h2>
                <p>Revisa, firma o consulta los documentos asignados a tu bandeja.</p>
            </div>
            <div class="count-pill">
                <strong><?= (int) ($pagination['total'] ?? 0) ?></strong>
                procesos
            </div>
        </div>

        <div class="panel-body">
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

        <div class="table-shell">
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
                        $rutaArchivo = (string) ($p['ruta_archivo'] ?? '');
                    ?>
                    <tr>
                        <td><?= e($fechaTs ? date('d/m/Y', $fechaTs) : '') ?></td>
                        <?php if ($perfil === 'ADMIN'): ?><td><?= e($fechaTs ? date('H:i:s', $fechaTs) : '') ?></td><?php endif; ?>
                        <td>
                            <div class="doc-title"><?= e($p['nombre_proceso'] ?: ($p['nombre_archivo'] ?? 'Documento')) ?></div>
                            <?php if (!empty($p['descripcion'])): ?><div class="doc-desc"><?= e($p['descripcion']) ?></div><?php endif; ?>
                            <?php if (!empty($p['ruta_archivo'])): ?><span class="doc-path"><?= e($p['ruta_archivo']) ?></span><?php endif; ?>
                        </td>
                        <td><?= e($creador) ?></td>
                        <td><span class="status <?= estado_class($estado) ?>"><?= e($estado) ?></span></td>
                        <td>
                            <div class="actions">
                                <?php if ($rutaArchivo !== ''): ?>
                                    <a class="btn btn-ver" href="<?= e($rutaArchivo) ?>" target="_blank">VER PDF</a>
                                <?php endif; ?>
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
                                    <?php if ($rutaArchivo === ''): ?>
                                        <span style="color:#94a3b8; font-weight:700;">Sin acciones</span>
                                    <?php endif; ?>
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
        </div>

        <div class="pager">
            <div>Mostrando maximo 5 por pagina. Total: <?= (int) ($pagination['total'] ?? 0) ?></div>
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
</div>
</main>
<?php render_toast_script($toast); ?>
</div>
</body>
</html>
