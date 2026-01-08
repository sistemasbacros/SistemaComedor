<?php
// ==================================================
// MIGRACIÓN A API - AgendaPedidos1.php
// ==================================================

session_start();

// Cargar configuración de la API y cliente
require_once __DIR__ . '/config_api.php';
require_once __DIR__ . '/api_client.php';

// Verificar autenticación (JWT o sesión tradicional)
$api = getAPIClient();

// Verificar autenticación con API
if (!$api->isAuthenticated()) {
    header("Location: " . getAppUrl('Admiin.php'));
    exit;
}

// Obtener información del usuario desde la sesión
$user_name = $_SESSION['user_name'] ?? '';
$user_area = $_SESSION['user_area'] ?? '';

// Si no hay sesión activa, redirigir al login
if (empty($user_name)) {
    header("Location: " . getAppUrl('Admiin.php'));
    exit;
}

// ==================================================
// FUNCIÓN HELPER: Generar semanas localmente (fallback)
// ==================================================
function obtenerLunesLocal() {
    $lunes = [];
    $fecha_actual = date('Y-m-d', strtotime('monday this week'));
    
    for ($i = 0; $i < 9; $i++) {
        $lunes[] = $fecha_actual;
        $fecha_actual = date('Y-m-d', strtotime($fecha_actual . ' +1 week'));
    }
    
    return $lunes;
}

// ==================================================
// OBTENER SEMANAS DISPONIBLES DESDE LA API
// ==================================================

$lunes_filtrados = [];
$semana_actual = '';
$error_semanas = null;

$response_semanas = obtenerSemanasDisponibles();

if ($response_semanas['success']) {
    $data_semanas = $response_semanas['data'];
    $semana_actual = $data_semanas['semana_actual'] ?? date('Y-m-d', strtotime('monday this week'));
    
    // Convertir las semanas al formato esperado
    if (isset($data_semanas['semanas']) && is_array($data_semanas['semanas'])) {
        foreach ($data_semanas['semanas'] as $semana) {
            if (isset($semana['fecha'])) {
                $lunes_filtrados[] = $semana['fecha'];
            }
        }
    }
    
    // Si no se obtuvieron semanas, usar fallback
    if (empty($lunes_filtrados)) {
        $lunes_filtrados = obtenerLunesLocal();
        $semana_actual = $lunes_filtrados[0] ?? date('Y-m-d', strtotime('monday this week'));
    }
} else {
    $error_semanas = $response_semanas['error'];
    
    // Fallback: generar semanas localmente si la API falla
    $lunes_filtrados = obtenerLunesLocal();
    $semana_actual = $lunes_filtrados[0] ?? date('Y-m-d', strtotime('monday this week'));
}

// ==================================================
// OBTENER CONSUMOS DE LA SEMANA
// ==================================================

// Variables para almacenar resultados
$resultados_tabla = [];
$total_consumos = 0;
$fecha_consulta = null;
$error_consumos = null;

