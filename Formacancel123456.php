<?php
// ----------------------- PHP BACKEND ---------------------------
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

setlocale(LC_ALL, 'es_ES.UTF-8', 'spanish');
date_default_timezone_set('America/Mexico_City');

// Conexión a SQL Server
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionInfo = array(
    "Database" => "Comedor",
    "UID" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "ReturnDatesAsStrings" => true
);

$conn = sqlsrv_connect($serverName, $connectionInfo);
if (!$conn) {
    die("<div class='alert alert-danger'>Error de conexión a la base de datos</div>");
}

// Obtener filtro de estado desde GET
$filtro_estado = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendientes';
$rol = isset($_GET['newpwd']) ? $_GET['newpwd'] : '';

// Procesar validaciones POST
$mensaje_exito = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['validar_individual'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $accion = $_POST['accion'] ?? 'aprobar'; // 'aprobar' o 'rechazar'
        $rol_usuario = $_GET['newpwd'] ?? '';
        
        if (!empty($nombre) && !empty($fecha) && !empty($rol_usuario)) {
            if ($rol_usuario == 'Administrador') {
                $estado = ($accion == 'aprobar') ? 'APROBADO' : 'RECHAZADO';
                $sql_update = "UPDATE cancelaciones SET Estatus = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                $mensaje_exito = ($accion == 'aprobar') ? 
                    "✅ Validación de Administrador completada para: " . $nombre :
                    "❌ Rechazo de Administrador registrado para: " . $nombre;
            } elseif ($rol_usuario == 'Coordinador') {
                $estado = ($accion == 'aprobar') ? 'APROBADO' : 'RECHAZADO';
                $sql_update = "UPDATE cancelaciones SET ValJefDirect = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                $mensaje_exito = ($accion == 'aprobar') ? 
                    "✅ Validación de Coordinador completada para: " . $nombre :
                    "❌ Rechazo de Coordinador registrado para: " . $nombre;
            }
            
            if (isset($sql_update)) {
                $params = array($estado, $nombre, $fecha);
                $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                if (sqlsrv_execute($stmt_update)) {
                    $tipo_mensaje = ($accion == 'aprobar') ? 'success' : 'warning';
                    $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=" . $tipo_mensaje;
                    if (isset($_GET['newpwd'])) {
                        $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
                    }
                    header("Location: " . $redirect_url);
                    exit();
                }
            }
        }
    }
    
    if (isset($_POST['validar_lote'])) {
        $tipo_validacion = $_POST['TIPOVALIDA'] ?? '';
        $departamento = $_POST['DEPARTAMENTO'] ?? '';
        $nombre_lote = $_POST['name123'] ?? '';
        $fecha_lote = $_POST['name1234'] ?? '';
        $accion_lote = $_POST['accion_lote'] ?? 'aprobar';
        $rol_usuario = $_GET['newpwd'] ?? '';
        
        if (!empty($rol_usuario)) {
            $estado = ($accion_lote == 'aprobar') ? 'APROBADO' : 'RECHAZADO';
            
            if ($tipo_validacion == 'UNICA') {
                if (!empty($nombre_lote) && !empty($fecha_lote)) {
                    if ($rol_usuario == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                    } elseif ($rol_usuario == 'Coordinador') {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = ? WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                    }
                    
                    if (isset($sql_update)) {
                        $params = array($estado, $nombre_lote, $fecha_lote);
                        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                        if (sqlsrv_execute($stmt_update)) {
                            $mensaje_exito = ($accion_lote == 'aprobar') ? 
                                "✅ Validación única completada para: " . $nombre_lote :
                                "❌ Rechazo único registrado para: " . $nombre_lote;
                            $tipo_mensaje = ($accion_lote == 'aprobar') ? 'success' : 'warning';
                        }
                    }
                }
            } else {
                if (!empty($departamento)) {
                    if ($rol_usuario == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = ? WHERE DEPARTAMENTO LIKE ? AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
                    } elseif ($rol_usuario == 'Coordinador') {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = ? WHERE DEPARTAMENTO LIKE ? AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
                    }
                    
                    if (isset($sql_update)) {
                        $params = array($estado, '%' . $departamento . '%');
                        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                        if (sqlsrv_execute($stmt_update)) {
                            $rows_affected = sqlsrv_rows_affected($stmt_update);
                            $mensaje_exito = ($accion_lote == 'aprobar') ? 
                                "✅ Validación múltiple completada para departamento: " . $departamento . " ($rows_affected registros actualizados)" :
                                "❌ Rechazo múltiple registrado para departamento: " . $departamento . " ($rows_affected registros actualizados)";
                            $tipo_mensaje = ($accion_lote == 'aprobar') ? 'success' : 'warning';
                        }
                    }
                }
            }
            
            if (!empty($mensaje_exito)) {
                $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=" . $tipo_mensaje;
                if (isset($_GET['newpwd'])) {
                    $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
                }
                header("Location: " . $redirect_url);
                exit();
            }
        }
    }
}

