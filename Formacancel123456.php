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
        $rol_usuario = $_GET['newpwd'] ?? '';
        
        if (!empty($nombre) && !empty($fecha) && !empty($rol_usuario)) {
            if ($rol_usuario == 'Administrador') {
                $sql_update = "UPDATE cancelaciones SET Estatus = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                $mensaje_exito = "✅ Validación de Administrador completada para: " . $nombre;
            } elseif ($rol_usuario == 'Coordinador') {
                $sql_update = "UPDATE cancelaciones SET ValJefDirect = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                $mensaje_exito = "✅ Validación de Coordinador completada para: " . $nombre;
            }
            
            if (isset($sql_update)) {
                $params = array($nombre, $fecha);
                $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                if (sqlsrv_execute($stmt_update)) {
                    $tipo_mensaje = 'success';
                    $redirect_url = "?filtro=" . urlencode($filtro_estado) . "&mensaje=" . urlencode($mensaje_exito) . "&tipo=success";
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
        $rol_usuario = $_GET['newpwd'] ?? '';
        
        if (!empty($rol_usuario)) {
            if ($tipo_validacion == 'UNICA') {
                if (!empty($nombre_lote) && !empty($fecha_lote)) {
                    if ($rol_usuario == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                    } elseif ($rol_usuario == 'Coordinador') {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = 'APROBADO' WHERE NOMBRE = ? AND CONVERT(varchar, FECHA, 120) = ?";
                    }
                    
                    if (isset($sql_update)) {
                        $params = array($nombre_lote, $fecha_lote);
                        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                        if (sqlsrv_execute($stmt_update)) {
                            $mensaje_exito = "✅ Validación única completada para: " . $nombre_lote;
                            $tipo_mensaje = 'success';
                        }
                    }
                }
            } else {
                if (!empty($departamento)) {
                    if ($rol_usuario == 'Administrador') {
                        $sql_update = "UPDATE cancelaciones SET Estatus = 'APROBADO' WHERE DEPARTAMENTO LIKE ? AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
                    } elseif ($rol_usuario == 'Coordinador') {
                        $sql_update = "UPDATE cancelaciones SET ValJefDirect = 'APROBADO' WHERE DEPARTAMENTO LIKE ? AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
                    }
                    
                    if (isset($sql_update)) {
                        $params = array('%' . $departamento . '%');
                        $stmt_update = sqlsrv_prepare($conn, $sql_update, $params);
                        if (sqlsrv_execute($stmt_update)) {
                            $rows_affected = sqlsrv_rows_affected($stmt_update);
                            $mensaje_exito = "✅ Validación múltiple completada para departamento: " . $departamento . " ($rows_affected registros actualizados)";
                            $tipo_mensaje = 'success';
                        }
                    }
                }
            }
            
            if ($tipo_mensaje == 'success' || $tipo_mensaje == 'danger') {
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

// CONSULTA SEGÚN FILTRO Y ROL - CORREGIDA
if ($filtro_estado == 'pendientes') {
    // PENDIENTES: Mostrar los que NO han sido aprobados según el rol
    if ($rol == 'Administrador') {
        // Para Admin pendientes: mostrar donde Estatus NO es 'APROBADO'
        $sql = "SELECT * FROM cancelaciones 
                WHERE (Estatus != 'APROBADO' OR Estatus IS NULL) 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } elseif ($rol == 'Coordinador') {
        // Para Coordinador pendientes: mostrar donde ValJefDirect NO es 'APROBADO'
        $sql = "SELECT * FROM cancelaciones 
                WHERE (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } else {
        // Sin rol: mostrar todos los pendientes (ambos campos)
        $sql = "SELECT * FROM cancelaciones 
                WHERE ((Estatus != 'APROBADO' OR Estatus IS NULL) 
                OR (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL))
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    }
} else {
    // APROBADOS: Mostrar los que SÍ han sido aprobados
    if ($rol == 'Administrador') {
        // Para Admin aprobados: mostrar donde Estatus = 'APROBADO'
        $sql = "SELECT * FROM cancelaciones 
                WHERE Estatus = 'APROBADO' 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } elseif ($rol == 'Coordinador') {
        // Para Coordinador aprobados: mostrar donde ValJefDirect = 'APROBADO'
        $sql = "SELECT * FROM cancelaciones 
                WHERE ValJefDirect = 'APROBADO' 
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    } else {
        // Sin rol: mostrar todos los aprobados (cualquiera de los dos campos)
        $sql = "SELECT * FROM cancelaciones 
                WHERE (Estatus = 'APROBADO' OR ValJefDirect = 'APROBADO')
                AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE()) 
                ORDER BY FECHA DESC";
    }
}

// DEBUG: Mostrar la consulta SQL
// echo "<!-- SQL: " . htmlspecialchars($sql) . " -->";

// Ejecutar consulta
$stmt = sqlsrv_query($conn, $sql);
$registros = array();

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Normalizar datos UTF-8
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

// CONTADORES CORREGIDOS
// Contador de pendientes según rol
if ($rol == 'Administrador') {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE (Estatus != 'APROBADO' OR Estatus IS NULL) 
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} elseif ($rol == 'Coordinador') {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL) 
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
} else {
    $sql_pendientes = "SELECT COUNT(*) as total FROM cancelaciones 
                      WHERE ((Estatus != 'APROBADO' OR Estatus IS NULL) 
                      OR (ValJefDirect != 'APROBADO' OR ValJefDirect IS NULL))
                      AND convert(date, FECHA, 102) >= DATEADD(month, -3, GETDATE())";
}

// Contador de aprobados según rol
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
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-validate:hover {
            background: #2e7d32;
            transform: translateY(-1px);
        }
        
        .btn-disabled {
            background: #e0e0e0;
            color: #9e9e9e;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            font-size: 12px;
            cursor: not-allowed;
        }
        
        .selected-row {
            background-color: rgba(33, 150, 243, 0.1) !important;
            border-left: 3px solid #1e88e5 !important;
        }
    </style>
</head>
<body>
    <?php if (!empty($mensaje_exito)): ?>
    <div class="alert alert-<?php echo $tipo_mensaje == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php if ($tipo_mensaje == 'success'): ?>
            <i class="fas fa-check-circle me-2"></i>
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
                            echo 'Mostrando registros con Estatus = APROBADO';
                        } elseif ($rol == 'Coordinador') {
                            echo 'Mostrando registros con Validación Jefe = APROBADO';
                        } else {
                            echo 'Mostrando registros con Estatus = APROBADO o Validación Jefe = APROBADO';
                        }
                    } else {
                        if ($rol == 'Administrador') {
                            echo 'Mostrando registros con Estatus PENDIENTE';
                        } elseif ($rol == 'Coordinador') {
                            echo 'Mostrando registros con Validación Jefe PENDIENTE';
                        } else {
                            echo 'Mostrando registros pendientes de validación';
                        }
                    }
                    ?>
                    <br>
                    <i class="fas fa-database me-1"></i> Registros mostrados: <?php echo count($registros); ?>
                </small>
            </div>
        </div>
        
        <!-- Formulario de validación -->
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
                    <div class="col-12">
                        <button type="submit" name="validar_lote" class="btn btn-primary w-100 btn-sm">
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
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Jefe Inmediato</th>
                            <th>Tipo Consumo</th>
                            <th>Fecha</th>
                            <th>Causa</th>
                            <th>Estado</th>
                            <th>Acción</th>
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
                        <?php foreach ($registros as $registro): ?>
                        <?php 
                            $estatus = strtoupper(trim($registro['Estatus'] ?? ''));
                            $valJef = strtoupper(trim($registro['ValJefDirect'] ?? ''));
                            
                            // DETERMINAR ESTADO VISUAL - CORREGIDO
                            if ($filtro_estado == 'aprobados') {
                                // Si estamos en el filtro de aprobados, siempre mostrar APROBADO
                                $badge_class = 'badge-aprobado';
                                $badge_text = 'APROBADO';
                                $badge_icon = 'fa-check-circle';
                            } else {
                                // Si estamos en pendientes, mostrar según los valores reales
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
                            
                            // Determinar si se puede validar según rol y filtro
                            $puede_validar = false;
                            if ($filtro_estado == 'pendientes') {
                                if ($rol == 'Administrador' && $estatus != 'APROBADO') {
                                    $puede_validar = true;
                                } elseif ($rol == 'Coordinador' && $valJef != 'APROBADO') {
                                    $puede_validar = true;
                                }
                            }
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
                            <td><?php echo $registro['CAUSA'] ?? ''; ?></td>
                            <td>
                                <span class="badge-estado <?php echo $badge_class; ?>">
                                    <i class="fas <?php echo $badge_icon; ?> me-1"></i>
                                    <?php echo $badge_text; ?>
                                </span>
                                <br>
                                <small class="text-muted" style="font-size: 10px;">
                                    <?php if ($estatus == 'APROBADO') echo '✓ Admin'; ?>
                                    <?php if ($valJef == 'APROBADO') echo '✓ Coord'; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($puede_validar): ?>
                                <button type="button" class="btn-validate"
                                        onclick="validarIndividual('<?php echo addslashes($registro['NOMBRE'] ?? ''); ?>', 
                                                                  '<?php echo addslashes($registro['FECHA'] ?? ''); ?>', 
                                                                  '<?php echo addslashes($registro['DEPARTAMENTO'] ?? ''); ?>')">
                                    <i class="fas fa-check me-1"></i> Validar
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn-disabled" disabled>
                                    <?php if ($filtro_estado == 'aprobados'): ?>
                                        <i class="fas fa-check me-1"></i> Ya Aprobado
                                    <?php elseif ($estatus == 'APROBADO' && $rol == 'Administrador'): ?>
                                        <i class="fas fa-check me-1"></i> Ya Aprobado
                                    <?php elseif ($valJef == 'APROBADO' && $rol == 'Coordinador'): ?>
                                        <i class="fas fa-check me-1"></i> Ya Validado
                                    <?php else: ?>
                                        <i class="fas fa-lock me-1"></i> No Disponible
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check me-2"></i>Confirmar Validación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-question-circle fa-3x text-primary"></i>
                    </div>
                    <h6 class="text-center mb-3">¿Estás seguro de validar este registro?</h6>
                    
                    <div class="alert alert-light">
                        <div class="row">
                            <div class="col-4 fw-bold">Nombre:</div>
                            <div class="col-8" id="modalNombre">-</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4 fw-bold">Fecha:</div>
                            <div class="col-8" id="modalFecha">-</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-4 fw-bold">Departamento:</div>
                            <div class="col-8" id="modalDepartamento">-</div>
                        </div>
                    </div>
                    
                    <form method="post" action="" id="individualValidationForm">
                        <input type="hidden" name="nombre" id="individualNombre">
                        <input type="hidden" name="fecha" id="individualFecha">
                        <input type="hidden" name="departamento" id="individualDepartamento">
                        <input type="hidden" name="validar_individual" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmarValidacion()">
                        <i class="fas fa-check-circle me-1"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Seleccionar filas
        document.querySelectorAll('.row-selectable').forEach(row => {
            row.addEventListener('click', function() {
                // Quitar selección anterior
                document.querySelectorAll('.row-selectable').forEach(r => {
                    r.classList.remove('selected-row');
                });
                
                // Seleccionar esta fila
                this.classList.add('selected-row');
                
                // Actualizar formulario
                const nombre = this.dataset.nombre;
                const fecha = this.dataset.fecha;
                const departamento = this.dataset.departamento;
                
                document.getElementById('name123').value = nombre;
                document.getElementById('name1234').value = fecha;
                document.getElementById('DEPARTAMENTO').value = departamento;
            });
        });
        
        // Cambiar tipo de validación
        document.getElementById('TIPOVALIDA').addEventListener('change', function() {
            if (this.value === 'UNICA') {
                document.getElementById('DEPARTAMENTO').value = '';
            } else {
                document.getElementById('name123').value = '';
                document.getElementById('name1234').value = '';
            }
        });
        
        // Función para validar individual
        function validarIndividual(nombre, fecha, departamento) {
            document.getElementById('modalNombre').textContent = nombre;
            document.getElementById('modalFecha').textContent = fecha;
            document.getElementById('modalDepartamento').textContent = departamento;
            
            document.getElementById('individualNombre').value = nombre;
            document.getElementById('individualFecha').value = fecha;
            document.getElementById('individualDepartamento').value = departamento;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        }
        
        // Función para confirmar validación
        function confirmarValidacion() {
            document.getElementById('individualValidationForm').submit();
        }
        
        // Auto-ocultar alertas
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.remove();
            });
        }, 5000);
    </script>
</body>
</html>