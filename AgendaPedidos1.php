<?php
session_start();

// Obtener información del usuario desde la sesión
$user_name = $_SESSION['user_name'] ?? '';
$user_area = $_SESSION['user_area'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Si no hay sesión activa, redirigir al login
if (empty($user_name)) {
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

// Función para obtener todos los lunes entre dos fechas
function obtenerLunes($fecha_inicio = null, $fecha_fin = null) {
    if ($fecha_inicio === null) {
        $fecha_inicio = date('Y-m-d');
    }
    if ($fecha_fin === null) {
        $fecha_fin = date('Y-m-d', strtotime('+2 months'));
    }
    
    $lunes = [];
    $fecha_actual = date('Y-m-d', strtotime('monday this week', strtotime($fecha_inicio)));
    
    while (strtotime($fecha_actual) <= strtotime($fecha_fin)) {
        $lunes[] = $fecha_actual;
        $fecha_actual = date('Y-m-d', strtotime($fecha_actual . ' +1 week'));
    }
    
    return $lunes;
}

// Función para filtrar lunes
function filtrarLunesPasados($lunes_array) {
    $lunes_filtrados = [];
    $hoy = date('Y-m-d');
    $lunes_semana_actual = date('Y-m-d', strtotime('monday this week'));
    
    foreach ($lunes_array as $lunes) {
        if ($lunes == $lunes_semana_actual) {
            $lunes_filtrados[] = $lunes;
        } elseif (strtotime($lunes) > strtotime($hoy)) {
            $lunes_filtrados[] = $lunes;
        }
    }
    
    return $lunes_filtrados;
}

// Obtener lunes
$lunes_todos = obtenerLunes();
$lunes_filtrados = filtrarLunesPasados($lunes_todos);
$lunes_semana_actual = date('Y-m-d', strtotime('monday this week'));

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
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

// Variables
$resultados_tabla = [];
$total_consumos = 0;
$fecha_consulta = $lunes_semana_actual;

// Determinar fecha de consulta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fec']) && !empty($_POST['fec'])) {
    $fecha_consulta = $_POST['fec'];
}

// DEBUG: Variable para mensajes
$debug_messages = [];

// Realizar consulta
if (!empty($user_name) && !empty($fecha_consulta)) {
    $debug_messages[] = "Buscando consumos para: $user_name en fecha: $fecha_consulta";
    
    // Obtener ID del empleado
    $sql_id = "SELECT Id_Empleado FROM [dbo].[Catalogo_EmpArea] WHERE Nombre = ?";
    $params_id = array($user_name);
    $stmt_id = sqlsrv_query($conn, $sql_id, $params_id);
    
    if ($stmt_id === false) {
        $debug_messages[] = "ERROR en consulta de ID: " . print_r(sqlsrv_errors(), true);
    } else {
        if ($row_id = sqlsrv_fetch_array($stmt_id, SQLSRV_FETCH_ASSOC)) {
            $user_id = $row_id['Id_Empleado'];
            $debug_messages[] = "ID encontrado: $user_id";
            
            // DEPURACIÓN: Verificar qué datos hay en la tabla para este empleado
            $sql_debug = "SELECT * FROM [dbo].[PedidosComida] WHERE Id_Empleado = ? AND Fecha = ?";
            $params_debug = array($user_id, $fecha_consulta);
            $stmt_debug = sqlsrv_query($conn, $sql_debug, $params_debug);
            
            if ($stmt_debug === false) {
                $debug_messages[] = "ERROR en consulta de depuración: " . print_r(sqlsrv_errors(), true);
            } else {
                $debug_data = [];
                while ($row_debug = sqlsrv_fetch_array($stmt_debug, SQLSRV_FETCH_ASSOC)) {
                    $debug_data[] = $row_debug;
                }
                $debug_messages[] = "Registros encontrados en PedidosComida: " . count($debug_data);
                if (!empty($debug_data)) {
                    $debug_messages[] = "Datos crudos: " . json_encode($debug_data, JSON_PRETTY_PRINT);
                }
                sqlsrv_free_stmt($stmt_debug);
            }
            
            // Consulta principal para obtener consumos
            $sql_consumos = "SELECT Lunes, Martes, Miercoles, Jueves, Viernes 
                           FROM [dbo].[PedidosComida] 
                           WHERE Id_Empleado = ? AND Fecha = ?";
            
            $params_consumos = array($user_id, $fecha_consulta);
            $stmt_consumos = sqlsrv_query($conn, $sql_consumos, $params_consumos);
            
            if ($stmt_consumos === false) {
                $debug_messages[] = "ERROR en consulta de consumos: " . print_r(sqlsrv_errors(), true);
            } else {
                $debug_messages[] = "Consulta de consumos ejecutada correctamente";
                
                // Procesar resultados
                $registros = [];
                $num_registros = 0;
                
                while ($row = sqlsrv_fetch_array($stmt_consumos, SQLSRV_FETCH_ASSOC)) {
                    $registros[] = $row;
                    $num_registros++;
                    
                    // DEBUG: Mostrar datos de cada registro
                    $debug_messages[] = "Registro $num_registros - Lunes: " . ($row['Lunes'] ?? 'NULL') . 
                                      ", Martes: " . ($row['Martes'] ?? 'NULL') . 
                                      ", Miércoles: " . ($row['Miercoles'] ?? 'NULL') . 
                                      ", Jueves: " . ($row['Jueves'] ?? 'NULL') . 
                                      ", Viernes: " . ($row['Viernes'] ?? 'NULL');
                }
                
                $debug_messages[] = "Total de registros encontrados: $num_registros";
                
                if (!empty($registros)) {
                    // Combinar registros en uno solo
                    $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
                    $registro_combinado = ['Fecha' => $fecha_consulta];
                    
                    foreach ($dias as $dia) {
                        $consumos_dia = [];
                        
                        foreach ($registros as $registro) {
                            if (!empty($registro[$dia]) && trim($registro[$dia]) != '') {
                                $valor = trim($registro[$dia]);
                                $debug_messages[] = "Día $dia tiene valor: '$valor'";
                                
                                // Asegurarse de que el valor sea correcto
                                if (strtoupper($valor) === 'DESAYUNO' || strtoupper($valor) === 'COMIDA' || 
                                    $valor === 'Desayuno' || $valor === 'Comida') {
                                    $consumos_dia[] = ucfirst(strtolower($valor));
                                } else {
                                    $consumos_dia[] = $valor;
                                }
                            }
                        }
                        
                        // Eliminar duplicados
                        $consumos_dia = array_unique($consumos_dia);
                        
                        if (!empty($consumos_dia)) {
                            $registro_combinado[$dia] = implode(', ', $consumos_dia);
                            $total_consumos += count($consumos_dia);
                            $debug_messages[] = "$dia combinado: " . $registro_combinado[$dia];
                        } else {
                            $registro_combinado[$dia] = '';
                            $debug_messages[] = "$dia: Sin consumo";
                        }
                    }
                    
                    $resultados_tabla[] = $registro_combinado;
                    $debug_messages[] = "Registro combinado creado exitosamente";
                }
                
                sqlsrv_free_stmt($stmt_consumos);
            }
        } else {
            $debug_messages[] = "NO se encontró ID para el usuario: $user_name";
        }
        sqlsrv_free_stmt($stmt_id);
    }
}

// Mostrar debug si hay parámetro en URL
$show_debug = isset($_GET['debug']) && $_GET['debug'] == '1';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comedor Corporativo - Mis Consumos</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #1a3a6c;
            --primary-dark: #0d254a;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --text-light: #f0f8ff;
        }

        body {
            background: linear-gradient(135deg, #0b1a3a, #1a3a6c, #0d254a);
            min-height: 100vh;
            padding: 2rem 1rem;
            color: var(--text-light);
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .table {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 1.5rem;
        }

        .table th {
            background: var(--primary-dark) !important;
            color: white !important;
            font-weight: 700;
            padding: 1rem;
            text-align: center;
        }

        .table td {
            font-weight: 600;
            color: #222 !important;
            padding: 1rem;
            text-align: center;
            vertical-align: middle;
        }

        .consumo-celda {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            justify-content: center;
            min-height: 80px;
        }

        .desayuno-texto {
            color: #1565c0;
            font-weight: 700;
            background: #e3f2fd;
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid #bbdefb;
            width: 120px;
        }

        .comida-texto {
            color: #2e7d32;
            font-weight: 700;
            background: #e8f5e9;
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid #c8e6c9;
            width: 120px;
        }

        .empty-cell {
            color: #999 !important;
            font-style: italic;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2D6DA6, #1E4E79);
            border: none;
            border-radius: 14px;
            padding: 0.85rem 1.8rem;
            font-weight: 700;
            width: 100%;
        }

        .nav-link {
            color: rgba(220, 240, 255, 0.95);
            text-decoration: none;
            font-weight: 700;
            padding: 0.65rem 1.4rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.12);
            display: inline-block;
            margin-bottom: 1.8rem;
            margin-right: 1rem;
        }

        .info-header {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: var(--text-light);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .debug-panel {
            background: rgba(0, 0, 0, 0.7);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            color: white;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Enlaces de navegación -->
        <div class="text-center mb-4">
            <a href="http://192.168.100.95/Comedor" class="nav-link">
                <i class="fas fa-home"></i> Menú principal
            </a>
            <a href="http://desarollo-bacros/Comedor/FormatCancel.php" class="nav-link">
                <i class="fas fa-calendar-times"></i> Cancelaciones
            </a>
           
        </div>

        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="Logo2.png" alt="Logo" style="max-width: 130px;">
        </div>

        <!-- Información del usuario -->
        <div class="user-info">
            <div class="mb-2">
                <i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($user_name); ?></strong>
            </div>
            <div class="mb-2">
                <i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($user_id); ?>
            </div>
            <div>
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($user_area); ?>
            </div>
        </div>

        <!-- Título -->
        <h1 class="text-center mb-4"><i class="fas fa-utensils"></i> Consulta de Consumos Semanales</h1>

        <!-- Formulario -->
        <div class="glass-card">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i> Selecciona la semana a consultar
                    </label>
                    <select name="fec" id="fec" class="form-select" required>
                        <option value="">Selecciona una fecha</option>
                        <?php foreach ($lunes_filtrados as $lunes): 
                            $fecha_formateada = date('d/m/Y', strtotime($lunes));
                            $selected = ($fecha_consulta == $lunes) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $lunes; ?>" <?php echo $selected; ?>>
                                <?php echo $fecha_formateada; ?>
                                <?php echo ($lunes == $lunes_semana_actual) ? ' (Semana en curso)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mb-3">
                    <i class="fas fa-search"></i> Buscar Consumos
                </button>

                <?php if (!empty($resultados_tabla)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-chart-bar"></i> Tienes <?php echo $total_consumos; ?> consumos para esta semana
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-info-circle"></i> 
                        <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 
                            'No se encontraron consumos para esta semana' : 
                            'Selecciona una semana para consultar'; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla -->
        <?php if (!empty($resultados_tabla)): ?>
        <div class="glass-card">
            <!-- ENCABEZADO CON ID DEL EMPLEADO -->
            <div class="info-header">
                <i class="fas fa-user-check"></i> Mostrando consumos para: 
                <strong><?php echo htmlspecialchars($user_name); ?> (ID: <?php echo htmlspecialchars($user_id); ?>)</strong> - 
                <strong><?php echo date('d/m/Y', strtotime($fecha_consulta)); ?></strong>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Semana</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados_tabla as $fila): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary-dark) !important;">
                                    <?php echo date('d/m/Y', strtotime($fila['Fecha'])); ?>
                                </td>
                                
                                <?php 
                                $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
                                foreach ($dias as $dia): 
                                ?>
                                    <td>
                                        <div class="consumo-celda">
                                            <?php 
                                            if (!empty($fila[$dia])) {
                                                $consumos = explode(', ', $fila[$dia]);
                                                
                                                foreach ($consumos as $consumo) {
                                                    $consumo_limpio = trim($consumo);
                                                    if (strtoupper($consumo_limpio) === 'DESAYUNO' || $consumo_limpio === 'Desayuno') {
                                                        echo '<div class="desayuno-texto">DESAYUNO</div>';
                                                    } elseif (strtoupper($consumo_limpio) === 'COMIDA' || $consumo_limpio === 'Comida') {
                                                        echo '<div class="comida-texto">COMIDA</div>';
                                                    } else {
                                                        echo '<div>' . htmlspecialchars($consumo_limpio) . '</div>';
                                                    }
                                                }
                                            } else {
                                                echo '<span class="empty-cell">Sin consumo</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Panel de depuración -->
        <?php if ($show_debug && !empty($debug_messages)): ?>
        <div class="glass-card">
            <h4><i class="fas fa-bug"></i> Información de Depuración</h4>
            <div class="debug-panel">
                <?php foreach ($debug_messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-refrescar cada hora
        setTimeout(function() {
            location.reload();
        }, 3600000);
    </script>
</body>
</html>