// Mostrar mensaje de éxito si viene por GET
if (isset($_GET['mensaje'])) {
    $mensaje_exito = urldecode($_GET['mensaje']);
    $tipo_mensaje = $_GET['tipo'] ?? 'success';
}

// CONSULTA SEGÚN FILTRO Y ROL - INCLUYENDO DESCRIPCION
// EXCLUIR REGISTROS RECHAZADOS según rol
if ($filtro_estado == 'pendientes') {
    if ($rol == 'Administrador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE (Estatus != 'APROBADO' OR Estatus IS NULL) 
                AND Estatus != 'RECHAZADO'
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } elseif ($rol == 'Coordinador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                AND ValJefDirect != 'RECHAZADO'
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } else {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE ((Estatus != 'APROBADO' OR Estatus IS NULL) 
                OR (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL))
                AND (Estatus != 'RECHAZADO' OR Estatus IS NULL)
                AND (ValJefDirect != 'RECHAZADO' OR ValJefDirect IS NULL)
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    }
} else {
    if ($rol == 'Administrador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE Estatus = 'APROBADO' 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } elseif ($rol == 'Coordinador') {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE ValJefDirect = 'APROBADO' 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } else {
        $sql = "SELECT *, DESCRIPCION as DESCRIPCION_DETALLE FROM cancelaciones 
                WHERE (Estatus = 'APROBADO' OR ValJefDirect = 'APROBADO')
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    }
}

// Ejecutar consulta
$stmt = sqlsrv_query($conn, $sql);
$registros = array();

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($encoding != 'UTF-8') {
                    $value = mb_convert_encoding($value, 'UTF-8', $encoding);
                }
                $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        $registros[] = $row;
    }
}

// CONTADORES (excluyendo RECHAZADOS)
if ($rol == 'Administrador') {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE (Estatus != 'APROBADO' OR Estatus IS NULL) 
                      AND Estatus != 'RECHAZADO'
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} elseif ($rol == 'Coordinador') {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                      AND ValJefDirect != 'RECHAZADO'
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} else {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE ((Estatus != 'APROBADO' OR Estatus IS NULL) 
                      OR (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL))
                      AND (Estatus != 'RECHAZADO' OR Estatus IS NULL)
                      AND (ValJefDirect != 'RECHAZADO' OR ValJefDirect IS NULL)
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
}

if ($rol == 'Administrador') {
    $sql_aprobados = "SELECT COUNT(*) as total FROM cancelaciones 
                     WHERE Estatus = 'APROBADO' 
                     AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} elseif ($rol == 'Coordinador') {
    $sql_aprobados = "SELECT COUNT(*) as total FROM cancelaciones 
                     WHERE ValJefDirect = 'APROBADO' 
                     AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} else {
    $sql_aprobados = "SELECT COUNT(*) as total FROM cancelaciones 
                     WHERE (Estatus = 'APROBADO' OR ValJefDirect = 'APROBADO')
                     AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
}

// Ejecutar contadores
$stmt_p = sqlsrv_query($conn, $sql_pendientes);
$stmt_a = sqlsrv_query($conn, $sql_aprobados);

$total_pendientes = 0;
$total_aprobados = 0;

if ($stmt_p && $row = sqlsrv_fetch_array($stmt_p, SQLSRV_FETCH_ASSOC)) {
    $total_pendientes = $row['total'];
}

