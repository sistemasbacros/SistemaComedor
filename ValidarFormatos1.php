<?php
// ----------------------- PHP BACKEND ---------------------------
// Configuraci車n completa de encoding UTF-8
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Configurar locale para espa?ol
setlocale(LC_ALL, 'es_ES.UTF-8', 'spanish');
date_default_timezone_set('America/Mexico_City');

// Conexi車n a SQL Server
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
    die("<div class='alert alert-danger'>Error de conexi車n a la base de datos</div>");
}

// Obtener filtro de estado desde GET
$filtro_estado = isset($_GET['filtro']) ? $_GET['filtro'] : 'pendientes';

// Construir consulta SQL seg迆n filtro
$sql_base = "SELECT * FROM cancelaciones WHERE 1=1";
$sql_conditions = "";

switch($filtro_estado) {
    case 'pendientes':
        $sql_conditions = " AND (Estatus != 'APROBADO' OR Estatus IS NULL)";
        break;
    case 'aprobados':
        $sql_conditions = " AND Estatus = 'APROBADO'";
        break;
}

// Agregar condici車n de fecha reciente (迆ltimos 3 meses)
$sql_conditions .= " AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";

// Consulta final
$sql = $sql_base . $sql_conditions . " ORDER BY FECHA DESC";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die("<div class='alert alert-danger'>Error en la consulta</div>");
}

// Procesar datos
$registros = array();
$contadores = array(
    'pendientes' => 0,
    'aprobados' => 0
);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Normalizar datos y asegurar UTF-8
    foreach ($row as $key => $value) {
        if (is_string($value)) {
            $value = trim($value);
            // Detectar encoding y convertir a UTF-8
            $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding != 'UTF-8') {
                $value = mb_convert_encoding($value, 'UTF-8', $encoding);
            }
            $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    $registros[] = $row;
    
    // Determinar estado (convertir a may迆sculas y limpiar)
    $estatus = strtoupper(trim($row['Estatus'] ?? ''));
    
    // Contar aprobados vs pendientes
    if ($estatus == 'APROBADO') {
        $contadores['aprobados']++;
    } else {
        $contadores['pendientes']++;
    }
}

