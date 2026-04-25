<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once '../config/conexion.php';

// --- LÓGICA DE ELIMINACIÓN ---
if (isset($_GET['eliminar'])) {
    $id_a_eliminar = intval($_GET['eliminar']);
    if ($id_a_eliminar !== $_SESSION['admin_id']) {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_a_eliminar);
        $stmt->execute();
        header("Location: admin_panel.php?res=eliminado");
        exit();
    }
}

if (isset($_GET['del_doc'])) {
    $id_doc = intval($_GET['del_doc']);
    $stmt = $conexion->prepare("DELETE FROM documentos WHERE id = ?");
    $stmt->bind_param("i", $id_doc);
    $stmt->execute();
    $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
    header("Location: admin_panel.php?res=doc_eliminado&p=$p");
    exit();
}

// --- FILTROS HISTORIAL ---
$where_clauses = [];
$params = [];
$types = "";

if (!empty($_GET['f_estado'])) { $where_clauses[] = "estado = ?"; $params[] = $_GET['f_estado']; $types .= "s"; }
if (!empty($_GET['f_fecha'])) { $where_clauses[] = "DATE(fecha_envio) = ?"; $params[] = $_GET['f_fecha']; $types .= "s"; }
if (!empty($_GET['f_dest'])) { $where_clauses[] = "id_destinatario LIKE ?"; $params[] = "%".$_GET['f_dest']."%"; $types .= "s"; }
if (!empty($_GET['f_remitente'])) { $where_clauses[] = "id_remitente LIKE ?"; $params[] = "%".$_GET['f_remitente']."%"; $types .= "s"; }

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- PAGINACIÓN ---
$por_pagina = 10;
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$inicio = ($pagina - 1) * $por_pagina;

$stmt_count = $conexion->prepare("SELECT COUNT(*) as total FROM documentos $where_sql");
if ($types) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// --- CONSULTAS ---
$usuarios = $conexion->query("SELECT id, dni, nombre, correo, rol FROM usuarios ORDER BY id DESC");
$sql_h = "SELECT * FROM documentos $where_sql ORDER BY fecha_envio DESC LIMIT ?, ?";
$stmt_h = $conexion->prepare($sql_h);
$final_params = array_merge($params, [$inicio, $por_pagina]);
$stmt_h->bind_param($types . "ii", ...$final_params);
$stmt_h->execute();
$historial_docs = $stmt_h->get_result();