if ($stmt_a && $row = sqlsrv_fetch_array($stmt_a, SQLSRV_FETCH_ASSOC)) {
    $total_aprobados = $row['total'];
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Validación</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 15px;
        }
        
        .header-card {
            background: linear-gradient(135deg, #1e88e5, #1565c0);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .filter-btn {
            text-decoration: none;
            color: #333;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .filter-btn.active {
            background: #1e88e5;
            color: white;
            border-color: #1e88e5;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .badge-estado {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-aprobado {
            background-color: #e8f5e9;
            color: #43a047;
            border: 1px solid #c8e6c9;
        }
        
        .badge-pendiente {
            background-color: #fff3e0;
            color: #ef6c00;
            border: 1px solid #ffe0b2;
        }
        
        .btn-validate {
            background: #43a047;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .btn-validate:hover {
            background: #2e7d32;
            transform: translateY(-1px);
        }
        
        .btn-reject {
            background: #e53935;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-reject:hover {
            background: #c62828;
            transform: translateY(-1px);
        }
        
        .btn-disabled {
            background: #e0e0e0;
            color: #9e9e9e;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            cursor: not-allowed;
        }
        
        .selected-row {
            background-color: rgba(33, 150, 243, 0.1) !important;
            border-left: 3px solid #1e88e5 !important;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .action-toggle {
            display: flex;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .action-toggle-btn {
            flex: 1;
            padding: 8px 15px;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .action-toggle-btn.active {
            background: #1e88e5;
            color: white;
        }
        
        .action-toggle-btn:first-child {
            border-right: 1px solid #dee2e6;
        }
        
        .action-toggle-btn.aprobar.active {
            background: #43a047;
        }
        
        .action-toggle-btn.rechazar.active {
            background: #e53935;
        }
        
        /* Estilos para el modal moderno */
        .modal-confirmacion {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content-confirm {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transform: translateY(0);
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header-confirm {
            padding: 25px 30px 15px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .modal-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .modal-title-confirm {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .modal-body-confirm {
            padding: 25px 30px;
            text-align: center;
        }
        
        .modal-message {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .modal-details {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-label {
            flex: 0 0 120px;
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .modal-footer-confirm {
            padding: 20px 30px 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .btn-modal {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-modal-cancel {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #dee2e6;
        }
        
        .btn-modal-cancel:hover {
            background: #e9ecef;
            border-color: #ced4da;
            transform: translateY(-2px);
        }
        
        .btn-modal-confirm {
            background: linear-gradient(135deg, #43a047, #2e7d32);
            color: white;
            border: 2px solid #43a047;
        }
        
        .btn-modal-confirm:hover {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 160, 71, 0.3);
        }
        
        .btn-modal-reject {
            background: linear-gradient(135deg, #e53935, #c62828);
            color: white;
            border: 2px solid #e53935;
        }
        
        .btn-modal-reject:hover {
            background: linear-gradient(135deg, #c62828, #b71c1c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 57, 53, 0.3);
        }
        
        .badge-accion {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        /* Estilos para las celdas de causa con tooltip COMPACTO */
        .causa-cell {
            cursor: pointer;
            position: relative;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding: 8px;
        }
        
        .causa-cell:hover {
            background-color: rgba(33, 150, 243, 0.05);
        }
        
        /* Tooltip personalizado COMPACTO */
        .tooltip-descripcion {
            display: none;
            position: fixed;
            background: rgba(0, 0, 0, 0.92);
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            max-width: 400px;
            z-index: 10000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(3px);
            animation: fadeInTooltip 0.15s ease;
            font-size: 13px;
            line-height: 1.4;
            word-wrap: break-word;
            white-space: normal;
            pointer-events: none;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        
        @keyframes fadeInTooltip {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tooltip-descripcion.show {
            display: block;
        }
        
        .tooltip-arrow {
            position: absolute;
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 6px solid rgba(0, 0, 0, 0.92);
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .text-truncate {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .tooltip-header {
            font-weight: 700;
            color: #4c6ef5;
            margin-bottom: 6px;
            font-size: 13px;
            display: block;
            padding-bottom: 4px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .tooltip-info-item {
            margin-bottom: 5px;
            display: flex;
            align-items: flex-start;
        }
        
        .tooltip-info-item:last-child {
            margin-bottom: 0;
        }
        
        .tooltip-label {
            font-weight: 600;
            color: #a5d8ff;
            font-size: 12px;
            min-width: 65px;
            flex-shrink: 0;
        }
        
        .tooltip-value {
            color: #ffffff;
            font-weight: 400;
            font-size: 12px;
            flex: 1;
            word-break: break-word;
        }
        
        .tooltip-descripcion-text {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed rgba(255, 255, 255, 0.15);
        }
        
        .descripcion-label {
            font-weight: 600;
            color: #a5d8ff;
            font-size: 12px;
            margin-bottom: 3px;
            display: block;
        }
        
        .descripcion-content {
            color: #ffffff;
            font-size: 12px;
            line-height: 1.4;
            max-height: 150px;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .descripcion-content::-webkit-scrollbar {
            width: 4px;
        }
        
        .descripcion-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
        
        .descripcion-content::-webkit-scrollbar-thumb {
            background: #4c6ef5;
            border-radius: 2px;
        }
        
        .causa-cell:hover::after {
            content: "Ver detalles";
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            white-space: nowrap;
            z-index: 100;
            pointer-events: none;
            animation: fadeIn 0.2s ease;
            font-weight: 500;
        }
        
        .causa-cell:hover::before {
            content: "";
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #333;
            z-index: 100;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert alert-<?php echo $tipo_mensaje == 'success' ? 'success' : ($tipo_mensaje == 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show" role="alert">
        <?php if ($tipo_mensaje == 'success'): ?>
            <i class="fas fa-check-circle me-2"></i>
        <?php elseif ($tipo_mensaje == 'warning'): ?>
            <i class="fas fa-exclamation-triangle me-2"></i>
        <?php else: ?>
            <i class="fas fa-exclamation-circle me-2"></i>
        <?php endif; ?>
        <?php echo $mensaje_exito; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h4 mb-2">
                        <i class="fas fa-clipboard-check me-2"></i>Validación de Cancelaciones
                    </h1>
                    <p class="mb-0 opacity-75">
                        Sistema de Gestión - Comedor Industrial
                        <?php if($rol): ?>
                        <br><small><i class="fas fa-user-tag me-1"></i> Rol: <?php echo htmlspecialchars($rol); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="http://192.168.100.95/Comedor" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filter-card">
            <h5 class="mb-3 text-dark"><i class="fas fa-filter me-2"></i>Filtrar por Estado</h5>
            <div class="d-flex gap-3 flex-wrap">
                <a href="?filtro=pendientes<?php echo $rol ? '&newpwd=' . htmlspecialchars($rol) : ''; ?>" 
                   class="filter-btn <?php echo $filtro_estado == 'pendientes' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    Pendientes
                    <span class="badge bg-warning ms-2"><?php echo $total_pendientes; ?></span>
                </a>
                
                <a href="?filtro=aprobados<?php echo $rol ? '&newpwd=' . htmlspecialchars($rol) : ''; ?>" 
                   class="filter-btn <?php echo $filtro_estado == 'aprobados' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    Aprobados
                    <span class="badge bg-success ms-2"><?php echo $total_aprobados; ?></span>
                </a>
            </div>
            
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <?php 
                    if ($filtro_estado == 'aprobados') {
                        if ($rol == 'Administrador') {
                            echo 'Mostrando registros APROBADOS por Administrador';
                        } elseif ($rol == 'Coordinador') {
                            echo 'Mostrando registros APROBADOS por Coordinador';
                        } else {
                            echo 'Mostrando registros APROBADOS (Admin o Coord)';
                        }
                    } else {
                        if ($rol == 'Administrador') {
                            echo 'Mostrando registros PENDIENTES de validación por Administrador';
                        } elseif ($rol == 'Coordinador') {
                            echo 'Mostrando registros PENDIENTES de validación por Coordinador';
                        } else {
                            echo 'Mostrando registros PENDIENTES de validación';
                        }
                    }
                    ?>
                    <br>
                    <i class="fas fa-database me-1"></i> Registros mostrados: <?php echo count($registros); ?>
                    <br>
                    <i class="fas fa-info-circle me-1"></i> Los registros RECHAZADOS no se muestran en la tabla
                    <br>
                    <i class="fas fa-mouse-pointer me-1"></i> Pase el cursor sobre la columna "Causa" para ver <strong>Nombre, Fecha y Descripción</strong>
                </small>
            </div>
        </div>
        
        <!-- Formulario de validación en lote -->
        <div class="filter-card">
            <h5 class="mb-3 text-dark"><i class="fas fa-sliders-h me-2"></i>Validación Rápida</h5>
            <form method="post" action="" id="validationForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Tipo de Validación</label>
                        <select id="TIPOVALIDA" name="TIPOVALIDA" class="form-select form-select-sm" required>
                            <option value="MULTIPLE">Validación Múltiple</option>
                            <option value="UNICA">Validación Única</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Departamento</label>
                        <select id="DEPARTAMENTO" name="DEPARTAMENTO" class="form-select form-select-sm">
                            <option value="">Seleccionar</option>
                            <option>Operaciones</option>
                            <option>Talento Humano</option>
                            <option>Finanzas</option>
                            <option>Administración</option>
                            <option>Auditoría</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="name123" name="name123" 
                               placeholder="Selecciona de la tabla" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Fecha</label>
                        <input type="text" class="form-control form-control-sm" id="name1234" name="name1234" 
                               placeholder="Selecciona de la tabla" readonly>
                    </div>
                    
                    <!-- Selector de acción (Aprobar/Rechazar) -->
                    <div class="col-12">
                        <div class="action-toggle">
                            <button type="button" class="action-toggle-btn aprobar active" data-action="aprobar">
                                <i class="fas fa-check me-1"></i> Aprobar
                            </button>
                            <button type="button" class="action-toggle-btn rechazar" data-action="rechazar">
                                <i class="fas fa-times me-1"></i> Rechazar
                            </button>
                        </div>
                        <input type="hidden" name="accion_lote" id="accionLote" value="aprobar">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="validar_lote" class="btn btn-success w-100 btn-sm">
                            <i class="fas fa-play-circle me-2"></i> Ejecutar Validación
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabla de registros -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-dark mb-0">
                    <i class="fas fa-database me-2"></i>Registros
                </h5>
                <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> Actualizar
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="tablaRegistros">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Jefe Inmediato</th>
                            <th>Tipo Consumo</th>
                            <th>Fecha</th>
                            <th>Causa</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-database fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No hay registros encontrados</p>
                                <small class="text-info">Filtro: <?php echo $filtro_estado; ?></small>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($registros as $index => $registro): ?>
                        <?php 
                            $estatus = strtoupper(trim($registro['Estatus'] ?? ''));
                            $valJef = strtoupper(trim($registro['ValJefDirect'] ?? ''));
                            
                            // DETERMINAR ESTADO VISUAL
                            if ($filtro_estado == 'aprobados') {
                                $badge_class = 'badge-aprobado';
                                $badge_text = 'APROBADO';
                                $badge_icon = 'fa-check-circle';
                            } else {
                                if ($estatus == 'APROBADO' || $valJef == 'APROBADO') {
                                    $badge_class = 'badge-aprobado';
                                    $badge_text = 'APROBADO';
                                    $badge_icon = 'fa-check-circle';
                                } else {
                                    $badge_class = 'badge-pendiente';
                                    $badge_text = 'PENDIENTE';
                                    $badge_icon = 'fa-clock';
                                }
                            }
                            
                            // Determinar si se puede validar/rechazar según rol y filtro
                            $puede_aprobar = false;
                            $puede_rechazar = false;
                            
                            if ($filtro_estado == 'pendientes') {
                                if ($rol == 'Administrador' && $estatus != 'APROBADO' && $estatus != 'RECHAZADO') {
                                    $puede_aprobar = true;
                                    $puede_rechazar = true;
                                } elseif ($rol == 'Coordinador' && $valJef != 'APROBADO' && $valJef != 'RECHAZADO') {
                                    $puede_aprobar = true;
                                    $puede_rechazar = true;
                                }
                            }
                            
                            // Generar ID único para los formularios
                            $form_aprobar_id = 'formAprobar_' . $index;
                            $form_rechazar_id = 'formRechazar_' . $index;
                        ?>
                        <tr class="row-selectable"
                            data-nombre="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-fecha="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-departamento="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <td><?php echo $registro['NOMBRE'] ?? ''; ?></td>
                            <td><?php echo $registro['DEPARTAMENTO'] ?? ''; ?></td>
                            <td><?php echo $registro['JEFE'] ?? ''; ?></td>
                            <td><?php echo $registro['TIPO_CONSUMO'] ?? ''; ?></td>
                            <td><?php echo $registro['FECHA'] ?? ''; ?></td>
                            <td class="causa-cell" 
                                data-nombre="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-fecha="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-descripcion="<?php echo htmlspecialchars($registro['DESCRIPCION_DETALLE'] ?? $registro['DESCRIPCION'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                onmouseenter="mostrarTooltipCompleto(event, this)"
                                onmouseleave="ocultarTooltipDescripcion()">
                                <div class="text-truncate" style="max-width: 200px;">
                                    <?php 
                                    $causa = $registro['CAUSA'] ?? '';
                                    echo !empty($causa) ? $causa : '<span class="text-muted fst-italic">Sin causa especificada</span>';
                                    ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-estado <?php echo $badge_class; ?>">
                                    <i class="fas <?php echo $badge_icon; ?> me-1"></i>
                                    <?php echo $badge_text; ?>
                                </span>
                                <br>
                                <small class="text-muted" style="font-size: 10px;">
                                    <?php 
                                    if ($estatus == 'APROBADO') echo '✓ Admin'; 
                                    elseif ($estatus == 'RECHAZADO') echo '✗ Admin';
                                    
                                    if ($valJef == 'APROBADO') echo ' ✓ Coord';
                                    elseif ($valJef == 'RECHAZADO') echo ' ✗ Coord';
                                    ?>
                                </small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($puede_aprobar): ?>
                                    <!-- Formulario para APROBAR -->
                                    <form method="post" action="" id="<?php echo $form_aprobar_id; ?>" style="display: inline;">
                                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="departamento" value="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="accion" value="aprobar">
                                        <input type="hidden" name="validar_individual" value="1">
                                        <button type="button" class="btn-validate" onclick="mostrarConfirmacion('<?php echo $form_aprobar_id; ?>', 'aprobar', '<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fas fa-check me-1"></i> Aprobar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($puede_rechazar): ?>
                                    <!-- Formulario para RECHAZAR -->
                                    <form method="post" action="" id="<?php echo $form_rechazar_id; ?>" style="display: inline;">
                                        <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="departamento" value="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="accion" value="rechazar">
                                        <input type="hidden" name="validar_individual" value="1">
                                        <button type="button" class="btn-reject" onclick="mostrarConfirmacion('<?php echo $form_rechazar_id; ?>', 'rechazar', '<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fas fa-times me-1"></i> Rechazar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if (!$puede_aprobar && !$puede_rechazar): ?>
                                    <button type="button" class="btn-disabled" disabled>
                                        <?php if ($filtro_estado == 'aprobados'): ?>
                                            <i class="fas fa-check me-1"></i> Ya Aprobado
                                        <?php else: ?>
                                            <i class="fas fa-lock me-1"></i> No Disponible
                                        <?php endif; ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Tooltip para información completa -->
    <div class="tooltip-descripcion" id="tooltipDescripcion">
        <div id="tooltipContent"></div>
        <div class="tooltip-arrow"></div>
    </div>
    
    <!-- Modal de confirmación moderno -->
    <div class="modal-confirmacion" id="modalConfirmacion">
        <div class="modal-content-confirm">
            <div class="modal-header-confirm">
                <div class="modal-icon" id="modalIcon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3 class="modal-title-confirm" id="modalTitle">Confirmar Acción</h3>
            </div>
            
            <div class="modal-body-confirm">
                <div class="modal-message" id="modalMessage">
                    ¿Estás seguro de realizar esta acción?
                </div>
                
                <div class="modal-details">
                    <div class="detail-row">
                        <div class="detail-label">Nombre:</div>
                        <div class="detail-value" id="detailNombre">-</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Fecha:</div>
                        <div class="detail-value" id="detailFecha">-</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Departamento:</div>
                        <div class="detail-value" id="detailDepartamento">-</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Rol:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($rol); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Acción:</div>
                        <div class="detail-value">
                            <span class="badge-accion" id="detailAccion">-</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer-confirm">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModal()">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn-modal" id="btnConfirmar">
                    <i class="fas fa-check me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variables globales
        let currentFormId = '';
        let tooltipVisible = false;
        let tooltipTimeout = null;
        
        // Inicializar cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle acción (Aprobar/Rechazar) en formulario de lote
            const actionToggleButtons = document.querySelectorAll('.action-toggle-btn');
            if (actionToggleButtons.length > 0) {
                actionToggleButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const action = this.dataset.action;
                        
                        // Actualizar botones toggle
                        document.querySelectorAll('.action-toggle-btn').forEach(b => {
                            b.classList.remove('active', 'aprobar', 'rechazar');
                            if (b.dataset.action === action) {
                                b.classList.add('active');
                                if (action === 'aprobar') {
                                    b.classList.add('aprobar');
                                } else {
                                    b.classList.add('rechazar');
                                }
                            }
                        });
                        
                        // Actualizar campo oculto
                        const accionLoteInput = document.getElementById('accionLote');
                        if (accionLoteInput) {
                            accionLoteInput.value = action;
                        }
                        
                        // Cambiar color del botón principal
                        const submitBtn = document.querySelector('button[name="validar_lote"]');
                        if (submitBtn) {
                            if (action === 'aprobar') {
                                submitBtn.className = 'btn btn-success w-100 btn-sm';
                                submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Ejecutar Aprobación';
                            } else {
                                submitBtn.className = 'btn btn-danger w-100 btn-sm';
                                submitBtn.innerHTML = '<i class="fas fa-times-circle me-2"></i> Ejecutar Rechazo';
                            }
                        }
                    });
                });
            }
            
            // Seleccionar filas
            const selectableRows = document.querySelectorAll('.row-selectable');
            if (selectableRows.length > 0) {
                selectableRows.forEach(row => {
                    row.addEventListener('click', function() {
                        // Quitar selección anterior
                        document.querySelectorAll('.row-selectable').forEach(r => {
                            r.classList.remove('selected-row');
                        });
                        
                        // Seleccionar esta fila
                        this.classList.add('selected-row');
                        
                        // Actualizar formulario de lote
                        const nombre = this.dataset.nombre;
                        const fecha = this.dataset.fecha;
                        const departamento = this.dataset.departamento;
                        
                        const name123Input = document.getElementById('name123');
                        const name1234Input = document.getElementById('name1234');
                        const departamentoSelect = document.getElementById('DEPARTAMENTO');
                        
                        if (name123Input) name123Input.value = nombre || '';
                        if (name1234Input) name1234Input.value = fecha || '';
                        if (departamentoSelect) departamentoSelect.value = departamento || '';
                    });
                });
            }
            
            // Cambiar tipo de validación
            const tipoValidaSelect = document.getElementById('TIPOVALIDA');
            if (tipoValidaSelect) {
                tipoValidaSelect.addEventListener('change', function() {
                    const departamentoSelect = document.getElementById('DEPARTAMENTO');
                    const name123Input = document.getElementById('name123');
                    const name1234Input = document.getElementById('name1234');
                    
                    if (this.value === 'UNICA') {
                        if (departamentoSelect) departamentoSelect.value = '';
                    } else {
                        if (name123Input) name123Input.value = '';
                        if (name1234Input) name1234Input.value = '';
                    }
                });
            }
            
            // Configurar botón de confirmación del modal
            const btnConfirmar = document.getElementById('btnConfirmar');
            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', function() {
                    if (currentFormId) {
                        const formElement = document.getElementById(currentFormId);
                        if (formElement) {
                            formElement.submit();
                        }
                    }
                    cerrarModal();
                });
            }
            
            // Cerrar modal al hacer clic fuera
            const modal = document.getElementById('modalConfirmacion');
            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        cerrarModal();
                    }
                });
            }
            
            // Cerrar modal con Escape
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    cerrarModal();
                    ocultarTooltipDescripcion();
                }
            });
            
            // Ocultar tooltip al hacer scroll
            window.addEventListener('scroll', function() {
                ocultarTooltipDescripcion();
            });
        });
        
        // Función para mostrar el tooltip COMPACTO
        function mostrarTooltipCompleto(event, element) {
            // Limpiar timeout anterior si existe
            if (tooltipTimeout) {
                clearTimeout(tooltipTimeout);
            }
            
            // Mostrar tooltip después de un breve retraso (mejor UX)
            tooltipTimeout = setTimeout(function() {
                // Usar el elemento pasado como parámetro o el event.currentTarget
                const cell = element || event.currentTarget;
                
                // Verificar que el elemento existe y tiene los atributos necesarios
                if (!cell || !cell.dataset) {
                    ocultarTooltipDescripcion();
                    return;
                }
                
                const nombre = cell.dataset.nombre || 'No disponible';
                const fecha = cell.dataset.fecha || 'No disponible';
                const descripcion = cell.dataset.descripcion || 'Sin descripción disponible';
                
                // Obtener elementos del tooltip
                const tooltip = document.getElementById('tooltipDescripcion');
                const tooltipContent = document.getElementById('tooltipContent');
                
                if (!tooltip || !tooltipContent) return;
                
                // Construir el HTML del tooltip COMPACTO
                let tooltipHTML = `
                    <span class="tooltip-header">📋 Información del Registro</span>
                    
                    <div class="tooltip-info-item">
                        <span class="tooltip-label">Nombre:</span>
                        <span class="tooltip-value">${nombre}</span>
                    </div>
                    
                    <div class="tooltip-info-item">
                        <span class="tooltip-label">Fecha:</span>
                        <span class="tooltip-value">${fecha}</span>
                    </div>
                    
                    <div class="tooltip-descripcion-text">
                        <span class="descripcion-label">Descripción:</span>
                        <div class="descripcion-content">${descripcion}</div>
                    </div>
                `;
                
                tooltipContent.innerHTML = tooltipHTML;
                
                // Obtener posición del cursor
                let x, y;
                
                // Usar las coordenadas del evento si están disponibles
                if (event && event.clientX && event.clientY) {
                    x = event.clientX;
                    y = event.clientY;
                } else {
                    // Si no hay coordenadas del evento, posicionar cerca del elemento
                    const rect = cell.getBoundingClientRect();
                    x = rect.left + rect.width / 2;
                    y = rect.top;
                }
                
                // Mostrar tooltip temporalmente para calcular dimensiones
                tooltip.style.visibility = 'hidden';
                tooltip.style.display = 'block';
                
                // Obtener dimensiones del tooltip
                const tooltipWidth = tooltip.offsetWidth;
                const tooltipHeight = tooltip.offsetHeight;
                const windowWidth = window.innerWidth;
                const windowHeight = window.innerHeight;
                
                // Calcular posición inicial (centrado sobre el cursor)
                let left = x - (tooltipWidth / 2);
                let top = y - tooltipHeight - 10;
                
                // Ajustar para no salir de la pantalla por la izquierda
                if (left < 10) {
                    left = 10;
                }
                
                // Ajustar para no salir de la pantalla por la derecha
                if (left + tooltipWidth > windowWidth - 10) {
                    left = windowWidth - tooltipWidth - 10;
                }
                
                // Ajustar para no salir por arriba (mostrar abajo)
                if (top < 10) {
                    top = y + 20;
                }
                
                // Ajustar para no salir por abajo
                if (top + tooltipHeight > windowHeight - 10) {
                    top = windowHeight - tooltipHeight - 10;
                }
                
                // Aplicar posición final
                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
                tooltip.style.visibility = 'visible';
                tooltip.classList.add('show');
                tooltipVisible = true;
                
            }, 200); // 200ms de retraso para evitar tooltips molestos
        }
        
        // Función para ocultar el tooltip de descripción
        function ocultarTooltipDescripcion() {
            if (tooltipTimeout) {
                clearTimeout(tooltipTimeout);
                tooltipTimeout = null;
            }
            
            const tooltip = document.getElementById('tooltipDescripcion');
            if (tooltip) {
                tooltip.classList.remove('show');
                tooltip.style.display = 'none';
                tooltipVisible = false;
            }
        }
        
        // Función para mostrar el modal de confirmación
        function mostrarConfirmacion(formId, accion, nombre, fecha, departamento) {
            // Guardar el ID del formulario
            currentFormId = formId;
            
            // Obtener elementos del modal
            const modal = document.getElementById('modalConfirmacion');
            const modalIcon = document.getElementById('modalIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const detailNombre = document.getElementById('detailNombre');
            const detailFecha = document.getElementById('detailFecha');
            const detailDepartamento = document.getElementById('detailDepartamento');
            const detailAccion = document.getElementById('detailAccion');
            const btnConfirmar = document.getElementById('btnConfirmar');
            
            // Configurar según la acción
            if (accion === 'aprobar') {
                // Aprobación - Estilo verde
                modalIcon.innerHTML = '<i class="fas fa-check-circle" style="color: #43a047;"></i>';
                modalTitle.textContent = 'Confirmar Aprobación';
                modalMessage.textContent = '¿Estás seguro de APROBAR este registro?';
                detailAccion.innerHTML = '<span class="badge-accion" style="background-color: #e8f5e9; color: #43a047; border: 1px solid #c8e6c9;">APROBAR</span>';
                btnConfirmar.className = 'btn-modal btn-modal-confirm';
                btnConfirmar.innerHTML = '<i class="fas fa-check me-1"></i> Sí, Aprobar';
            } else {
                // Rechazo - Estilo rojo
                modalIcon.innerHTML = '<i class="fas fa-times-circle" style="color: #e53935;"></i>';
                modalTitle.textContent = 'Confirmar Rechazo';
                modalMessage.textContent = '¿Estás seguro de RECHAZAR este registro?';
                detailAccion.innerHTML = '<span class="badge-accion" style="background-color: #ffebee; color: #e53935; border: 1px solid #ffcdd2;">RECHAZAR</span>';
                btnConfirmar.className = 'btn-modal btn-modal-reject';
                btnConfirmar.innerHTML = '<i class="fas fa-times me-1"></i> Sí, Rechazar';
            }
            
            // Llenar detalles
            detailNombre.textContent = nombre || '-';
            detailFecha.textContent = fecha || '-';
            detailDepartamento.textContent = departamento || '-';
            
            // Mostrar modal
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevenir scroll
            }
        }
        
        // Función para cerrar el modal
        function cerrarModal() {
            const modal = document.getElementById('modalConfirmacion');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Restaurar scroll
            }
        }
        
        // Auto-ocultar alertas después de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (e) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    </script>
</body>
</html>