// Procesar validaciones POST
$mensaje_exito = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Procesar validaci車n individual
    if (isset($_POST['validar_individual'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha = trim($_POST['fecha'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $rol = $_GET['newpwd'] ?? '';
        
        if (!empty($nombre) && !empty($fecha) && !empty($rol)) {
            // Solo Administrador puede aprobar (cambiar Estatus a APROBADO)
            if ($rol == 'Administrador') {
                $sql_update = "UPDATE cancelaciones SET Estatus = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                $mensaje_exito = "? Validaci車n de Administrador completada para: " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
            } 
            // Coordinador aprueba en la columna ValJefDirect
            elseif ($rol == 'Coordinador') {
                $sql_update = "UPDATE cancelaciones SET ValJefDirect = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                $mensaje_exito = "? Validaci車n de Coordinador completada para: " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
            }
            
            if (isset($sql_update)) {
                $params = array($nombre, $fecha);
                $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                if (sqlsrv_execute($stmt_update)) {
                    $tipo_mensaje = 'success';
                    // Redirigir para evitar reenv赤o
                    $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=success";
                    if (isset($_GET['newpwd'])) {
                        $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
                    }
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    $mensaje_exito = "? Error al validar el registro";
                    $tipo_mensaje = 'danger';
                }
            }
        } else {
            $mensaje_exito = "? Datos incompletos para la validaci車n";
            $tipo_mensaje = 'danger';
        }
    }
    
    // Procesar validaci車n m迆ltiple
    if (isset($_POST['validar_lote'])) {
        $tipo_validacion = $_POST['TIPOVALIDA'] ?? '';
        $departamento = $_POST['DEPARTAMENTO'] ?? '';
        $nombre_lote = $_POST['name123'] ?? '';
        $fecha_lote = $_POST['name1234'] ?? '';
        $rol = $_GET['newpwd'] ?? '';
        
        if (!empty($rol)) {
            if ($tipo_validacion == 'UNICA') {
                // Validaci車n 迆nica
                if (!empty($nombre_lote) && !empty($fecha_lote)) {
                    if ($rol == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                    } elseif ($rol == 'Coordinador') {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                    }
                    
                    if (isset($sql_update)) {
                        $params = array($nombre_lote, $fecha_lote);
                        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                        if (sqlsrv_execute($stmt_update)) {
                            $mensaje_exito = "? Validaci車n 迆nica completada para: " . htmlspecialchars($nombre_lote, ENT_QUOTES, 'UTF-8');
                            $tipo_mensaje = 'success';
                        } else {
                            $mensaje_exito = "? Error al validar el registro";
                            $tipo_mensaje = 'danger';
                        }
                    }
                } else {
                    $mensaje_exito = "? Por favor, selecciona un registro de la tabla";
                    $tipo_mensaje = 'danger';
                }
            } else {
                // Validaci車n m迆ltiple por departamento
                if (!empty($departamento)) {
                    if ($rol == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = 'APROBADO' WHERE DEPARTAMENTO LIKE ? AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
                    } elseif ($rol == 'Coordinador') {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = 'APROBADO' WHERE DEPARTAMENTO LIKE ? AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
                    }
                    
                    if (isset($sql_update)) {
                        $params = array('%' . $departamento . '%');
                        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                        if (sqlsrv_execute($stmt_update)) {
                            $rows_affected = sqlsrv_rows_affected($stmt_update);
                            $mensaje_exito = "? Validaci車n m迆ltiple completada para departamento: " . htmlspecialchars($departamento, ENT_QUOTES, 'UTF-8') . " ($rows_affected registros actualizados)";
                            $tipo_mensaje = 'success';
                        } else {
                            $mensaje_exito = "? Error al validar los registros";
                            $tipo_mensaje = 'danger';
                        }
                    }
                } else {
                    $mensaje_exito = "? Por favor, selecciona un departamento";
                    $tipo_mensaje = 'danger';
                }
            }
            
            // Redirigir si hubo 谷xito o error
            if ($tipo_mensaje == 'success' || $tipo_mensaje == 'danger') {
                $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=" . $tipo_mensaje;
                if (isset($_GET['newpwd'])) {
                    $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
                }
                header("Location: " . $redirect_url);
                exit();
            }
        } else {
            $mensaje_exito = "? No se ha especificado el rol de usuario";
            $tipo_mensaje = 'danger';
            
            $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=" . $tipo_mensaje;
            if (isset($_GET['newpwd'])) {
                $redirect_url .= "&newpwd=" . urlencode($_GET['newpwd']);
            }
            header("Location: " . $redirect_url);
            exit();
        }
    }
}

// Mostrar mensaje de 谷xito si viene por GET
if (isset($_GET['mensaje'])) {
    $mensaje_exito = urldecode($_GET['mensaje']);
    $tipo_mensaje = $_GET['tipo'] ?? 'success';
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validaci車n de Cancelaciones - Sistema Comedor</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1E4E79;
            --secondary-color: #2D6DA6;
            --accent-color: #4a90e2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            padding: 15px;
        }
        
        /* Header Styles */
        .main-header {
            background: white;
            color: var(--primary-color);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--primary-color);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .header-title h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }
        
        .header-title p {
            font-size: 0.8rem;
            color: var(--dark-color);
            margin: 3px 0 0 0;
            opacity: 0.8;
        }
        
        /* Filter Buttons */
        .filters-section {
            background: white;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-color);
        }
        
        .filters-section h5 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: white;
            color: var(--dark-color);
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-btn .badge {
            font-size: 0.75rem;
            padding: 3px 7px;
        }
        
        .filter-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 10px;
            padding: 5px 0;
            border-top: 1px solid var(--border-color);
        }
        
        /* Validation Modal */
        .modal-validation {
            border-radius: 8px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 8px 8px 0 0;
            padding: 12px 20px;
        }
        
        .modal-title {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .validation-details {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }
        
        .detail-item {
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--primary-color);
            min-width: 120px;
        }
        
        .detail-value {
            flex: 1;
            color: var(--dark-color);
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-color);
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .table-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        /* DataTable Styles */
        table.dataTable {
            font-size: 0.85rem;
            border-collapse: separate !important;
            border-spacing: 0;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
        }
        
        table.dataTable thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            color: white !important;
            border: none !important;
            padding: 10px 8px !important;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        table.dataTable tbody td {
            padding: 8px !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color) !important;
        }
        
        table.dataTable tbody tr:hover {
            background-color: rgba(30, 78, 121, 0.04) !important;
        }
        
        table.dataTable tbody tr.selected {
            background-color: rgba(30, 78, 121, 0.08) !important;
            border-left: 3px solid var(--primary-color);
        }
        
        /* Status Badges */
        .badge-estado {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        
        .badge-aprobado {
            background-color: rgba(40, 167, 69, 0.12);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.25);
        }
        
        .badge-pendiente {
            background-color: rgba(255, 193, 7, 0.12);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.25);
        }
        
        /* Action Buttons */
        .btn-action {
            padding: 4px 10px;
            font-size: 0.8rem;
            border-radius: 4px;
            border: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-validate {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-validate:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        .btn-disabled {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        /* Validation Form */
        .validation-form {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border-color);
        }
        
        .form-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 12px;
        }
        
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .form-control, .form-select {
            font-size: 0.85rem;
            padding: 6px 10px;
            height: 36px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(74, 144, 226, 0.2);
        }
        
        /* Alert Messages */
        .alert-message {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
            min-width: 300px;
            max-width: 400px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
                font-size: 13px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .filter-btn {
                width: 100%;
                justify-content: center;
            }
            
            .table-container {
                padding: 12px;
            }
            
            table.dataTable thead th,
            table.dataTable tbody td {
                padding: 6px 4px !important;
                font-size: 0.8rem;
            }
            
            .alert-message {
                left: 10px;
                right: 10px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <!-- Alert Messages -->
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert-message">
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php if ($tipo_mensaje == 'success'): ?>
                <i class="fas fa-check-circle me-2"></i>
            <?php elseif ($tipo_mensaje == 'danger'): ?>
                <i class="fas fa-exclamation-circle me-2"></i>
            <?php else: ?>
                <i class="fas fa-info-circle me-2"></i>
            <?php endif; ?>
            <?php echo $mensaje_exito; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Header -->
    <div class="main-header">
        <div class="header-content">
            <div class="header-title">
                <h1><i class="fas fa-clipboard-check me-2"></i>Validaci車n de Cancelaciones</h1>
                <p>Sistema de Gesti車n - Comedor Industrial</p>
            </div>
            <div>
                <a href="http://192.168.100.95/Comedor" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-chevron-left me-1"></i> Volver al Men迆
                </a>
            </div>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="filters-section">
        <h5><i class="fas fa-filter me-2"></i>Filtrar por Estado</h5>
        <div class="filter-buttons">
            <a href="?filtro=pendientes<?php echo isset($_GET['newpwd']) ? '&newpwd=' . htmlspecialchars($_GET['newpwd']) : ''; ?>" 
               class="filter-btn <?php echo $filtro_estado == 'pendientes' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pendientes
                <span class="badge bg-warning"><?php echo $contadores['pendientes']; ?></span>
            </a>
            <a href="?filtro=aprobados<?php echo isset($_GET['newpwd']) ? '&newpwd=' . htmlspecialchars($_GET['newpwd']) : ''; ?>" 
               class="filter-btn <?php echo $filtro_estado == 'aprobados' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Aprobados
                <span class="badge bg-success"><?php echo $contadores['aprobados']; ?></span>
            </a>
        </div>
        <div class="filter-info">
            <i class="fas fa-info-circle me-1"></i>
            <?php 
            if ($filtro_estado == 'aprobados') {
                echo 'Mostrando registros con Estatus = APROBADO';
            } else {
                echo 'Mostrando registros pendientes de validaci車n (Estatus ≧ APROBADO)';
            }
            ?>
            <?php if(isset($_GET['newpwd'])): ?>
                <br><small><i class="fas fa-user me-1"></i> Rol actual: <?php echo htmlspecialchars($_GET['newpwd'], ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Validation Form -->
    <div class="validation-form">
        <h5 class="form-title"><i class="fas fa-sliders-h me-2"></i>Filtros de Validaci車n</h5>
        <form method="post" action="" id="validationForm">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Tipo de Validaci車n</label>
                    <select id="TIPOVALIDA" name="TIPOVALIDA" class="form-select" required>
                        <option value="MULTIPLE">Validaci車n M迆ltiple</option>
                        <option value="UNICA">Validaci車n 迆nica</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Departamento</label>
                    <select id="DEPARTAMENTO" name="DEPARTAMENTO" class="form-select">
                        <option value="">Seleccionar Departamento</option>
                        <option>Operaciones</option>
                        <option>Talento Humano</option>
                        <option>Finanzas</option>
                        <option>Administraci車n</option>
                        <option>Auditor赤a</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="name123" name="name123" 
                           placeholder="Selecciona de la tabla" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="text" class="form-control" id="name1234" name="name1234" 
                           placeholder="Selecciona de la tabla" readonly>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="validar_lote" class="btn btn-primary w-100">
                    <i class="fas fa-play-circle me-2"></i> Ejecutar Validaci車n
                </button>
            </div>
        </form>
    </div>
    
    <!-- Table Container -->
    <div class="table-container">
        <div class="table-header">
            <h5 class="table-title"><i class="fas fa-database me-2"></i>Registros de Cancelaciones</h5>
            <div>
                <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
                    <i class="fas fa-redo"></i> Actualizar
                </button>
            </div>
        </div>
        <p class="text-muted mb-3" style="font-size: 0.8rem;">
            <i class="fas fa-mouse-pointer me-1"></i> Selecciona un registro para validaci車n individual
        </p>
        
        <table id="registrosTable" class="table table-hover w-100">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Departamento</th>
                    <th>Jefe Inmediato</th>
                    <th class="text-center">Tipo Consumo</th>
                    <th class="text-center">Fecha</th>
                    <th>Causa</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acci車n</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-database fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No hay registros encontrados para este filtro</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($registros as $registro): ?>
                <?php 
                    $estatus = strtoupper(trim($registro['Estatus'] ?? ''));
                    $valJef = strtoupper(trim($registro['ValJefDirect'] ?? ''));
                    
                    // Determinar badge seg迆n estado
                    if ($estatus == 'APROBADO') {
                        $badge_class = 'badge-aprobado';
                        $badge_text = 'APROBADO';
                        $badge_icon = 'fa-check-circle';
                    } else {
                        $badge_class = 'badge-pendiente';
                        $badge_text = 'PENDIENTE';
                        $badge_icon = 'fa-clock';
                    }
                    
                    // Determinar si se puede validar seg迆n rol
                    $rol_usuario = $_GET['newpwd'] ?? '';
                    $puede_validar = false;
                    
                    if ($rol_usuario == 'Administrador' && $estatus != 'APROBADO') {
                        $puede_validar = true;
                    } elseif ($rol_usuario == 'Coordinador' && $valJef != 'APROBADO') {
                        $puede_validar = true;
                    }
                ?>
                <tr data-nombre="<?php echo htmlspecialchars($registro['NOMBRE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-fecha="<?php echo htmlspecialchars($registro['FECHA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-departamento="<?php echo htmlspecialchars($registro['DEPARTAMENTO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-estatus="<?php echo htmlspecialchars($estatus, ENT_QUOTES, 'UTF-8'); ?>"
                    data-valjef="<?php echo htmlspecialchars($valJef, ENT_QUOTES, 'UTF-8'); ?>"
                    data-jefe="<?php echo htmlspecialchars($registro['JEFE'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-tipo="<?php echo htmlspecialchars($registro['TIPO_CONSUMO'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-causa="<?php echo htmlspecialchars($registro['CAUSA'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <td><?php echo $registro['NOMBRE'] ?? ''; ?></td>
                    <td><?php echo $registro['DEPARTAMENTO'] ?? ''; ?></td>
                    <td><?php echo $registro['JEFE'] ?? ''; ?></td>
                    <td class="text-center"><?php echo $registro['TIPO_CONSUMO'] ?? ''; ?></td>
                    <td class="text-center"><?php echo $registro['FECHA'] ?? ''; ?></td>
                    <td><?php echo $registro['CAUSA'] ?? ''; ?></td>
                    <td class="text-center">
                        <span class="badge-estado <?php echo $badge_class; ?>">
                            <i class="fas <?php echo $badge_icon; ?>"></i> <?php echo $badge_text; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($puede_validar): ?>
                        <button type="button" class="btn-action btn-validate" 
                                onclick="validarIndividual(this)">
                            <i class="fas fa-check me-1"></i> Validar
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn-action btn-disabled" disabled>
                            <i class="fas fa-lock me-1"></i> No Disponible
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal para validaci車n individual -->
    <div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-validation">
                <div class="modal-header">
                    <h5 class="modal-title" id="validationModalLabel">
                        <i class="fas fa-user-check me-2"></i>Confirmar Validaci車n
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="validation-details">
                        <div class="detail-item">
                            <span class="detail-label">Nombre:</span>
                            <span class="detail-value" id="modalNombre">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Departamento:</span>
                            <span class="detail-value" id="modalDepartamento">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jefe Inmediato:</span>
                            <span class="detail-value" id="modalJefe">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Fecha:</span>
                            <span class="detail-value" id="modalFecha">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tipo Consumo:</span>
                            <span class="detail-value" id="modalTipo">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Causa:</span>
                            <span class="detail-value" id="modalCausa">-</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Estado Actual:</span>
                            <span class="detail-value" id="modalEstado">-</span>
                        </div>
                    </div>
                    
                    <form method="post" action="" id="individualValidationForm">
                        <input type="hidden" name="nombre" id="individualNombre">
                        <input type="hidden" name="fecha" id="individualFecha">
                        <input type="hidden" name="departamento" id="individualDepartamento">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="validar_individual" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i> Confirmar Validaci車n
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Inicializar DataTable
        $(document).ready(function() {
            var table = $('#registrosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json'
                },
                responsive: true,
                paging: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                order: [[4, 'desc']],
                scrollX: true,
                scrollY: '400px',
                scrollCollapse: true,
                columnDefs: [
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: 4 },
                    { responsivePriority: 3, targets: 6 }
                ]
            });
            
            // Ocultar alerta autom芍ticamente despu谷s de 5 segundos
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Manejar clic en filas de la tabla
            $('#registrosTable tbody').on('click', 'tr', function() {
                // Quitar selecci車n de todas las filas
                $('#registrosTable tbody tr').removeClass('selected');
                
                // Agregar selecci車n a la fila clickeada
                $(this).addClass('selected');
                
                // Obtener datos de la fila
                var nombre = $(this).data('nombre');
                var fecha = $(this).data('fecha');
                var departamento = $(this).data('departamento');
                
                // Actualizar campos del formulario principal
                $('#name123').val(nombre);
                $('#name1234').val(fecha);
                $('#DEPARTAMENTO').val(departamento);
            });
            
            // Funci車n para validaci車n individual - SIMPLIFICADA
            window.validarIndividual = function(button) {
                var row = $(button).closest('tr');
                var nombre = row.data('nombre');
                var fecha = row.data('fecha');
                var departamento = row.data('departamento');
                var jefe = row.data('jefe');
                var tipo = row.data('tipo');
                var causa = row.data('causa');
                var estatus = row.data('estatus') || 'PENDIENTE';
                var valJef = row.data('valjef') || 'PENDIENTE';
                
                // Determinar rol del usuario
                var urlParams = new URLSearchParams(window.location.search);
                var rol = urlParams.get('newpwd') || '';
                
                // Actualizar modal con los datos
                $('#modalNombre').text(nombre || '-');
                $('#modalDepartamento').text(departamento || '-');
                $('#modalJefe').text(jefe || '-');
                $('#modalFecha').text(fecha || '-');
                $('#modalTipo').text(tipo || '-');
                $('#modalCausa').text(causa || '-');
                
                // Mostrar estado actual
                var estadoTexto = '';
                if (rol === 'Administrador') {
                    estadoTexto = 'Estatus: ' + estatus;
                } else if (rol === 'Coordinador') {
                    estadoTexto = 'Validaci車n Jefe: ' + valJef;
                } else {
                    estadoTexto = 'Estatus: ' + estatus + ' | Val. Jefe: ' + valJef;
                }
                $('#modalEstado').text(estadoTexto);
                
                // Actualizar formulario oculto
                $('#individualNombre').val(nombre || '');
                $('#individualFecha').val(fecha || '');
                $('#individualDepartamento').val(departamento || '');
                
                // Mostrar modal
                var modal = new bootstrap.Modal(document.getElementById('validationModal'));
                modal.show();
                
                // PREVENIR EL DOBLE CLIC - deshabilitar el bot車n temporalmente
                $(button).prop('disabled', true).addClass('btn-disabled').html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...');
                
                // Re-habilitar el bot車n despu谷s de 2 segundos (por si cancela el modal)
                setTimeout(function() {
                    $(button).prop('disabled', false).removeClass('btn-disabled').html('<i class="fas fa-check me-1"></i> Validar');
                }, 2000);
            };
            
            // Confirmaci車n para validaci車n individual - SIMPLIFICADA
            $('#individualValidationForm').submit(function(e) {
                e.preventDefault(); // Prevenir env赤o inmediato
                
                var nombre = $('#individualNombre').val();
                var rol = new URLSearchParams(window.location.search).get('newpwd') || '';
                
                var mensaje = '';
                if (rol === 'Administrador') {
                    mensaje = '?Est芍s seguro de APROBAR el registro de "' + nombre + '"?';
                } else if (rol === 'Coordinador') {
                    mensaje = '?Est芍s seguro de VALIDAR como Jefe/Director el registro de "' + nombre + '"?';
                } else {
                    mensaje = '?Est芍s seguro de validar el registro de "' + nombre + '"?';
                }
                
                // Mostrar confirmaci車n nativa
                if (confirm(mensaje)) {
                    // Si confirma, enviar el formulario
                    this.submit();
                } else {
                    // Si cancela, cerrar el modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('validationModal'));
                    modal.hide();
                }
            });
            
            // Confirmaci車n para validaci車n m迆ltiple
            $('#validationForm').submit(function(e) {
                var tipoValidacion = $('#TIPOVALIDA').val();
                var rol = new URLSearchParams(window.location.search).get('newpwd') || '';
                
                if (tipoValidacion === 'UNICA') {
                    var nombre = $('#name123').val();
                    if (!nombre) {
                        alert('Por favor, selecciona un registro de la tabla para validaci車n 迆nica.');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (rol === 'Administrador') {
                        var mensaje = '?Est芍s seguro de APROBAR el registro de "' + nombre + '"?';
                    } else if (rol === 'Coordinador') {
                        var mensaje = '?Est芍s seguro de VALIDAR como Jefe/Director el registro de "' + nombre + '"?';
                    } else {
                        var mensaje = '?Est芍s seguro de validar el registro de "' + nombre + '"?';
                    }
                    
                    if (!confirm(mensaje)) {
                        e.preventDefault();
                        return false;
                    }
                } else {
                    var departamento = $('#DEPARTAMENTO').val();
                    if (!departamento) {
                        alert('Por favor, selecciona un departamento para validaci車n m迆ltiple.');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (rol === 'Administrador') {
                        var mensaje = '?Est芍s seguro de APROBAR TODOS los registros del departamento "' + departamento + '"?';
                    } else if (rol === 'Coordinador') {
                        var mensaje = '?Est芍s seguro de VALIDAR como Jefe/Director TODOS los registros del departamento "' + departamento + '"?';
                    } else {
                        var mensaje = '?Est芍s seguro de validar todos los registros del departamento "' + departamento + '"?';
                    }
                    
                    if (!confirm(mensaje)) {
                        e.preventDefault();
                        return false;
                    }
                }
                return true;
            });
            
            // Cambiar tipo de validaci車n - mostrar/ocultar campos
            $('#TIPOVALIDA').change(function() {
                if ($(this).val() === 'UNICA') {
                    // Para validaci車n 迆nica, limpiar departamento
                    $('#DEPARTAMENTO').val('');
                    $('#name123, #name1234').prop('readonly', true);
                } else {
                    // Para validaci車n m迆ltiple, limpiar nombre y fecha
                    $('#name123').val('');
                    $('#name1234').val('');
                }
            });
            
            // Ajustar altura de la tabla seg迆n el viewport
            function adjustTableHeight() {
                var windowHeight = $(window).height();
                var tableTop = $('.table-container').offset().top;
                var padding = 120;
                var tableHeight = windowHeight - tableTop - padding;
                
                $('.dataTables_scrollBody').css('max-height', Math.max(300, tableHeight) + 'px');
            }
            
            // Ajustar altura al cargar y al redimensionar
            adjustTableHeight();
            $(window).resize(adjustTableHeight);
        });
    </script>
</body>
</html>