// Solo consultar consumos cuando el usuario envía el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fec']) && !empty($_POST['fec'])) {
    $fecha_consulta = test_input($_POST['fec']);
    
    // Obtener consumos desde la API
    if (!empty($user_name) && !empty($fecha_consulta)) {
        $response_consumos = obtenerConsumosSemanales($fecha_consulta);
        
        // DEBUG: Ver la respuesta completa
        error_log("=== DEBUG CONSUMOS ===");
        error_log("Response completo: " . print_r($response_consumos, true));
        
        if ($response_consumos['success']) {
            $data = $response_consumos['data'];
            
            // DEBUG: Ver estructura de data
            error_log("Data recibido: " . print_r($data, true));
            
            // La API puede devolver un wrapper extra con 'success' y 'data'
            // Detectar y desempaquetar si existe
            if (isset($data['success']) && isset($data['data'])) {
                error_log("Detectado wrapper extra, desempaquetando...");
                $data = $data['data'];
            }
            
            error_log("Tiene empleado? " . (isset($data['empleado']) ? 'SI' : 'NO'));
            error_log("Tiene desglose? " . (isset($data['desglose']) ? 'SI (' . count($data['desglose']) . ' items)' : 'NO'));
            error_log("Tiene total_consumos? " . (isset($data['total_consumos']) ? 'SI' : 'NO'));
            
            // Validar estructura de datos de la API
            if (isset($data['empleado'], $data['desglose']) && is_array($data['desglose'])) {
                // Crear matriz de consumos agrupados por tipo
                $consumos_matriz = [
                    'Desayuno' => ['Lunes' => '', 'Martes' => '', 'Miercoles' => '', 'Jueves' => '', 'Viernes' => ''],
                    'Comida' => ['Lunes' => '', 'Martes' => '', 'Miercoles' => '', 'Jueves' => '', 'Viernes' => '']
                ];
                
                // Llenar la matriz con los datos del desglose
                foreach ($data['desglose'] as $consumo) {
                    $dia = $consumo['dia'] ?? '';
                    $tipo = $consumo['tipo'] ?? '';
                    
                    // Normalizar el nombre del día (sin acentos para el array)
                    $dia_normalizado = str_replace('é', 'e', $dia);
                    
                    if (isset($consumos_matriz[$tipo][$dia_normalizado])) {
                        $consumos_matriz[$tipo][$dia_normalizado] = $tipo;
                    }
                }
                
                // Crear 2 filas: una para Desayuno y otra para Comida
                foreach ($consumos_matriz as $tipo => $dias) {
                    $resultados_tabla[] = [
                        'Fecha' => $data['fecha_consulta'] ?? $fecha_consulta,
                        'Id_Empleado' => $data['empleado']['id_empleado'] ?? '',
                        'Nombre' => $data['empleado']['nombre'] ?? $user_name,
                        'Tipo' => $tipo,
                        'Lunes' => $dias['Lunes'],
                        'Martes' => $dias['Martes'],
                        'Miercoles' => $dias['Miercoles'],
                        'Jueves' => $dias['Jueves'],
                        'Viernes' => $dias['Viernes']
                    ];
                }
                
                $total_consumos = $data['total_consumos'] ?? count($data['desglose']);
                error_log("✓ Datos procesados correctamente. Total consumos: $total_consumos, Filas generadas: " . count($resultados_tabla));
            } else {
                $error_consumos = 'La API devolvió datos en formato incorrecto. Revisa los logs del servidor.';
                error_log("ERROR: Validación fallida - No se encontró empleado o desglose");
            }
        } else {
            $error_consumos = $response_consumos['error'];
            error_log("ERROR API: " . $error_consumos);
        }
    }
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comedor Corporativo</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">

    <style>
        /* Tus estilos CSS se mantienen igual */
        :root {
            --primary: #1a3a6c;
            --primary-dark: #0d254a;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-glow: rgba(255, 255, 255, 0.18);
            --text-light: #f0f8ff;
            --shadow: 0 12px 32px rgba(0, 10, 30, 0.4);
        }

        * {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0b1a3a, #1a3a6c, #0d254a);
            min-height: 100vh;
            padding: 2rem 1rem;
            color: var(--text-light);
            overflow-x: hidden;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 
                var(--shadow),
                0 0 0 1px var(--glass-glow),
                inset 0 0 0 1px rgba(255, 255, 255, 0.07);
            padding: 2.25rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .logo-container img {
            max-width: 130px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        h1 {
            font-weight: 700;
            text-align: center;
            margin-bottom: 2.2rem;
            font-size: 2.1rem;
            text-shadow: 0 2px 6px rgba(0,0,0,0.3);
            letter-spacing: -0.5px;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
        }

        .user-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: #e0f0ff;
        }

        .user-area {
            font-weight: 500;
            font-size: 1rem;
            color: #b8d4ff;
            margin-top: 0.3rem;
        }

        .form-label {
            font-weight: 600;
            color: #e0f0ff;
            margin-top: 1.1rem;
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.92);
            border: none;
            border-radius: 14px;
            padding: 0.85rem 1.1rem;
            font-size: 1.02rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(45, 109, 166, 0.4);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2D6DA6, #1E4E79);
            border: none;
            border-radius: 14px;
            padding: 0.85rem 1.8rem;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
            transition: all 0.35s cubic-bezier(0.2, 0, 0.2, 1);
            box-shadow: 
                0 6px 16px rgba(0, 0, 0, 0.25),
                0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3a7db6, #2a5e8a);
            transform: translateY(-3px);
            box-shadow: 
                0 10px 24px rgba(0, 0, 0, 0.35),
                0 6px 12px rgba(0, 0, 0, 0.25);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        #NC {
            font-size: 1.35rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1E4E79, #2D6DA6);
            padding: 0.9rem 1.8rem;
            border-radius: 16px;
            display: inline-block;
            margin-top: 1.8rem;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
            letter-spacing: 0.3px;
        }

        .table {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
        }

        .table th {
            background: var(--primary-dark) !important;
            color: white !important;
            font-weight: 700;
            font-size: 0.98rem;
            padding: 1rem;
        }

        .table td {
            font-weight: 600;
            color: #222 !important;
            padding: 0.9rem;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(245, 249, 255, 0.65);
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
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-right: 1rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.22);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .nav-buttons {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .alert-info {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: var(--text-light);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .glass-card {
                padding: 1.6rem;
            }
            h1 {
                font-size: 1.7rem;
            }
            .btn-primary {
                font-size: 1.05rem;
                padding: 0.8rem 1.5rem;
            }
            .nav-link {
                display: block;
                margin-right: 0;
                margin-bottom: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Enlaces de navegación -->
        <div class="nav-buttons">
            <a href="http://192.168.100.95/Comedor" class="nav-link">← Menú principal</a>
            <a href="http://desarollo-bacros/Comedor/FormatCancel.php" class="nav-link">📅 Cancelaciones</a>
        </div>

        <!-- Logo -->
        <div class="logo-container">
            <img src="Logo2.png" alt="Logo">
        </div>

        <!-- Información del usuario -->
        <div class="user-info">
            <div class="user-name">👤 <?php echo htmlspecialchars($user_name); ?></div>
            <div class="user-area">🏢 <?php echo htmlspecialchars($user_area); ?></div>
        </div>

        <!-- Título -->
        <h1>Consulta de Consumos Semanales</h1>

        <!-- Mostrar errores de API si existen -->
        <?php if ($error_semanas || $error_consumos): ?>
            <div class="glass-card">
                <div class="alert alert-warning" role="alert">
                    <strong>⚠️ Modo Limitado:</strong>
                    <?php if ($error_semanas): ?>
                        <p>Error al obtener semanas desde la API: <?php echo htmlspecialchars($error_semanas); ?></p>
                        <p><small>Usando generación local de semanas como respaldo.</small></p>
                    <?php endif; ?>
                    <?php if ($error_consumos): ?>
                        <p>Error al obtener consumos: <?php echo htmlspecialchars($error_consumos); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="glass-card">
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="fec" class="form-label">Selecciona la semana a consultar</label>
                        <select name="fec" id="fec" class="form-select" required>
                            <option value="">Selecciona una fecha</option>
                            <?php foreach ($lunes_filtrados as $lunes): 
                                $fecha_formateada = date('d/m/Y', strtotime($lunes));
                                $selected = '';
                                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fec'])) {
                                    $selected = ($_POST['fec'] == $lunes) ? 'selected' : '';
                                } else {
                                    $selected = ($lunes == $semana_actual) ? 'selected' : '';
                                }
                            ?>
                                <option value="<?php echo $lunes; ?>" <?php echo $selected; ?>>
                                    <?php echo $fecha_formateada; ?>
                                    <?php echo ($lunes == $semana_actual) ? '(Semana en curso)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($lunes_filtrados)): ?>
                            <div class="alert alert-warning mt-2">
                                No hay semanas disponibles para consultar.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Buscar Consumos</button>
                </div>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <?php if (!empty($resultados_tabla)): ?>
                        <div id="NC" class="mt-4 text-center">
                            Tienes <?php echo $total_consumos; ?> consumos para esta semana
                        </div>
                    <?php else: ?>
                        <div id="NC" class="mt-4 text-center">
                            No se encontraron consumos para esta semana
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla -->
        <div class="glass-card">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($resultados_tabla)): ?>
                <div class="alert alert-info">
                    Mostrando consumos para: <strong><?php echo htmlspecialchars($user_name); ?></strong> - 
                   <strong><?php echo date('d/m/Y', strtotime($fecha_consulta)); ?></strong>
                </div>
                
                <div class="table-responsive">
                    <table id="example" class="table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Id_Empleado</th>
                                <th>Nombre</th>
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
                                    <td><?php echo !empty($fila['Fecha']) ? date('d/m/Y', strtotime($fila['Fecha'])) : ''; ?></td>
                                    <td><?php echo htmlspecialchars($fila['Id_Empleado'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Nombre'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Lunes'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Martes'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Miercoles'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Jueves'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($fila['Viernes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p><strong>Selecciona una semana y presiona "Buscar Consumos"</strong></p>
                    <p class="text-muted">Aquí se mostrarán tus consumos de alimentos de la semana seleccionada.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar DataTable si hay datos
            <?php if (!empty($resultados_tabla)): ?>
                $('#example').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-MX.json"
                    },
                    "pageLength": 10,
                    "order": [[0, 'desc']]
                });
            <?php endif; ?>
            
            // Auto-refrescar la página cada hora para actualizar las fechas
            setTimeout(function() {
                location.reload();
            }, 3600000); // 3600000 ms = 1 hora
        });
    </script>
</body>
</html>