$total_docs_pend = $conexion->query("SELECT COUNT(*) as total FROM documentos WHERE estado = 'Pendiente'")->fetch_assoc()['total'];
$total_users_count = $conexion->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo | FIRMAPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --glass-bg: rgba(255, 255, 255, 0.75); --glass-border: rgba(255, 255, 255, 0.5); --text-main: #1e293b; --accent: #6366f1; }
        body { margin: 0; font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .bg-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(255,255,255,0.92), rgba(255,255,255,0.6)), url('../imagenes/fondopanel.png'); background-size: cover; z-index: -1; opacity: 0.7; }
        .navbar { background: rgba(255,255,255,0.85); padding: 15px 40px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 100; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--glass-bg); padding: 25px; border-radius: 24px; border: 1px solid var(--glass-border); text-align: center; backdrop-filter: blur(15px); }
        
        .card { background: var(--glass-bg); border-radius: 24px; border: 1px solid var(--glass-border); backdrop-filter: blur(15px); margin-bottom: 30px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.04); }
        .card-header { padding: 22px 30px; border-bottom: 1px solid rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; }
        
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; padding: 20px 30px; background: rgba(255,255,255,0.3); align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .filter-group input, .filter-group select { padding: 10px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); font-size: 13px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 18px 25px; text-align: center; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 18px 25px; font-size: 14px; border-bottom: 1px solid rgba(0,0,0,0.03); text-align: center; }
        
        /* ESTILO ROLES: FONDO NEGRO LETRA BLANCA */
        .role-badge { 
            background: #000; 
            color: #fff; 
            padding: 5px 12px; 
            border-radius: 8px; 
            font-size: 10px; 
            font-weight: 800; 
            text-transform: uppercase; 
            display: inline-block;
        }

        .btn { padding: 12px 22px; border-radius: 14px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
        .btn-dark { background: #1e293b; color: #fff; }
        .btn-accent { background: var(--accent); color: white; }
        
        .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-firmado { background: #dcfce7; color: #166534; }
        .status-pendiente { background: #fffbeb; color: #b45309; }
        
        .btn-del-x { color: #ef4444; font-weight: 900; font-size: 20px; background: none; border: none; cursor: pointer; }
        
        .pagination { padding: 25px; text-align: center; display: flex; justify-content: center; gap: 8px; }
        .pagination a { text-decoration: none; padding: 8px 16px; border-radius: 12px; background: white; color: black; font-weight: 700; border: 1px solid var(--glass-border); }
        .pagination a.active { background: var(--accent); color: white; }
    </style>
</head>
<body>
    <div class="bg-overlay"></div>
    <nav class="navbar">
        <h2 style="margin:0;">FIRMAPE <span style="font-weight:300; color:#64748b;">ADMIN</span></h2>
        <a href="logout.php" class="btn" style="background:#fee2e2; color:#dc2626;">Cerrar Sesión</a>
    </nav>

    <div class="container">
        <div class="stats">
            <div class="stat-card"><label>USUARIOS</label><p style="font-size:36px; font-weight:800; margin:5px 0;"><?= $total_users_count ?></p></div>
            <div class="stat-card"><label>PENDIENTES</label><p style="font-size:36px; font-weight:800; margin:5px 0; color:var(--accent);"><?= $total_docs_pend ?></p></div>
            <div class="stat-card"><label>SISTEMA</label><p style="font-size:13px; font-weight:700; color:#10b981; margin-top:20px;">● ACTIVO</p></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 style="margin:0;">Gestión de Usuarios</h3>
                <a href="admin_crear.php" class="btn btn-dark">+ Nuevo Usuario</a>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= $u['dni'] ?></strong></td>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['correo']) ?></td>
                            <td><span class="role-badge"><?= $u['rol'] ?></span></td>
                            <td>
                                <a href="admin_editar.php?id=<?= $u['id'] ?>" style="color:var(--accent); font-weight:700; text-decoration:none; margin-right:10px;">Editar</a>
                                <button onclick="confirmar('admin_panel.php?eliminar=<?= $u['id'] ?>', 'usuario')" style="color:#ef4444; font-weight:700; background:none; border:none; cursor:pointer;">Borrar</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Historial de Documentos</h3></div>
            <form method="GET">
                <div class="filter-grid">
                    <div class="filter-group"><label>Estado</label><select name="f_estado"><option value="">Todos</option><option value="Pendiente">Pendiente</option><option value="Firmado">Firmado</option><option value="Rechazado">Rechazado</option></select></div>
                    <div class="filter-group"><label>Fecha</label><input type="date" name="f_fecha"></div>
                    <div class="filter-group"><label>Destinatario</label><input type="text" name="f_dest" placeholder="DNI..."></div>
                    <div class="filter-group"><label>Remitente</label><input type="text" name="f_remitente" placeholder="DNI..."></div>
                    <button type="submit" class="btn btn-accent">Filtrar</button>
                </div>
            </form>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Remitente</th>
                            <th>Destinatario</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($d = $historial_docs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $d['nombre_archivo'] ?></td>
                            <td><?= $d['id_remitente'] ?></td>
                            <td><?= $d['id_destinatario'] ?></td>
                            <td><span class="status-pill status-<?= strtolower($d['estado']) ?>"><?= $d['estado'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($d['fecha_envio'])) ?></td>
                            <td><button onclick="confirmar('admin_panel.php?del_doc=<?= $d['id'] ?>', 'documento')" class="btn-del-x">×</button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmar(url, tipo) {
        Swal.fire({
            title: '¿Eliminar ' + tipo + '?',
            text: "Esta acción no tiene vuelta atrás.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#000',
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => { if (result.isConfirmed) { window.location.href = url; } });
    }
    </script>
</body>
</html>