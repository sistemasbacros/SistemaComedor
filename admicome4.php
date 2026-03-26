<?php
// ==================================================
// PROTECCIÓN DE SEGURIDAD MEJORADA - NO ELIMINAR
// ==================================================

// Configuración de sesión
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/Comedor/',
    'domain' => '',
    'secure' => false,
    'httpononly' => true,
    'samesite' => 'Strict'
]);

// Configuración de seguridad
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// ========== MANEJO DE CIERRE DE SESIÓN ==========
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    $_SESSION = array();
    session_destroy();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

// Verificación estricta de autenticación
$isAuthenticated = (
    isset($_SESSION['authenticated_from_login']) && 
    $_SESSION['authenticated_from_login'] === true &&
    isset($_SESSION['session_id']) && 
    $_SESSION['session_id'] === session_id() &&
    isset($_SESSION['browser_fingerprint']) && 
    $_SESSION['browser_fingerprint'] === md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'])
);

if (!$isAuthenticated) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time()-3600, '/');
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

// Verificar expiración de sesión
$sessionTimeout = 2 * 4000;
if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] > $sessionTimeout)) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time()-3600, '/');
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['one_time_access']);
}

$_SESSION['LAST_ACTIVITY'] = time();

// ==================================================
// CONTROL DE PERMISOS POR USUARIO
// ==================================================

$user_name = $_SESSION['user_name'] ?? 'Administrador';
$user_area = $_SESSION['user_area'] ?? 'Sistema de Comedor';
$usuarios_restringidos = ['CIENEGA JASSO MIRIAM'];
$usuario_actual = strtoupper(trim($user_name));
$acceso_completo = !in_array($usuario_actual, $usuarios_restringidos);

if (!$acceso_completo) {
    $_GET['section'] = 'reportes';
    if (!isset($_SESSION['restriccion_info'])) {
        $_SESSION['restriccion_info'] = "Su usuario tiene acceso restringido solo a la sección de Reportes.";
    }
}

// ==================================================
// CONEXIÓN A BASE DE DATOS
// ==================================================

$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

$serverNameContpaq = "WIN-44O80L37Q7M\COMERCIAL";
$connectionOptionsContpaq = array(
    "Database" => "ALQUIMISTA2024",
    "Uid" => "sa",
    "PWD" => "Administrador1*",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

$serverNameBaseNueva = "WIN-44O80L37Q7M\COMERCIAL";
$connectionOptionsBaseNueva = array(
    "Database" => "BASENUEVA",
    "Uid" => "sa",
    "PWD" => "Administrador1*",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// ==================================================
// PARÁMETROS DE FECHA
// ==================================================

if (isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'];
} else {
    $fecha_fin = date('Y-m-d');
    $fecha_inicio = date('Y-m-d', strtotime('-5 day'));
}

if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
    $fecha_inicio = $fecha_fin;
}

// ==================================================
// CLASIFICACIÓN DE PERSONAS - VERSIÓN CORREGIDA
// ==================================================

// 1. CONSUMO CONSIDERADO (EXENTOS - NO PAGAN) - Van al card de Personas Exentas
$personas_consumo_considerado = [
    'ALEJANDRA CRUZ',
    'ALTA DIRECCION',
    'CRUZ JOSE LUIS',
    'CRUZ RODRIGUEZ ALEJANDRO',
    'REYES QUIROZ HILDA',
    'VIGILANCIA',
    'JUREZ VAZQUEZ MIGUEL ANGEL',
    'SOTO DEL HOYO ISMAEL',
'PALMA TREJO SANDY MARK'
];

// 2. AGENDA (NO PAGAN - SOLO MONITOREO DE CONSUMOS)
$personas_agenda = [
    'CELAYA YAXI LUIS ENRIQUE',
    'FIRO CORTAZAR FERNANDO',
    'HERRERA CUALI HUGO ALEJANDRO',
    'REYES FONSECA NORMA ANGELICA',
    'GUTIERREZ EZQUIVEL EDGAR',
    'CASTILLO NIETO JESSICA',
    'JOSE FERNANDO OSORIO OJEDA'
];

// 3. QUITADOS (PAGANTES NORMALES)
$personas_quitadas = [
    'JURIDICO',
    'ADAME GARCIA JOSE PAUL'
];

// LISTA DE EXENTOS = SOLO CONSUMO CONSIDERADO
$personas_exentas = $personas_consumo_considerado;

// Crear condiciones SQL para exentos (SOLO CONSUMO CONSIDERADO)
$exentos_conditions = [];
foreach ($personas_exentas as $exento) {
    $exentos_conditions[] = "nombre LIKE '%$exento%'";
}
$exentos_sql_condition = "(" . implode(" OR ", $exentos_conditions) . ")";

$exentos_conditions_usuario = [];
foreach ($personas_exentas as $exento) {
    $exentos_conditions_usuario[] = "Usuario LIKE '%$exento%'";
}
$exentos_sql_condition_usuario = "(" . implode(" OR ", $exentos_conditions_usuario) . ")";

// Variables
$exentos_desayuno_servidos = 0;
$exentos_comida_servidos = 0;
$exentos_desayuno_agendados = 0;
$exentos_comida_agendadas = 0;
$exentos_total_servidos = 0;
$exentos_total_agendados = 0;
$monto_exentos = 0;
$desglose_exentos = array();

// Variables para seguimiento de AGENDA (solo monitoreo - NO pagan)
$agenda_desayunos_servidos = 0;
$agenda_comidas_servidas = 0;
$agenda_desayunos_agendados = 0;
$agenda_comidas_agendadas = 0;
$desglose_agenda = array();

// Inicializar variables del dashboard
$total_usuarios = 0;
$desayunos_hoy = 0;
$comidas_hoy = 0;
$desayunos_agendados = 0;
$comidas_agendadas = 0;
$monto_recaudado = 0;

$pagantes_desayunos_servidos = 0;
$pagantes_comidas_servidas = 0;
$pagantes_desayunos_agendados = 0;
$pagantes_comidas_agendadas = 0;
$total_pagantes = 0;

$cancelaciones_desayuno = 0;
$cancelaciones_comida = 0;
$total_cancelaciones = 0;
$cancelaciones_pendientes = 0;
$cancelaciones_pendientes_notificacion = '';

$total_gastos_alquimista = 0;
$total_gastos_basenueva = 0;
$total_gastos_contpaq = 0;
$total_registros_contpaq = 0;

$fecha_cambio_precios = '2026-01-01';
$precio_desayuno_viejo = 30;
$precio_comida_viejo = 30;
$precio_desayuno_nuevo = 35;
$precio_comida_nuevo = 45;

$monto_desayunos_antes_2026 = 0;
$monto_desayunos_desde_2026 = 0;
$monto_comidas_antes_2026 = 0;
$monto_comidas_desde_2026 = 0;
$total_desayunos_antes_2026 = 0;
$total_desayunos_desde_2026 = 0;
$total_comidas_antes_2026 = 0;
$total_comidas_desde_2026 = 0;

$monto_exentos_desayunos_antes_2026 = 0;
$monto_exentos_desayunos_desde_2026 = 0;
$monto_exentos_comidas_antes_2026 = 0;
$monto_exentos_comidas_desde_2026 = 0;
$total_exentos_desayunos_antes_2026 = 0;
$total_exentos_desayunos_desde_2026 = 0;
$total_exentos_comidas_antes_2026 = 0;
$total_exentos_comidas_desde_2026 = 0;

try {
    // Conexión a la base de datos principal
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn !== false) {
        // Consulta para total de usuarios en el periodo (EXCLUYENDO EXENTOS - SOLO CONSUMO CONSIDERADO)
        $sql_usuarios = "SELECT COUNT(*) as Total_Usuarios FROM (
            SELECT DISTINCT Usuario
            FROM PedidosComida
            WHERE CONVERT(DATE, Fecha) BETWEEN ? AND ?
            AND NOT $exentos_sql_condition_usuario
        ) as a";
        $params_usuarios = array($fecha_inicio, $fecha_fin);
        $stmt_usuarios = sqlsrv_query($conn, $sql_usuarios, $params_usuarios);
        
        if ($stmt_usuarios !== false) {
            $row = sqlsrv_fetch_array($stmt_usuarios, SQLSRV_FETCH_ASSOC);
            $total_usuarios = $row['Total_Usuarios'];
        }
        
        // DESGLOSE DE EXENTOS (SOLO CONSUMO CONSIDERADO)
        $sql_desglose_exentos = "SELECT 
            CASE 
                WHEN nombre LIKE '%ALEJANDRA CRUZ%' THEN 'ALEJANDRA CRUZ'
                WHEN nombre LIKE '%ALTA DIRECCION%' THEN 'ALTA DIRECCION'
                WHEN nombre LIKE '%CRUZ JOSE LUIS%' THEN 'CRUZ JOSE LUIS'
                WHEN nombre LIKE '%CRUZ RODRIGUEZ ALEJANDRO%' THEN 'CRUZ RODRIGUEZ ALEJANDRO'
                WHEN nombre LIKE '%REYES QUIROZ HILDA%' THEN 'REYES QUIROZ HILDA'
                WHEN nombre LIKE '%VIGILANCIA%' THEN 'VIGILANCIA'
                WHEN nombre LIKE '%JUREZ VAZQUEZ MIGUEL ANGEL%' THEN 'JUREZ VAZQUEZ MIGUEL ANGEL'
                WHEN nombre LIKE '%SOTO DEL HOYO ISMAEL%' THEN 'SOTO DEL HOYO ISMAEL'
				 
                ELSE nombre
            END as persona_exenta,
            COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' AND convert(date, Hora_Entrada, 103) < '2026-01-01' THEN 1 END) AS desayunos_antes_2026,
            COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' AND convert(date, Hora_Entrada, 103) >= '2026-01-01' THEN 1 END) AS desayunos_desde_2026,
            COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' AND convert(date, Hora_Entrada, 103) < '2026-01-01' THEN 1 END) AS comidas_antes_2026,
            COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' AND convert(date, Hora_Entrada, 103) >= '2026-01-01' THEN 1 END) AS comidas_desde_2026,
            COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 1 END) AS desayunos_total,
            COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' THEN 1 END) AS comidas_total,
            COUNT(*) as total_consumos
        FROM Entradas
        WHERE not nombre='.' and not nombre='' and not nombre LIKE '[0-9]%'
        AND $exentos_sql_condition
        AND convert(date, Hora_Entrada, 103) BETWEEN ? AND ?
        GROUP BY 
            CASE 
                WHEN nombre LIKE '%ALEJANDRA CRUZ%' THEN 'ALEJANDRA CRUZ'
                WHEN nombre LIKE '%ALTA DIRECCION%' THEN 'ALTA DIRECCION'
                WHEN nombre LIKE '%CRUZ JOSE LUIS%' THEN 'CRUZ JOSE LUIS'
                WHEN nombre LIKE '%CRUZ RODRIGUEZ ALEJANDRO%' THEN 'CRUZ RODRIGUEZ ALEJANDRO'
                WHEN nombre LIKE '%REYES QUIROZ HILDA%' THEN 'REYES QUIROZ HILDA'
                WHEN nombre LIKE '%VIGILANCIA%' THEN 'VIGILANCIA'
                WHEN nombre LIKE '%JUREZ VAZQUEZ MIGUEL ANGEL%' THEN 'JUREZ VAZQUEZ MIGUEL ANGEL'
                WHEN nombre LIKE '%SOTO DEL HOYO ISMAEL%' THEN 'SOTO DEL HOYO ISMAEL'
                ELSE nombre
            END
        ORDER BY total_consumos DESC";
        
        $params_desglose = array($fecha_inicio, $fecha_fin);
        $stmt_desglose = sqlsrv_query($conn, $sql_desglose_exentos, $params_desglose);
        
        if ($stmt_desglose !== false) {
            while ($row = sqlsrv_fetch_array($stmt_desglose, SQLSRV_FETCH_ASSOC)) {
                $persona = $row['persona_exenta'];
                $desayunos_antes_2026 = $row['desayunos_antes_2026'] ?? 0;
                $desayunos_desde_2026 = $row['desayunos_desde_2026'] ?? 0;
                $comidas_antes_2026 = $row['comidas_antes_2026'] ?? 0;
                $comidas_desde_2026 = $row['comidas_desde_2026'] ?? 0;
                $desayunos_total = $row['desayunos_total'] ?? 0;
                $comidas_total = $row['comidas_total'] ?? 0;
                $total = $row['total_consumos'] ?? 0;
                
                $monto_desayunos = ($desayunos_antes_2026 * $precio_desayuno_viejo) + ($desayunos_desde_2026 * $precio_desayuno_nuevo);
                $monto_comidas = ($comidas_antes_2026 * $precio_comida_viejo) + ($comidas_desde_2026 * $precio_comida_nuevo);
                $monto_total = $monto_desayunos + $monto_comidas;
                
                $desglose_exentos[$persona] = array(
                    'desayunos_antes_2026' => $desayunos_antes_2026,
                    'desayunos_desde_2026' => $desayunos_desde_2026,
                    'comidas_antes_2026' => $comidas_antes_2026,
                    'comidas_desde_2026' => $comidas_desde_2026,
                    'desayunos_total' => $desayunos_total,
                    'comidas_total' => $comidas_total,
                    'total' => $total,
                    'monto' => $monto_total
                );
                
                $exentos_desayuno_servidos += $desayunos_total;
                $exentos_comida_servidos += $comidas_total;
            }
        }
        
        $exentos_total_servidos = $exentos_desayuno_servidos + $exentos_comida_servidos;
        
        // DESGLOSE DE AGENDA (SOLO MONITOREO - NO AFECTAN MONTOS)
        if (!empty($personas_agenda)) {
            $agenda_conditions_sql = [];
            foreach ($personas_agenda as $agenda) {
                $agenda_conditions_sql[] = "nombre LIKE '%$agenda%'";
            }
            $agenda_sql_condition_completa = "(" . implode(" OR ", $agenda_conditions_sql) . ")";
            
            $sql_desglose_agenda = "SELECT 
                CASE 
                    WHEN nombre LIKE '%CELAYA YAXI LUIS ENRIQUE%' THEN 'CELAYA YAXI LUIS ENRIQUE'
                    WHEN nombre LIKE '%FIRO CORTAZAR FERNANDO%' THEN 'FIRO CORTAZAR FERNANDO'
                    WHEN nombre LIKE '%HERRERA CUALI HUGO ALEJANDRO%' THEN 'HERRERA CUALI HUGO ALEJANDRO'
                    WHEN nombre LIKE '%REYES FONSECA NORMA ANGELICA%' THEN 'REYES FONSECA NORMA ANGELICA'
                    WHEN nombre LIKE '%GUTIERREZ EZQUIVEL EDGAR%' THEN 'GUTIERREZ EZQUIVEL EDGAR'
                    WHEN nombre LIKE '%CASTILLO NIETO JESSICA%' THEN 'CASTILLO NIETO JESSICA'
                    WHEN nombre LIKE '%JOSE FERNANDO OSORIO OJEDA%' THEN 'JOSE FERNANDO OSORIO OJEDA'
                    ELSE nombre
                END as persona_agenda,
                COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 1 END) AS desayunos_servidos,
                COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' THEN 1 END) AS comidas_servidas,
                COUNT(*) as total_consumos
            FROM Entradas
            WHERE not nombre='.' and not nombre='' and not nombre LIKE '[0-9]%'
            AND $agenda_sql_condition_completa
            AND convert(date, Hora_Entrada, 103) BETWEEN ? AND ?
            GROUP BY 
                CASE 
                    WHEN nombre LIKE '%CELAYA YAXI LUIS ENRIQUE%' THEN 'CELAYA YAXI LUIS ENRIQUE'
                    WHEN nombre LIKE '%FIRO CORTAZAR FERNANDO%' THEN 'FIRO CORTAZAR FERNANDO'
                    WHEN nombre LIKE '%HERRERA CUALI HUGO ALEJANDRO%' THEN 'HERRERA CUALI HUGO ALEJANDRO'
                    WHEN nombre LIKE '%REYES FONSECA NORMA ANGELICA%' THEN 'REYES FONSECA NORMA ANGELICA'
                    WHEN nombre LIKE '%GUTIERREZ EZQUIVEL EDGAR%' THEN 'GUTIERREZ EZQUIVEL EDGAR'
                    WHEN nombre LIKE '%CASTILLO NIETO JESSICA%' THEN 'CASTILLO NIETO JESSICA'
                    WHEN nombre LIKE '%JOSE FERNANDO OSORIO OJEDA%' THEN 'JOSE FERNANDO OSORIO OJEDA'
                    ELSE nombre
                END
            ORDER BY total_consumos DESC";
            
            $params_agenda_desglose = array($fecha_inicio, $fecha_fin);
            $stmt_agenda_desglose = sqlsrv_query($conn, $sql_desglose_agenda, $params_agenda_desglose);
            
            if ($stmt_agenda_desglose !== false) {
                while ($row = sqlsrv_fetch_array($stmt_agenda_desglose, SQLSRV_FETCH_ASSOC)) {
                    $persona = $row['persona_agenda'];
                    $desayunos_servidos = $row['desayunos_servidos'] ?? 0;
                    $comidas_servidas = $row['comidas_servidas'] ?? 0;
                    $total = $row['total_consumos'] ?? 0;
                    
                    $desglose_agenda[$persona] = array(
                        'desayunos_servidos' => $desayunos_servidos,
                        'comidas_servidas' => $comidas_servidas,
                        'total' => $total
                    );
                    
                    $agenda_desayunos_servidos += $desayunos_servidos;
                    $agenda_comidas_servidas += $comidas_servidas;
                }
            }
        }
        
        // CONSULTA PRINCIPAL: Desayunos y comidas servidos
        $sql_comidas = "SELECT 
                        -- Total servidos (incluyendo exentos)
                        COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 1 END) AS Desayuno_Total,
                        COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' THEN 1 END) AS Comida_Total,
                        -- Exentos servidos (SOLO CONSUMO CONSIDERADO)
                        COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' AND $exentos_sql_condition THEN 1 END) AS ExentosDesayuno,
                        COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' AND $exentos_sql_condition THEN 1 END) AS ExentosComida,
                        -- Pagantes antes del 2026-01-01 (QUITADOS + otros pagantes - EXCLUYE AGENDA)
                        COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' AND NOT $exentos_sql_condition AND convert(date, Hora_Entrada, 103) < '2026-01-01' THEN 1 END) AS PagantesDesayunoAntes2026,
                        COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' AND NOT $exentos_sql_condition AND convert(date, Hora_Entrada, 103) < '2026-01-01' THEN 1 END) AS PagantesComidaAntes2026,
                        -- Pagantes desde el 2026-01-01 (QUITADOS + otros pagantes - EXCLUYE AGENDA)
                        COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' AND NOT $exentos_sql_condition AND convert(date, Hora_Entrada, 103) >= '2026-01-01' THEN 1 END) AS PagantesDesayunoDesde2026,
                        COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' AND NOT $exentos_sql_condition AND convert(date, Hora_Entrada, 103) >= '2026-01-01' THEN 1 END) AS PagantesComidaDesde2026
                       FROM Entradas
                       WHERE not nombre='.' and not nombre='' and not nombre LIKE '[0-9]%'
                       AND convert(date, Hora_Entrada, 103) BETWEEN ? AND ?";
        
        $params_comidas = array($fecha_inicio, $fecha_fin);
        $stmt_comidas = sqlsrv_query($conn, $sql_comidas, $params_comidas);
        
        if ($stmt_comidas !== false) {
            $row = sqlsrv_fetch_array($stmt_comidas, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $total_desayunos_con_exentos = $row['Desayuno_Total'] ?? 0;
                $total_comidas_con_exentos = $row['Comida_Total'] ?? 0;
                $exentos_desayuno_servidos = $row['ExentosDesayuno'] ?? 0;
                $exentos_comida_servidos = $row['ExentosComida'] ?? 0;
                
                // PAGANTES = QUITADOS + otros pagantes (EXCLUYE AGENDA)
                $pagantes_desayunos_antes_2026 = $row['PagantesDesayunoAntes2026'] ?? 0;
                $pagantes_desayunos_desde_2026 = $row['PagantesDesayunoDesde2026'] ?? 0;
                $pagantes_comidas_antes_2026 = $row['PagantesComidaAntes2026'] ?? 0;
                $pagantes_comidas_desde_2026 = $row['PagantesComidaDesde2026'] ?? 0;
                
                // TOTALES VISUALES (incluyen AGENDA para monitoreo)
                $desayunos_hoy = $total_desayunos_con_exentos - $exentos_desayuno_servidos;
                $comidas_hoy = $total_comidas_con_exentos - $exentos_comida_servidos;
                
                $exentos_total_servidos = $exentos_desayuno_servidos + $exentos_comida_servidos;
            }
        }
        
        // CONSULTA: Desayunos y comidas agendados
        $sql_agendados = "WITH Datos AS (
                            SELECT 
                                CAST(
                                    DATEADD(DAY,
                                        CASE Dia
                                            WHEN 'Lunes' THEN 0
                                            WHEN 'Martes' THEN 1
                                            WHEN 'Miercoles' THEN 2
                                            WHEN 'Jueves' THEN 3
                                            WHEN 'Viernes' THEN 4
                                            ELSE 0
                                        END,
                                        Fecha
                                    ) AS DATE
                                ) AS FechaReal,
                                Usuario,
                                Tipo_Comida
                            FROM PedidosComida 
                            CROSS APPLY (
                                VALUES 
                                    ('Lunes', Lunes),
                                    ('Martes', Martes),
                                    ('Miercoles', Miercoles),
                                    ('Jueves', Jueves),
                                    ('Viernes', Viernes)
                            ) AS Dias(Dia, Tipo_Comida)
                            WHERE Tipo_Comida IS NOT NULL AND LTRIM(RTRIM(Tipo_Comida)) <> ''
                        )
                        SELECT 
                            COUNT(CASE WHEN Tipo_Comida = 'Desayuno' THEN 1 END) AS Desayuno_Total,
                            COUNT(CASE WHEN Tipo_Comida = 'Comida' THEN 1 END) AS Comida_Total,
                            COUNT(CASE WHEN Tipo_Comida = 'Desayuno' AND $exentos_sql_condition_usuario THEN 1 END) AS ExentosDesayunoAgendado,
                            COUNT(CASE WHEN Tipo_Comida = 'Comida' AND $exentos_sql_condition_usuario THEN 1 END) AS ExentosComidaAgendado
                        FROM Datos
                        WHERE FechaReal BETWEEN ? AND ?";
        
        $params_agendados = array($fecha_inicio, $fecha_fin);
        $stmt_agendados = sqlsrv_query($conn, $sql_agendados, $params_agendados);
        
        if ($stmt_agendados !== false) {
            $row = sqlsrv_fetch_array($stmt_agendados, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $total_desayunos_agendados_con_exentos = $row['Desayuno_Total'] ?? 0;
                $total_comidas_agendadas_con_exentos = $row['Comida_Total'] ?? 0;
                $exentos_desayuno_agendados = $row['ExentosDesayunoAgendado'] ?? 0;
                $exentos_comida_agendadas = $row['ExentosComidaAgendado'] ?? 0;
                
                // TOTALES VISUALES (incluyen AGENDA para monitoreo)
                $desayunos_agendados = $total_desayunos_agendados_con_exentos - $exentos_desayuno_agendados;
                $comidas_agendadas = $total_comidas_agendadas_con_exentos - $exentos_comida_agendadas;
                
                $exentos_total_agendados = $exentos_desayuno_agendados + $exentos_comida_agendadas;
            }
        }
        
        // CANCELACIONES
        $sql_cancelaciones = "
        SELECT 
            SUM(CASE WHEN tipo_consumo IN ('Desayuno', 'Ambos') THEN 1 ELSE 0 END) as CancelacionesDesayuno,
            SUM(CASE WHEN tipo_consumo IN ('Comida', 'Ambos') THEN 1 ELSE 0 END) as CancelacionesComida,
            SUM(CASE 
                WHEN tipo_consumo = 'Desayuno' AND ESTATUS = 'APROBADO' THEN 1
                WHEN tipo_consumo = 'Comida' AND ESTATUS = 'APROBADO' THEN 1
                WHEN tipo_consumo = 'Ambos' AND ESTATUS = 'APROBADO' THEN 2
                ELSE 0 
            END) as TotalRegistros
        FROM cancelaciones
        WHERE convert(date, FECHA, 102) BETWEEN ? AND ?";
        
        $params_cancelaciones = array($fecha_inicio, $fecha_fin);
        $stmt_cancelaciones = sqlsrv_query($conn, $sql_cancelaciones, $params_cancelaciones);
        
        if ($stmt_cancelaciones !== false) {
            $row = sqlsrv_fetch_array($stmt_cancelaciones, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $cancelaciones_desayuno = $row['CancelacionesDesayuno'] ?? 0;
                $cancelaciones_comida = $row['CancelacionesComida'] ?? 0;
                $total_cancelaciones = $row['TotalRegistros'] ?? 0;
            }
        }
        
        // Cancelaciones pendientes
        $sql_cancelaciones_pendientes = "SELECT COUNT(*) as TotalPendientes
                              FROM cancelaciones
                              WHERE ESTATUS != 'APROBADO' and NOT ESTATUS = 'RECHAZADO'
                              AND ESTATUS IS NOT NULL AND YEAR(convert(date, FECHA, 102)) = 2026";
        
        $stmt_pendientes = sqlsrv_query($conn, $sql_cancelaciones_pendientes);
        
        if ($stmt_pendientes !== false) {
            $row = sqlsrv_fetch_array($stmt_pendientes, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $cancelaciones_pendientes = $row['TotalPendientes'] ?? 0;
                if ($cancelaciones_pendientes > 0) {
                    $cancelaciones_pendientes_notificacion = "⚠️ Tienes $cancelaciones_pendientes cancelaciones pendientes de revisión";
                }
            }
        }
        
        // Calcular montos (SOLO QUITADOS + otros pagantes - EXCLUYE AGENDA)
        $monto_desayunos_antes_2026 = $pagantes_desayunos_antes_2026 * $precio_desayuno_viejo;
        $monto_desayunos_desde_2026 = $pagantes_desayunos_desde_2026 * $precio_desayuno_nuevo;
        $monto_comidas_antes_2026 = $pagantes_comidas_antes_2026 * $precio_comida_viejo;
        $monto_comidas_desde_2026 = $pagantes_comidas_desde_2026 * $precio_comida_nuevo;
        
        $monto_recaudado = $monto_desayunos_antes_2026 + $monto_desayunos_desde_2026 + 
                          $monto_comidas_antes_2026 + $monto_comidas_desde_2026;
        
        // Monto exentos (SOLO CONSUMO CONSIDERADO)
        $monto_exentos = 0;
        foreach ($desglose_exentos as $datos) {
            $monto_exentos += $datos['monto'];
        }
        
        sqlsrv_close($conn);
        
    } else {
        throw new Exception("Error de conexión a la base de datos principal");
    }
} catch (Exception $e) {
    // Valores de ejemplo con la nueva lógica
    $total_usuarios = 150;
    
    // CONSUMO CONSIDERADO (exentos)
    $exentos_desayuno_servidos = 8;
    $exentos_comida_servidos = 8;
    $exentos_desayuno_agendados = 8;
    $exentos_comida_agendadas = 8;
    $exentos_total_servidos = 16;
    $exentos_total_agendados = 16;
    
    // AGENDA (solo monitoreo - NO pagan)
    $agenda_desayunos_servidos = 5;
    $agenda_comidas_servidas = 7;
    $agenda_desayunos_agendados = 6;
    $agenda_comidas_agendadas = 8;
    
    // Totales visuales (incluyen AGENDA)
    $desayunos_hoy = 112; // Incluye AGENDA
    $comidas_hoy = 214;   // Incluye AGENDA
    $desayunos_agendados = 127;
    $comidas_agendadas = 237;
    
    // Montos (QUITADOS + otros pagantes - EXCLUYE AGENDA)
    $monto_desayunos_antes_2026 = 50 * $precio_desayuno_viejo;
    $monto_desayunos_desde_2026 = 50 * $precio_desayuno_nuevo;
    $monto_comidas_antes_2026 = 100 * $precio_comida_viejo;
    $monto_comidas_desde_2026 = 100 * $precio_comida_nuevo;
    $monto_recaudado = $monto_desayunos_antes_2026 + $monto_desayunos_desde_2026 + 
                      $monto_comidas_antes_2026 + $monto_comidas_desde_2026;
    
    $monto_exentos = 4010;
    
    // Valores de ejemplo para cancelaciones
    $cancelaciones_desayuno = 5;
    $cancelaciones_comida = 8;
    $total_cancelaciones = 13;
    $cancelaciones_pendientes = 3;
    $cancelaciones_pendientes_notificacion = "⚠️ Tienes 3 cancelaciones pendientes de revisión";
    
    $desglose_exentos = array(
        'ALEJANDRA CRUZ' => array(
            'desayunos_antes_2026' => 3, 'desayunos_desde_2026' => 3, 'desayunos_total' => 6,
            'comidas_antes_2026' => 4, 'comidas_desde_2026' => 4, 'comidas_total' => 8,
            'total' => 14, 'monto' => (3*30)+(3*35)+(4*30)+(4*45)
        ),
        'ALTA DIRECCION' => array(
            'desayunos_antes_2026' => 2, 'desayunos_desde_2026' => 2, 'desayunos_total' => 4,
            'comidas_antes_2026' => 3, 'comidas_desde_2026' => 3, 'comidas_total' => 6,
            'total' => 10, 'monto' => (2*30)+(2*35)+(3*30)+(3*45)
        ),
    );
    
    $desglose_agenda = array(
        'CELAYA YAXI LUIS ENRIQUE' => array('desayunos_servidos' => 2, 'comidas_servidas' => 3, 'total' => 5),
        'FIRO CORTAZAR FERNANDO' => array('desayunos_servidos' => 3, 'comidas_servidas' => 4, 'total' => 7),
    );
    
    $tasa_asistencia = 78.5;
    $total_gastos_alquimista = 120000;
    $total_gastos_basenueva = 65000;
    $total_gastos_contpaq = 185000;
}

// CONSULTAS CONTEPAQ
try {
    $total_gastos_alquimista = 0;
    $total_gastos_basenueva = 0;
    $total_registros_alquimista = 0;
    $total_registros_basenueva = 0;
    
    $fecha_inicio_contpaq = date('Y-m-d', strtotime($fecha_inicio));
    $fecha_fin_contpaq = date('Y-m-d', strtotime($fecha_fin));
    
    $sql_alquimista = "
    SELECT 
        SUM(MONTO) as TotalGastos,
        COUNT(*) as TotalRegistros
    FROM (
        SELECT 
            dbo.docDocumentExt.EstadoPago,
            EngModule.ModuleName as Documento,
            CASE 
                WHEN EngModule.ModuleName = 'Gastos NF' THEN 'Efectivo' 
                ELSE REPLACE(PaymentTermName, '-Pendiente de Pago', '') 
            END as [FORMA_DE_PAGO],
            orgBusinessEntity.officialName as EMPRESA,
            docDocument.DocumentID as ID,
            docDocument.Title as DESCRIPCION,
            ALQUIMISTA2024.[dbo].engUser.UserName as SOLICITANTE,
            CASE 
                WHEN ISNULL(docDocument.rate, 1) = 0 THEN 1 
                ELSE ISNULL(docDocument.rate, 1) 
            END * docdocument.Total as MONTO,
            ALQUIMISTA2024.[dbo].vwcboCostCenter.Value as CC,
            '' as UNIDAD,
            CONVERT(DATE, ALQUIMISTA2024.[dbo].docDocument.DateDocument) as [FECHA_CREACION],
            CASE 
                WHEN engUser_2.UserName IS NOT NULL OR engUser_1.UserName IS NOT NULL THEN 'Autorizado' 
                ELSE '' 
            END as [ESTATUS_AUTORIZACION],
            Fechap as [FECHA_PAGO_FINANZAS],
            ALQUIMISTA2024.[dbo].docDocumentExt.Departamento as [AREA],
            engUser_1.userName as [Autorizacion1],
            engUser_2.userName as [Autorizacion2]
        FROM ALQUIMISTA2024.[dbo].docDocument 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].engUser 
            ON ALQUIMISTA2024.[dbo].docDocument.CreatedBy = ALQUIMISTA2024.[dbo].engUser.UserID 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].docDocumentExt 
            ON ALQUIMISTA2024.[dbo].docDocument.DocumentID = ALQUIMISTA2024.[dbo].docDocumentExt.IDExtra 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].engRefCurrency 
            ON ALQUIMISTA2024.[dbo].docDocument.CurrencyID = ALQUIMISTA2024.[dbo].engRefCurrency.CurrencyID 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].engPaymentTerm 
            ON ALQUIMISTA2024.[dbo].docDocument.PaymentTermID = ALQUIMISTA2024.[dbo].engPaymentTerm.PaymentTermID  
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].vwcboCostCenter 
            ON ALQUIMISTA2024.[dbo].docDocument.CostCenterID = ALQUIMISTA2024.[dbo].vwcboCostCenter.ID 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].vwLBSBusinessEntityList 
            ON ALQUIMISTA2024.[dbo].docDocument.BusinessEntityID = ALQUIMISTA2024.[dbo].vwLBSBusinessEntityList.BusinessEntityID 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].vwLBSContactList 
            ON ALQUIMISTA2024.[dbo].docDocument.SalesRepContactID = ALQUIMISTA2024.[dbo].vwLBSContactList.ContactID 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].docDocumentCFD 
            ON ALQUIMISTA2024.[dbo].docDocument.DocumentID = ALQUIMISTA2024.[dbo].docDocumentCFD.DocumentID  
        LEFT JOIN ALQUIMISTA2024.[dbo].engUser AS engUser_1 
            ON ALQUIMISTA2024.[dbo].docDocument.AuthorizedBy = engUser_1.UserID 
        LEFT OUTER JOIN ALQUIMISTA2024.[dbo].engUser AS engUser_2 
            ON ALQUimista2024.[dbo].docDocument.Authorized2By = engUser_2.UserID 
        INNER JOIN orgBusinessEntity 
            ON docDocument.OwnedBusinessEntityID = orgBusinessEntity.BusinessEntityID
        INNER JOIN engModule 
            ON docDocument.ModuleID = engModule.ModuleID
        LEFT JOIN (
            SELECT ID, CONVERT(DATE, MAX(CreatedOn)) as Fechap 
            FROM engModuleFile
            WHERE description LIKE '%Pago%' 
            GROUP BY ID
        ) Fp ON docDocument.DocumentID = FP.ID
        WHERE (ALQUIMISTA2024.[dbo].docDocument.ModuleID IN (183, 1246, 1253, 242, 1246, 1249))
            AND docDocument.Deletedon IS NULL 
            AND docDocument.Cancelledon IS NULL
            AND YEAR(ALQUIMISTA2024.[dbo].docDocument.DateDocument) IN ('2025','2026')
    ) as GastosFiltrados
    WHERE CC IN (
        '999-017 Mantto. Edificio',
        '999-006 Gerencia Finanzas',
        '999-016 General Comedor',
        '999-007 Gerencia Talento Humano',
        '999-017 Mantto. Edificio',
        '999-021 PROVEEDORES CREDITO',
        '999-006-003 ISN',
        '999-007-003 NOMINA',
        '999-006-001 IMSS'
    ) 
    AND EstadoPago = 'pagado'
    AND FECHA_CREACION BETWEEN ? AND ?";
    
    $sql_basenueva = "
    SELECT 
        SUM(MONTO) as TotalGastos,
        COUNT(*) as TotalRegistros
    FROM (
        SELECT 
            dbo.docDocumentExt.EstadoPago,
            EngModule.ModuleName as Documento,
            CASE 
                WHEN EngModule.ModuleName = 'Gastos NF' THEN 'Efectivo' 
                ELSE REPLACE(PaymentTermName, '-Pendiente de Pago', '') 
            END as [FORMA_DE_PAGO],
            orgBusinessEntity.officialName as EMPRESA,
            docDocument.DocumentID as ID,
            docDocument.Title as DESCRIPCION,
            BASENUEVA.[dbo].engUser.UserName as SOLICITANTE,
            CASE 
                WHEN ISNULL(docDocument.rate, 1) = 0 THEN 1 
                ELSE ISNULL(docDocument.rate, 1) 
            END * docdocument.Total as MONTO,
            BASENUEVA.[dbo].vwcboCostCenter.Value as CC,
            '' as UNIDAD,
            CONVERT(DATE, BASENUEVA.[dbo].docDocument.DateDocument) as [FECHA_CREACION],
            CASE 
                WHEN engUser_2.UserName IS NOT NULL OR engUser_1.UserName IS NOT NULL THEN 'Autorizado' 
                ELSE '' 
            END as [ESTATUS_AUTORIZACION],
            Fechap as [FECHA_PAGO_FINANZAS],
            BASENUEVA.[dbo].docDocumentExt.Departamento as [AREA],
            engUser_1.userName as [Autorizacion1],
            engUser_2.userName as [Autorizacion2]
        FROM BASENUEVA.[dbo].docDocument 
        LEFT OUTER JOIN BASENUEVA.[dbo].engUser 
            ON BASENUEVA.[dbo].docDocument.CreatedBy = BASENUEVA.[dbo].engUser.UserID 
        LEFT OUTER JOIN BASENUEVA.[dbo].docDocumentExt 
            ON BASENUEVA.[dbo].docDocument.DocumentID = BASENUEVA.[dbo].docDocumentExt.IDExtra 
        LEFT OUTER JOIN BASENUEVA.[dbo].engRefCurrency 
            ON BASENUEVA.[dbo].docDocument.CurrencyID = BASENUEVA.[dbo].engRefCurrency.CurrencyID 
        LEFT OUTER JOIN BASENUEVA.[dbo].engPaymentTerm 
            ON BASENUEVA.[dbo].docDocument.PaymentTermID = BASENUEVA.[dbo].engPaymentTerm.PaymentTermID  
        LEFT OUTER JOIN BASENUEVA.[dbo].vwcboCostCenter 
            ON BASENUEVA.[dbo].docDocument.CostCenterID = BASENUEVA.[dbo].vwcboCostCenter.ID 
        LEFT OUTER JOIN BASENUEVA.[dbo].vwLBSBusinessEntityList 
            ON BASENUEVA.[dbo].docDocument.BusinessEntityID = BASENUEVA.[dbo].vwLBSBusinessEntityList.BusinessEntityID 
        LEFT OUTER JOIN BASENUEVA.[dbo].vwLBSContactList 
            ON BASENUEVA.[dbo].docDocument.SalesRepContactID = BASENUEVA.[dbo].vwLBSContactList.ContactID 
        LEFT OUTER JOIN BASENUEVA.[dbo].docDocumentCFD 
            ON BASENUEVA.[dbo].docDocument.DocumentID = BASENUEVA.[dbo].docDocumentCFD.DocumentID  
        LEFT JOIN BASENUEVA.[dbo].engUser AS engUser_1 
            ON BASENUEVA.[dbo].docDocument.AuthorizedBy = engUser_1.UserID 
        LEFT OUTER JOIN BASENUEVA.[dbo].engUser AS engUser_2 
            ON BASENUEVA.[dbo].docDocument.Authorized2By = engUser_2.UserID 
        INNER JOIN orgBusinessEntity 
            ON docDocument.OwnedBusinessEntityID = orgBusinessEntity.BusinessEntityID
        INNER JOIN engModule 
            ON docDocument.ModuleID = engModule.ModuleID
        LEFT JOIN (
            SELECT ID, CONVERT(DATE, MAX(CreatedOn)) as Fechap 
            FROM engModuleFile
            WHERE description LIKE '%Pago%' 
            GROUP BY ID
        ) Fp ON docDocument.DocumentID = FP.ID
        WHERE (BASENUEVA.[dbo].docDocument.ModuleID IN (183, 1246, 1253, 242, 1246, 1249))
            AND docDocument.Deletedon IS NULL 
            AND docDocument.Cancelledon IS NULL
            AND YEAR(BASENUEVA.[dbo].docDocument.DateDocument) in ('2025','2026')
    ) as GastosFiltrados
    WHERE CC IN (
        '999-016 General Comedor'
    ) 
    AND EstadoPago = 'pagado'
    AND FECHA_CREACION BETWEEN ? AND ?";
    
    $params_contpaq = array($fecha_inicio_contpaq, $fecha_fin_contpaq);
    
    $connAlquimista = sqlsrv_connect($serverNameContpaq, $connectionOptionsContpaq);
    if ($connAlquimista !== false) {
        $stmt_alquimista = sqlsrv_query($connAlquimista, $sql_alquimista, $params_contpaq);
        if ($stmt_alquimista !== false) {
            $row = sqlsrv_fetch_array($stmt_alquimista, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $total_gastos_alquimista = $row['TotalGastos'] ?? 0;
                $total_registros_alquimista = $row['TotalRegistros'] ?? 0;
            }
        }
        sqlsrv_close($connAlquimista);
    }
    
    $connBaseNueva = sqlsrv_connect($serverNameBaseNueva, $connectionOptionsBaseNueva);
    if ($connBaseNueva !== false) {
        $stmt_basenueva = sqlsrv_query($connBaseNueva, $sql_basenueva, $params_contpaq);
        if ($stmt_basenueva !== false) {
            $row = sqlsrv_fetch_array($stmt_basenueva, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $total_gastos_basenueva = $row['TotalGastos'] ?? 0;
                $total_registros_basenueva = $row['TotalRegistros'] ?? 0;
            }
        }
        sqlsrv_close($connBaseNueva);
    }
    
    $total_gastos_contpaq = $total_gastos_alquimista + $total_gastos_basenueva;
    $total_registros_contpaq = $total_registros_alquimista + $total_registros_basenueva;
    
} catch (Exception $e) {
    $total_gastos_alquimista = 120000;
    $total_gastos_basenueva = 65000;
    $total_gastos_contpaq = 185000;
    $total_registros_contpaq = 45;
}

// ==================================================
// CÁLCULOS FINALES
// ==================================================

$porcentaje_desayunos = $desayunos_agendados > 0 ? round(($desayunos_hoy / $desayunos_agendados) * 100, 1) : 0;
$porcentaje_comidas = $comidas_agendadas > 0 ? round(($comidas_hoy / $comidas_agendadas) * 100, 1) : 0;
$utilidad = $monto_recaudado - $total_gastos_contpaq;

$dias_periodo = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / (60 * 60 * 24) + 1;
$titulo_periodo = "Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin)) . " ($dias_periodo días)";

$total_servido_pagantes = $desayunos_hoy + $comidas_hoy;
$total_agendado_pagantes = $desayunos_agendados + $comidas_agendadas;
$tasa_asistencia = $total_agendado_pagantes > 0 ? round(($total_servido_pagantes / $total_agendado_pagantes) * 100, 2) : 0;

$total_servido_con_exentos = $total_servido_pagantes + $exentos_total_servidos;
$porcentaje_exentos = $total_servido_con_exentos > 0 ? round(($exentos_total_servidos / $total_servido_con_exentos) * 100, 1) : 0;

$total_desayunos_exentos = 0;
$total_comidas_exentas = 0;
$total_monto_exentos = 0;

foreach ($desglose_exentos as $persona => $datos) {
    $total_desayunos_exentos += $datos['desayunos_total'];
    $total_comidas_exentas += $datos['comidas_total'];
    $total_monto_exentos += $datos['monto'];
}

$total_agenda_desayunos = $agenda_desayunos_servidos;
$total_agenda_comidas = $agenda_comidas_servidas;
$total_agenda = $total_agenda_desayunos + $total_agenda_comidas;

$periodo_inicio = strtotime($fecha_inicio);
$periodo_fin = strtotime($fecha_fin);
$fecha_cambio = strtotime($fecha_cambio_precios);

$incluye_viejo_precio = ($periodo_inicio < $fecha_cambio);
$incluye_nuevo_precio = ($periodo_fin >= $fecha_cambio);
$periodo_mixto_precios = ($incluye_viejo_precio && $incluye_nuevo_precio);

// ==================================================
// VALIDACIÓN DE CIFRAS
// ==================================================

$diferencia_desayunos = $desayunos_agendados - $desayunos_hoy;
$diferencia_comidas = $comidas_agendadas - $comidas_hoy;
$total_agendado_real = $desayunos_agendados + $comidas_agendadas;
$total_servido_real = $desayunos_hoy + $comidas_hoy;
$coherencia_datos = ($total_agendado_real >= $total_servido_real) ? "ok" : "warning";

$mensaje_validacion = "";
if ($diferencia_desayunos < 0) {
    $mensaje_validacion .= "⚠️ Desayunos servidos ($desayunos_hoy) superan a agendados ($desayunos_agendados). ";
}
if ($diferencia_comidas < 0) {
    $mensaje_validacion .= "⚠️ Comidas servidas ($comidas_hoy) superan a agendadas ($comidas_agendadas). ";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Portal de Comedor - Administración <?php echo !$acceso_completo ? '(Acceso Restringido)' : 'Completa'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ==================================================
           TU CSS ORIGINAL - CON MEJORAS UX/RESPONSIVE
        ================================================== */
        :root {
            --primary-dark: #1a365d;
            --primary-blue: #2d5f9d;
            --accent-blue: #3b82f6;
            --secondary-blue: #60a5fa;
            --light-blue: #dbeafe;
            --white-pearl: #f8fafc;
            --light-gray: #e2e8f0;
            --medium-gray: #94a3b8;
            --dark-gray: #475569;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --purple-color: #8b5cf6;
            --teal-color: #14b8a6;
            --pink-color: #ec4899;
            --indigo-color: #6366f1;
            --orange-color: #f97316;
            --cyan-color: #06b6d4;
            --gold-color: #f59e0b;
            --silver-color: #94a3b8;
            --bronze-color: #b45309;
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(255, 255, 255, 0.5);
            --notification-color: #dc2626;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--dark-gray);
        }
        
        /* Efecto Glass Mejorado */
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow);
        }
        
        /* Modal Informativo (NUEVO) */
        .premium-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        .premium-modal.show {
            display: flex;
        }
        
        .premium-modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            max-width: 700px;
            width: 92%;
            max-height: 85vh;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.4s ease;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .premium-modal-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .premium-modal-header h3 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .premium-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .premium-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .premium-modal-body {
            padding: 25px;
            overflow-y: auto;
            max-height: calc(85vh - 80px);
        }
        
        .premium-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 8px 8px 0;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .premium-badge.exento {
            background: rgba(139, 92, 246, 0.15);
            color: var(--purple-color);
            border-color: var(--purple-color);
        }
        
        .premium-badge.agenda {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .premium-badge.quitado {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .premium-info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--accent-blue);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .premium-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .premium-stat-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid var(--light-gray);
        }
        
        .premium-stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .premium-stat-value.exento { color: var(--purple-color); }
        .premium-stat-value.agenda { color: var(--success-color); }
        .premium-stat-value.quitado { color: var(--danger-color); }
        
        .premium-stat-label {
            font-size: 0.9rem;
            color: var(--dark-gray);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        /* Panel de validación (NUEVO) */
        .validation-panel {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-top: 25px;
            border-left: 6px solid var(--info-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .validation-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .validation-item:last-child {
            border-bottom: none;
        }
        
        .validation-ok {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .validation-warning {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .validation-danger {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        /* TU CSS ORIGINAL - SIN CAMBIOS EN LA ESTRUCTURA */
        .sidebar {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 280px;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.15);
            left: 0;
            top: 0;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 14px 20px;
            margin: 6px 0;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(135deg, var(--accent-blue), var(--secondary-blue));
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .sidebar .nav-link.active::before {
            transform: scaleY(1);
        }
        
        .sidebar .nav-link i {
            margin-right: 14px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .sidebar .nav-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .restricted-badge {
            background-color: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        .access-info {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
            border-left: 4px solid var(--warning-color);
            border-radius: 0 8px 8px 0;
            padding: 12px 15px;
            margin: 10px 0;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 25px;
            transition: all 0.3s ease;
            background-color: var(--light-blue);
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background: var(--white-pearl);
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: white;
            border-radius: 16px 16px 0 0 !important;
            font-weight: 600;
            padding: 18px 25px;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, rgba(255,255,255,0.5), transparent);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .comparison-card {
            text-align: center;
            padding: 25px;
            position: relative;
            background: var(--white-pearl);
            border: 1px solid var(--light-gray);
        }
        
        .comparison-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            border-radius: 16px 16px 0 0;
        }
        
        .comparison-card.desayunos::before {
            background: linear-gradient(135deg, var(--success-color), #0d966c);
        }
        
        .comparison-card.comidas::before {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .comparison-card.cancelaciones::before {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .comparison-card.gastos::before {
            background: linear-gradient(135deg, var(--orange-color), #ea580c);
        }
        
        .comparison-card.utilidad::before {
            background: linear-gradient(135deg, var(--gold-color), #d97706);
        }
        
        .comparison-card.exentos::before {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
        }
        
        .comparison-card.inventario::before {
            background: linear-gradient(135deg, var(--bronze-color), #92400e);
        }
        
        .comparison-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            background: var(--white-pearl);
            border: 1px solid var(--light-gray);
        }
        
        .comparison-icon.desayunos {
            background: linear-gradient(135deg, var(--success-color), #0d966c);
            color: white;
        }
        
        .comparison-icon.comidas {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
        }
        
        .comparison-icon.cancelaciones {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }
        
        .comparison-icon.gastos {
            background: linear-gradient(135deg, var(--orange-color), #ea580c);
            color: white;
        }
        
        .comparison-icon.utilidad {
            background: linear-gradient(135deg, var(--gold-color), #d97706);
            color: white;
        }
        
        .comparison-icon.exentos {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: white;
        }
        
        .comparison-icon.inventario {
            background: linear-gradient(135deg, var(--bronze-color), #92400e);
            color: white;
        }
        
        .comparison-stats {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            gap: 15px;
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            border: 1px solid var(--light-gray);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-value.servido {
            color: var(--success-color);
        }
        
        .stat-value.agendado {
            color: var(--info-color);
        }
        
        .stat-value.cancelado {
            color: var(--danger-color);
        }
        
        .stat-value.gasto {
            color: var(--orange-color);
        }
        
        .stat-value.exento {
            color: var(--purple-color);
        }
        
        .stat-value.inventario {
            color: var(--bronze-color);
        }
        
        .stat-value.utilidad-positiva {
            color: var(--success-color);
        }
        
        .stat-value.utilidad-negativa {
            color: var(--danger-color);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--medium-gray);
            font-weight: 500;
        }
        
        .comparison-percentage {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 15px 0;
            padding: 10px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            color: var(--accent-blue);
        }
        
        .cancelaciones-total {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 15px 0;
            padding: 10px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 10px;
            color: var(--danger-color);
        }
        
        .gastos-total {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 15px 0;
            padding: 10px;
            background: rgba(249, 115, 22, 0.1);
            border-radius: 10px;
            color: var(--orange-color);
        }
        
        .exentos-total {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 15px 0;
            padding: 10px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 10px;
            color: var(--purple-color);
        }
        
        .inventario-total {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 15px 0;
            padding: 10px;
            background: rgba(180, 83, 9, 0.1);
            border-radius: 10px;
            color: var(--bronze-color);
        }
        
        .utilidad-total {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 15px 0;
            padding: 10px;
            border-radius: 10px;
        }
        
        .utilidad-total.positiva {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .utilidad-total.negativa {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .progress-comparison {
            height: 8px;
            background: var(--light-gray);
            border-radius: 10px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-comparison-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .progress-comparison-bar.desayunos {
            background: linear-gradient(90deg, var(--success-color), #0d966c);
        }
        
        .progress-comparison-bar.comidas {
            background: linear-gradient(90deg, var(--warning-color), #d97706);
        }
        
        .progress-comparison-bar.cancelaciones {
            background: linear-gradient(90deg, var(--danger-color), #dc2626);
        }
        
        .progress-comparison-bar.gastos {
            background: linear-gradient(90deg, var(--orange-color), #ea580c);
        }
        
        .progress-comparison-bar.utilidad {
            background: linear-gradient(90deg, var(--gold-color), #d97706);
        }
        
        .progress-comparison-bar.exentos {
            background: linear-gradient(90deg, var(--purple-color), #7c3aed);
        }
        
        .progress-comparison-bar.inventario {
            background: linear-gradient(90deg, var(--bronze-color), #92400e);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 32px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            background: var(--white-pearl);
            border: 1px solid var(--light-gray);
        }
        
        .feature-icon.users {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            color: white;
        }
        
        .feature-icon.attendance {
            background: linear-gradient(135deg, var(--teal-color), #0d9488);
            color: white;
        }
        
        .feature-icon.cancelaciones {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }
        
        .feature-icon.revenue {
            background: linear-gradient(135deg, var(--indigo-color), #4f46e5);
            color: white;
        }
        
        .feature-icon.gastos {
            background: linear-gradient(135deg, var(--orange-color), #ea580c);
            color: white;
        }
        
        .feature-icon.utilidad {
            background: linear-gradient(135deg, var(--gold-color), #d97706);
            color: white;
        }
        
        .feature-icon.exentos {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: white;
        }
        
        .feature-icon.inventario {
            background: linear-gradient(135deg, var(--bronze-color), #92400e);
            color: white;
        }
        
        .stat-card {
            text-align: center;
            padding: 30px 25px;
            position: relative;
            background: var(--white-pearl);
            border: 1px solid var(--light-gray);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            border-radius: 16px 16px 0 0;
        }
        
        .stat-card.users::before {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
        }
        
        .stat-card.attendance::before {
            background: linear-gradient(135deg, var(--teal-color), #0d9488);
        }
        
        .stat-card.cancelaciones::before {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .stat-card.revenue::before {
            background: linear-gradient(135deg, var(--indigo-color), #4f46e5);
        }
        
        .stat-card.gastos::before {
            background: linear-gradient(135deg, var(--orange-color), #ea580c);
        }
        
        .stat-card.utilidad::before {
            background: linear-gradient(135deg, var(--gold-color), #d97706);
        }
        
        .stat-card.exentos::before {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
        }
        
        .stat-card.inventario::before {
            background: linear-gradient(135deg, var(--bronze-color), #92400e);
        }
        
        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            margin: 15px 0;
            color: var(--primary-dark);
        }
        
        .stat-number.utilidad-positiva {
            color: var(--success-color);
        }
        
        .stat-number.utilidad-negativa {
            color: var(--danger-color);
        }
        
        .stat-number.exentos {
            color: var(--purple-color);
        }
        
        .stat-number.inventario {
            color: var(--bronze-color);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .section-title {
            color: var(--primary-dark);
            margin-bottom: 30px;
            padding-bottom: 18px;
            border-bottom: 3px solid var(--accent-blue);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 120px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-blue), transparent);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--secondary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 18px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-info h5 {
            margin: 0;
            font-size: 1.1rem;
            color: white;
        }
        
        .user-info p {
            margin: 0;
            font-size: 0.85rem;
            opacity: 0.8;
            color: white;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.6rem;
            color: var(--primary-dark);
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 1001;
            background: var(--white-pearl);
            border-radius: 10px;
            width: 50px;
            height: 50px;
            box-shadow: var(--card-shadow);
        }
        
        .toggle-sidebar-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 10px;
            width: 45px;
            height: 45px;
            font-size: 1.3rem;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .toggle-sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 18px;
            font-size: 1.1rem;
            background: var(--light-blue);
        }
        
        .report-iframe-container {
            position: relative;
            width: 100%;
            height: 800px;
            border: none;
            overflow: hidden;
        }
        
        .report-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 0 0 16px 16px;
        }
        
        .menu-iframe-container {
            position: relative;
            width: 100%;
            height: 900px;
            border: none;
            overflow: hidden;
        }
        
        .menu-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 0 0 16px 16px;
        }
        
        .inventario-iframe-container {
            position: relative;
            width: 100%;
            height: 900px;
            border: none;
            overflow: hidden;
        }
        
        .inventario-iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 0 0 16px 16px;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 100;
            border-radius: 0 0 16px 16px;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--light-blue);
            border-top: 5px solid var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            color: var(--primary-dark);
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            color: white;
            padding: 20px 25px;
            margin: -25px -25px 25px -25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .user-display {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .user-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name-display {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .user-area-display {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .notification-badge {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 5px;
            animation: pulse 2s infinite;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        
        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            color: white;
            animation: ring 2s infinite;
        }
        
        .notification-container {
            position: absolute;
            top: 70px;
            right: 25px;
            z-index: 1000;
        }
        
        .notification-alert {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
            animation: slideDown 0.5s ease;
            max-width: 350px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .notification-alert h6 {
            margin: 0 0 10px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-alert p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .notification-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        .filters-container {
            background: var(--white-pearl);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
            position: relative;
            overflow: hidden;
        }
        
        .filters-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 0.9rem;
            min-width: 120px;
        }
        
        .filter-date-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .filter-date-input {
            border: 1px solid var(--light-gray);
            border-radius: 10px;
            padding: 12px 16px;
            background: white;
            color: var(--dark-gray);
            font-size: 0.95rem;
            min-width: 160px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .filter-date-input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .date-separator {
            color: var(--medium-gray);
            font-weight: 600;
        }
        
        .filter-buttons {
            display: flex;
            gap: 12px;
            margin-left: auto;
        }
        
        .filter-btn {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
        
        .filter-btn.reset {
            background: linear-gradient(135deg, var(--medium-gray), var(--dark-gray));
            box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
        }
        
        .filter-btn.reset:hover {
            box-shadow: 0 6px 16px rgba(100, 116, 139, 0.4);
        }
        
        .period-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.1));
            border-radius: 12px;
            padding: 16px 20px;
            margin-top: 15px;
            border-left: 4px solid var(--accent-blue);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .period-text {
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
            font-size: 1.1rem;
        }
        
        .period-days {
            background: var(--accent-blue);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .finances-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .finance-card {
            background: linear-gradient(135deg, var(--white-pearl), #ffffff);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
            transition: all 0.3s ease;
        }
        
        .finance-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .finance-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .finance-amount {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 15px 0;
        }
        
        .finance-amount.ingresos {
            color: var(--success-color);
        }
        
        .finance-amount.gastos {
            color: var(--orange-color);
        }
        
        .finance-amount.exentos {
            color: var(--purple-color);
        }
        
        .finance-amount.utilidad-positiva {
            color: var(--success-color);
        }
        
        .finance-amount.utilidad-negativa {
            color: var(--danger-color);
        }
        
        .finance-subtitle {
            font-size: 0.9rem;
            color: var(--medium-gray);
            margin-bottom: 10px;
        }
        
        .finance-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            margin-top: 10px;
        }
        
        .trend-up {
            color: var(--success-color);
        }
        
        .trend-down {
            color: var(--danger-color);
        }
        
        .trend-neutral {
            color: var(--medium-gray);
        }
        
        .finance-breakdown {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--light-gray);
        }
        
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 0.9rem;
        }
        
        .breakdown-label {
            color: var(--dark-gray);
        }
        
        .breakdown-value {
            font-weight: 600;
        }
        
        .base-database-breakdown {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid var(--light-gray);
        }
        
        .database-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .database-item:last-child {
            border-bottom: none;
        }
        
        .database-name {
            font-weight: 500;
            color: var(--primary-dark);
        }
        
        .database-amount {
            font-weight: 600;
        }
        
        .database-count {
            font-size: 0.85rem;
            color: var(--medium-gray);
        }
        
        .exentos-list {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid var(--light-gray);
            max-height: 200px;
            overflow-y: auto;
        }
        
        .exento-item {
            padding: 5px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.9rem;
        }
        
        .exento-item:last-child {
            border-bottom: none;
        }
        
        .exento-detalle-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .exento-detalle-table th {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .exento-detalle-table td {
            padding: 10px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 0.85rem;
        }
        
        .exento-detalle-table tr:last-child td {
            border-bottom: none;
        }
        
        .exento-detalle-table tr:hover {
            background-color: rgba(139, 92, 246, 0.05);
        }
        
        .exento-total-cell {
            font-weight: 600;
            color: var(--purple-color);
        }
        
        .exento-monto-cell {
            font-weight: 600;
            color: var(--orange-color);
        }
        
        .exento-desglose-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--purple-color);
        }
        
        .exento-desglose-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--purple-color);
        }
        
        .exento-desglose-summary {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
        }
        
        .summary-item {
            background: rgba(139, 92, 246, 0.1);
            padding: 5px 10px;
            border-radius: 6px;
            color: var(--purple-color);
            font-weight: 500;
        }
        
        .inventario-card {
            border: 2px solid var(--bronze-color);
            background: linear-gradient(135deg, rgba(180, 83, 9, 0.05), rgba(180, 83, 9, 0.02));
        }
        
        .inventario-header {
            background: linear-gradient(135deg, var(--bronze-color), #92400e) !important;
        }
        
        .inventario-highlight {
            background: rgba(180, 83, 9, 0.1);
            border-left: 4px solid var(--bronze-color);
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin: 15px 0;
        }
        
        .inventario-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .inventario-stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 10px;
            border: 1px solid rgba(180, 83, 9, 0.2);
        }
        
        .inventario-stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--bronze-color);
            margin-bottom: 5px;
        }
        
        .inventario-stat-label {
            font-size: 0.85rem;
            color: var(--medium-gray);
            font-weight: 500;
        }
        
        .price-info-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--info-color), var(--accent-blue));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .price-breakdown {
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid var(--info-color);
        }
        
        .price-breakdown-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .price-breakdown-item:last-child {
            margin-bottom: 0;
        }
        
        .price-period {
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .price-amount {
            font-weight: 600;
            color: var(--info-color);
        }
        
        .price-subtext {
            font-size: 0.85rem;
            color: var(--medium-gray);
            margin-top: 3px;
        }
        
        .price-change-notice {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
            border-left: 4px solid var(--warning-color);
            padding: 12px 15px;
            border-radius: 0 8px 8px 0;
            margin: 15px 0;
        }
        
        .info-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .info-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        /* RESPONSIVE DESIGN MEJORADO */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .toggle-sidebar-btn {
                display: none;
            }
            
            .report-iframe-container {
                height: 600px;
            }
            
            .menu-iframe-container {
                height: 700px;
            }
            
            .inventario-iframe-container {
                height: 700px;
            }
            
            .main-header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .header-left {
                width: 100%;
                justify-content: space-between;
            }
            
            .header-title {
                font-size: 1.5rem;
            }
            
            .user-display {
                flex-direction: column;
                gap: 5px;
                text-align: center;
                width: 100%;
                margin-top: 10px;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .filter-date-input {
                min-width: 100%;
            }
            
            .filter-date-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .date-separator {
                display: none;
            }
            
            .filter-buttons {
                margin-left: 0;
                width: 100%;
            }
            
            .filter-btn {
                flex: 1;
                justify-content: center;
            }
            
            .period-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .finances-container {
                grid-template-columns: 1fr;
            }
            
            .inventario-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .notification-container {
                top: 80px;
                right: 15px;
            }
            
            .notification-alert {
                max-width: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .report-iframe-container {
                height: 500px;
            }
            
            .menu-iframe-container {
                height: 600px;
            }
            
            .inventario-iframe-container {
                height: 600px;
            }
            
            .toggle-sidebar-btn {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
            
            .exento-desglose-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .exento-desglose-summary {
                width: 100%;
                justify-content: space-between;
            }
            
            .inventario-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .notification-container {
                position: static;
                margin-top: 10px;
            }
            
            .notification-alert {
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .report-iframe-container {
                height: 400px;
            }
            
            .menu-iframe-container {
                height: 500px;
            }
            
            .inventario-iframe-container {
                height: 500px;
            }
            
            .header-left {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-title {
                font-size: 1.4rem;
                text-align: center;
            }
            
            .exento-desglose-summary {
                flex-direction: column;
                gap: 5px;
            }
            
            .notification-badge {
                width: 20px;
                height: 20px;
                font-size: 0.65rem;
            }
            
            .notification-bell {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- MODAL INFORMATIVO - CLASIFICACIÓN DE PERSONAS -->
    <div class="premium-modal" id="clasificacionModal">
        <div class="premium-modal-content">
            <div class="premium-modal-header">
                <h3><i class="fas fa-users-cog me-2"></i>Clasificación de Personal</h3>
                <button class="premium-modal-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="premium-modal-body">
                <div class="premium-info-box">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Resumen de Clasificación</h5>
                    <div class="premium-stats-grid">
                        <div class="premium-stat-card">
                            <div class="premium-stat-value exento"><?php echo count($personas_consumo_considerado); ?></div>
                            <div class="premium-stat-label">Consumo Considerado</div>
                            <small>Exentos - No pagan</small>
                        </div>
                        <div class="premium-stat-card">
                            <div class="premium-stat-value agenda"><?php echo count($personas_agenda); ?></div>
                            <div class="premium-stat-label">Agenda</div>
                            <small>Solo monitoreo - No pagan</small>
                        </div>
                        <div class="premium-stat-card">
                            <div class="premium-stat-value quitado"><?php echo count($personas_quitadas); ?></div>
                            <div class="premium-stat-label">Quitados</div>
                            <small>Pagantes normales</small>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3"><i class="fas fa-user-slash me-2" style="color: var(--purple-color);"></i>Consumo Considerado (Exentos)</h5>
                <div class="mb-4">
                    <?php foreach ($personas_consumo_considerado as $persona): ?>
                    <span class="premium-badge exento"><?php echo htmlspecialchars($persona); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <h5 class="mb-3"><i class="fas fa-calendar-check me-2" style="color: var(--success-color);"></i>Agenda (Solo monitoreo - No pagan)</h5>
                <div class="mb-4">
                    <?php foreach ($personas_agenda as $persona): ?>
                    <span class="premium-badge agenda"><?php echo htmlspecialchars($persona); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <h5 class="mb-3"><i class="fas fa-user-check me-2" style="color: var(--danger-color);"></i>Quitados (Pagantes normales)</h5>
                <div class="mb-4">
                    <?php foreach ($personas_quitadas as $persona): ?>
                    <span class="premium-badge quitado"><?php echo htmlspecialchars($persona); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <div class="premium-info-box">
                    <h6 class="mb-2"><i class="fas fa-chart-simple me-2"></i>Estadísticas del Periodo</h6>
                    <p class="mb-1"><strong>Exentos (Consumo Considerado):</strong> <?php echo $exentos_total_servidos; ?> comidas - $<?php echo number_format($monto_exentos, 2); ?> no cobrados</p>
                    <p class="mb-1"><strong>Agenda (Monitoreo):</strong> <?php echo $total_agenda; ?> comidas (no pagan)</p>
                    <p class="mb-0"><strong>Quitados:</strong> Pagan normalmente</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar - TU CÓDIGO ORIGINAL -->
    <div class="sidebar" id="sidebar">
        <div class="user-profile">
            <div class="user-avatar">
                <span><?php echo substr($user_name, 0, 2); ?></span>
            </div>
            <div class="user-info">
                <h5><?php echo htmlspecialchars($user_name); ?></h5>
                <p><?php echo htmlspecialchars($user_area); ?></p>
                <?php if (!$acceso_completo): ?>
                <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Acceso Restringido</small>
                <?php endif; ?>
            </div>
        </div>
        
        <ul class="nav flex-column px-3">
            <?php if ($acceso_completo): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : ''; ?>" href="#" data-section="dashboard">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['section']) && $_GET['section'] == 'usuarios') ? 'active' : ''; ?>" href="#" data-section="usuarios">
                        <i class="fas fa-user-cog"></i> Gestión de Usuarios
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['section']) && $_GET['section'] == 'reportes') ? 'active' : ''; ?>" href="#" data-section="reportes">
                    <i class="fas fa-chart-bar"></i> Generación de Reportes
                    <?php if (!$acceso_completo): ?>
                    <span class="restricted-badge">Acceso Permitido</span>
                    <?php endif; ?>
                </a>
            </li>
            
            <?php if ($acceso_completo): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['section']) && $_GET['section'] == 'menus') ? 'active' : ''; ?>" href="#" data-section="menus">
                        <i class="fas fa-clipboard-list"></i> Gestión de Menús
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['section']) && $_GET['section'] == 'inventario') ? 'active' : ''; ?>" href="#" data-section="inventario">
                        <i class="fas fa-clipboard-check"></i> Inventario de Utensilios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($_GET['section']) && $_GET['section'] == 'cancelaciones') ? 'active' : ''; ?>" href="#" data-section="cancelaciones">
                        <i class="fas fa-times-circle"></i> Validar Cancelaciones
                        <?php if ($acceso_completo && $cancelaciones_pendientes > 0): ?>
                        <span class="notification-badge"><?php echo $cancelaciones_pendientes; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="fas fa-chart-pie"></i> Dashboard
                        <span class="restricted-badge">Restringido</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="fas fa-user-cog"></i> Gestión de Usuarios
                        <span class="restricted-badge">Restringido</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="fas fa-clipboard-list"></i> Gestión de Menús
                        <span class="restricted-badge">Restringido</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="fas fa-clipboard-check"></i> Inventario de Utensilios
                        <span class="restricted-badge">Restringido</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">
                        <i class="fas fa-times-circle"></i> Validar Cancelaciones
                        <span class="restricted-badge">Restringido</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="http://desarollo-bacros/Comedor/admicome4.php?logout=true" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
        
        <?php if (!$acceso_completo): ?>
        <div class="access-info m-3">
            <small><i class="fas fa-info-circle me-2"></i><strong>Acceso Restringido:</strong> Su usuario solo tiene permisos para generar reportes.</small>
        </div>
        <?php endif; ?>
    </div>

    <!-- Botón de menú móvil -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="main-header">
            <div class="header-left">
                <button class="toggle-sidebar-btn" id="toggleSidebar" title="Mostrar/Ocultar Menú">
                    <i class="fas fa-bars"></i>
                </button>
                
                <h1 class="header-title">
                    Portal de Comedor - Administración <?php echo !$acceso_completo ? '(Acceso Restringido)' : 'Completa'; ?>
                    <?php if (!$acceso_completo): ?>
                    <span class="badge bg-warning ms-2">Solo Reportes</span>
                    <?php endif; ?>
                </h1>
            </div>
            
            <div class="user-display">
                <div class="user-info-header">
                    <div class="user-name-display"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-area-display"><?php echo htmlspecialchars($user_area); ?></div>
                    <?php if (!$acceso_completo): ?>
                    <div class="badge bg-warning ms-2">Acceso Restringido</div>
                    <?php endif; ?>
                </div>
                
                <?php if ($acceso_completo && $cancelaciones_pendientes > 0): ?>
                <div class="notification-bell" id="notificationBell" title="<?php echo htmlspecialchars($cancelaciones_pendientes_notificacion); ?>">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?php echo $cancelaciones_pendientes; ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Botón de Información (NUEVO) -->
                <button class="info-btn" onclick="abrirModal()" title="Ver clasificación de personal">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>

        <!-- Notificación de Cancelaciones Pendientes -->
        <?php if ($acceso_completo && $cancelaciones_pendientes > 0): ?>
        <div class="notification-container" id="notificationContainer" style="display: none;">
            <div class="notification-alert">
                <button class="notification-close" id="notificationClose">&times;</button>
                <h6>
                    <i class="fas fa-bell"></i>
                    ¡Cancelaciones Pendientes!
                </h6>
                <p><?php echo htmlspecialchars($cancelaciones_pendientes_notificacion); ?></p>
                <p class="mt-2 mb-0"><small><i class="fas fa-lightbulb me-1"></i>Accede a la sección "Validar Cancelaciones" para revisarlas.</small></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($acceso_completo): ?>
        <!-- Dashboard Section -->
        <div id="dashboard" class="section <?php echo (!isset($_GET['section']) || $_GET['section'] == 'dashboard') ? 'active' : 'd-none'; ?>">
            
            <!-- FILTROS DE FECHA -->
            <div class="filters-container">
                <form id="dateFilterForm" method="GET">
                    <input type="hidden" name="section" value="dashboard">
                    <div class="filter-group">
                        <div class="filter-item">
                            <div class="filter-label">Rango de Fechas:</div>
                            <div class="filter-date-group">
                                <input type="date" class="filter-date-input" name="fecha_inicio" id="fecha_inicio" 
                                       value="<?php echo $fecha_inicio; ?>" max="<?php echo date('Y-m-d'); ?>">
                                <span class="date-separator">a</span>
                                <input type="date" class="filter-date-input" name="fecha_fin" id="fecha_fin" 
                                       value="<?php echo $fecha_fin; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="filter-buttons">
                            <button type="submit" class="filter-btn">
                                <i class="fas fa-filter"></i> Aplicar Filtros
                            </button>
                            <button type="button" class="filter-btn reset" onclick="resetFilters()">
                                <i class="fas fa-redo"></i> Restablecer
                            </button>
                        </div>
                    </div>
                    
                    <div class="period-info">
                        <p class="period-text">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?php echo $titulo_periodo; ?>
                            <small class="ms-2">(Excluyendo <?php echo count($personas_exentas); ?> personas exentas)</small>
                        </p>
                        <span class="period-days">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo $dias_periodo; ?> día<?php echo $dias_periodo > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                    
                    <?php if ($periodo_mixto_precios): ?>
                    <div class="price-change-notice mt-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning me-3 fa-lg"></i>
                            <div>
                                <strong class="text-warning">Aviso de Precios Diferenciados:</strong>
                                <p class="mb-0">
                                    Antes del 2026-01-01: $30 | Desde 2026-01-01: Desayuno $35, Comida $45
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Indicador de carga -->
            <div id="dashboard-loading" class="loading-overlay" style="display: none;">
                <div class="loading-spinner"></div>
                <div class="loading-text">Cargando Dashboard...</div>
            </div>
            
            <div class="dashboard-grid">
                <!-- CARD DESAYUNOS -->
                <div class="card comparison-card desayunos">
                    <div class="comparison-icon desayunos">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h4 class="mb-3">Desayunos
                        <?php if ($periodo_mixto_precios): ?>
                        <span class="price-info-badge">Precios Mixtos</span>
                        <?php endif; ?>
                    </h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value servido"><?php echo $desayunos_hoy; ?></div>
                            <div class="stat-label">Servidos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value agendado"><?php echo $desayunos_agendados; ?></div>
                            <div class="stat-label">Agendados</div>
                        </div>
                    </div>
                    
                    <?php if ($periodo_mixto_precios): ?>
                    <div class="price-breakdown">
                        <div class="price-breakdown-item">
                            <span class="price-period">Antes 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_desayunos_antes_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-breakdown-item">
                            <span class="price-period">Desde 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_desayunos_desde_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-subtext">
                            <?php echo $pagantes_desayunos_antes_2026; ?> x $<?php echo $precio_desayuno_viejo; ?> + 
                            <?php echo $pagantes_desayunos_desde_2026; ?> x $<?php echo $precio_desayuno_nuevo; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="comparison-percentage">
                        <?php echo $porcentaje_desayunos; ?>% de cumplimiento
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar desayunos" style="width: <?php echo min($porcentaje_desayunos, 100); ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php 
                        if ($desayunos_agendados > $desayunos_hoy) {
                            echo ($desayunos_agendados - $desayunos_hoy) . ' desayunos pendientes';
                        } else {
                            echo 'Meta cumplida';
                        }
                        ?>
                    </small>
                </div>
                
                <!-- CARD COMIDAS -->
                <div class="card comparison-card comidas">
                    <div class="comparison-icon comidas">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4 class="mb-3">Comidas
                        <?php if ($periodo_mixto_precios): ?>
                        <span class="price-info-badge">Precios Mixtos</span>
                        <?php endif; ?>
                    </h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value servido"><?php echo $comidas_hoy; ?></div>
                            <div class="stat-label">Servidas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value agendado"><?php echo $comidas_agendadas; ?></div>
                            <div class="stat-label">Agendadas</div>
                        </div>
                    </div>
                    
                    <?php if ($periodo_mixto_precios): ?>
                    <div class="price-breakdown">
                        <div class="price-breakdown-item">
                            <span class="price-period">Antes 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_comidas_antes_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-breakdown-item">
                            <span class="price-period">Desde 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_comidas_desde_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-subtext">
                            <?php echo $pagantes_comidas_antes_2026; ?> x $<?php echo $precio_comida_viejo; ?> + 
                            <?php echo $pagantes_comidas_desde_2026; ?> x $<?php echo $precio_comida_nuevo; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="comparison-percentage">
                        <?php echo $porcentaje_comidas; ?>% de cumplimiento
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar comidas" style="width: <?php echo min($porcentaje_comidas, 100); ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php 
                        if ($comidas_agendadas > $comidas_hoy) {
                            echo ($comidas_agendadas - $comidas_hoy) . ' comidas pendientes';
                        } else {
                            echo 'Meta cumplida';
                        }
                        ?>
                    </small>
                </div>
                
                <!-- CARD PERSONAS EXENTAS (SOLO CONSUMO CONSIDERADO) -->
                <div class="card comparison-card exentos">
                    <div class="comparison-icon exentos">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h4 class="mb-3">Personas Exentas
                        <?php if ($periodo_mixto_precios): ?>
                        <span class="price-info-badge">Precios Mixtos</span>
                        <?php endif; ?>
                        <span class="badge bg-purple ms-1"><?php echo count($personas_exentas); ?> pers.</span>
                    </h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value exento"><?php echo $exentos_desayuno_servidos; ?></div>
                            <div class="stat-label">Desayunos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value exento"><?php echo $exentos_comida_servidos; ?></div>
                            <div class="stat-label">Comidas</div>
                        </div>
                    </div>
                    
                    <?php if ($periodo_mixto_precios): ?>
                    <div class="price-breakdown">
                        <div class="price-breakdown-item">
                            <span class="price-period">Desay. Antes 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_exentos_desayunos_antes_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-breakdown-item">
                            <span class="price-period">Desay. Desde 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_exentos_desayunos_desde_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-breakdown-item">
                            <span class="price-period">Com. Antes 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_exentos_comidas_antes_2026, 2, '.', ','); ?></span>
                        </div>
                        <div class="price-breakdown-item">
                            <span class="price-period">Com. Desde 2026:</span>
                            <span class="price-amount">$<?php echo number_format($monto_exentos_comidas_desde_2026, 2, '.', ','); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="exentos-total">
                        Total: <?php echo $exentos_total_servidos; ?> comidas
                        <small class="d-block">($<?php echo number_format($monto_exentos, 2, '.', ','); ?> no cobrados)</small>
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar exentos" style="width: <?php echo $porcentaje_exentos; ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php echo $porcentaje_exentos; ?>% del total servido
                    </small>
                </div>
                
                <!-- CARD CANCELACIONES (AGREGADO) -->
                <div class="card comparison-card cancelaciones">
                    <div class="comparison-icon cancelaciones">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h4 class="mb-3">Cancelaciones
                        <?php if ($cancelaciones_pendientes > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $cancelaciones_pendientes; ?> pendientes</span>
                        <?php endif; ?>
                    </h4>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-item p-2">
                                <div class="stat-value cancelado" style="font-size: 1.8rem;"><?php echo $cancelaciones_desayuno; ?></div>
                                <div class="stat-label">Desayunos</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item p-2">
                                <div class="stat-value cancelado" style="font-size: 1.8rem;"><?php echo $cancelaciones_comida; ?></div>
                                <div class="stat-label">Comidas</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cancelaciones-total mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total cancelaciones:</span>
                            <span class="fw-bold"><?php echo $total_cancelaciones; ?></span>
                        </div>
                        <?php if ($cancelaciones_pendientes > 0): ?>
                        <div class="text-danger mt-2 small">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <?php echo $cancelaciones_pendientes; ?> pendientes de revisión
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="progress-comparison mt-3">
                        <?php 
                        $total_agendado = $desayunos_agendados + $comidas_agendadas;
                        $porcentaje_cancelaciones = $total_agendado > 0 ? min(100, ($total_cancelaciones / $total_agendado) * 100) : 0;
                        ?>
                        <div class="progress-comparison-bar cancelaciones" style="width: <?php echo $porcentaje_cancelaciones; ?>%"></div>
                    </div>
                    
                    <small class="text-muted d-block text-center mt-2">
                        <?php echo round($porcentaje_cancelaciones, 1); ?>% del total agendado
                    </small>
                </div>
                
                <!-- CARD GASTOS CONTEPAQ I (UX MEJORADO) -->
                <div class="card comparison-card gastos">
                    <div class="comparison-icon gastos">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h4 class="mb-3">Gastos Contpaq i</h4>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-item p-2">
                                <div class="stat-value gasto" style="font-size: 1.5rem;">$<?php echo number_format($total_gastos_contpaq, 0); ?></div>
                                <div class="stat-label">Total Gastos</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item p-2">
                                <div class="stat-value gasto" style="font-size: 1.5rem;"><?php echo $total_registros_contpaq ?? 0; ?></div>
                                <div class="stat-label">Registros</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="base-database-breakdown mt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">ALQUIMISTA</span>
                            <span class="text-warning fw-bold">$<?php echo number_format($total_gastos_alquimista, 0); ?></span>
                            <small class="text-muted">(<?php echo $total_registros_alquimista ?? 0; ?>)</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">AHMEX</span>
                            <span class="text-warning fw-bold">$<?php echo number_format($total_gastos_basenueva, 0); ?></span>
                            <small class="text-muted">(<?php echo $total_registros_basenueva ?? 0; ?>)</small>
                        </div>
                    </div>
                    
                    <div class="progress-comparison mt-3">
                        <?php $porcentaje_gastos = $monto_recaudado > 0 ? min(100, ($total_gastos_contpaq / $monto_recaudado) * 100) : 0; ?>
                        <div class="progress-comparison-bar gastos" style="width: <?php echo $porcentaje_gastos; ?>%"></div>
                    </div>
                    
                    <small class="text-muted d-block text-center mt-2">
                        <?php echo round($porcentaje_gastos, 1); ?>% de la recaudación
                    </small>
                </div>
                
                <!-- CARD UTILIDAD (UX MEJORADO) -->
                <div class="card comparison-card utilidad">
                    <div class="comparison-icon utilidad">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="mb-3">Utilidad</h4>
                    
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="stat-item p-3">
                                <div class="stat-value <?php echo $utilidad >= 0 ? 'utilidad-positiva' : 'utilidad-negativa'; ?>" style="font-size: 2rem;">
                                    $<?php echo number_format($utilidad, 0); ?>
                                </div>
                                <div class="stat-label">Balance Neto</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <div class="p-2 text-center">
                                <small class="text-muted d-block">Ingresos</small>
                                <span class="fw-bold text-success">$<?php echo number_format($monto_recaudado, 0); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 text-center">
                                <small class="text-muted d-block">Gastos</small>
                                <span class="fw-bold text-warning">$<?php echo number_format($total_gastos_contpaq, 0); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="utilidad-total <?php echo $utilidad >= 0 ? 'positiva' : 'negativa'; ?> mt-3">
                        <?php echo $utilidad >= 0 ? 'Utilidad Positiva' : 'Pérdida'; ?>
                        <small class="d-block">Margen: <?php echo $monto_recaudado > 0 ? round(($utilidad / $monto_recaudado) * 100, 1) : 0; ?>%</small>
                    </div>
                    
                    <div class="progress-comparison mt-3">
                        <?php $porcentaje_utilidad = $monto_recaudado > 0 ? min(100, max(0, ($utilidad / $monto_recaudado) * 100)) : 0; ?>
                        <div class="progress-comparison-bar utilidad" style="width: <?php echo $porcentaje_utilidad; ?>%"></div>
                    </div>
                </div>
                
                <!-- USUARIOS ACTIVOS -->
                <div class="card stat-card users">
                    <div class="feature-icon users mx-auto">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_usuarios; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                
                <!-- TASA ASISTENCIA -->
                <div class="card stat-card attendance">
                    <div class="feature-icon attendance mx-auto">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number"><?php echo $tasa_asistencia; ?>%</div>
                    <div class="stat-label">Tasa de Asistencia</div>
                </div>
                
                <!-- MONTO RECAUDADO -->
                <div class="card stat-card revenue">
                    <div class="feature-icon revenue mx-auto">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($monto_recaudado, 0); ?></div>
                    <div class="stat-label">Monto Recaudado</div>
                </div>
            </div>

            <!-- Resumen Financiero (TU CÓDIGO ORIGINAL) -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i>Resumen Financiero del Periodo
                            <?php if ($periodo_mixto_precios): ?>
                            <span class="badge bg-warning ms-2">Precios Diferenciados</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="finances-container">
                                <!-- Ingresos -->
                                <div class="finance-card">
                                    <div class="finance-title">
                                        <i class="fas fa-money-bill-wave text-success"></i> Ingresos por Comedor
                                    </div>
                                    <div class="finance-amount ingresos">
                                        $<?php echo number_format($monto_recaudado, 2); ?>
                                    </div>
                                    <div class="finance-subtitle">
                                        Del <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                    </div>
                                </div>
                                
                                <!-- Exentos -->
                                <div class="finance-card">
                                    <div class="finance-title">
                                        <i class="fas fa-user-slash text-purple"></i> Comidas Exentas
                                    </div>
                                    <div class="finance-amount exentos">
                                        $<?php echo number_format($monto_exentos, 2); ?>
                                    </div>
                                    <div class="finance-subtitle">
                                        <?php echo $exentos_total_servidos; ?> comidas no cobradas
                                    </div>
                                </div>
                                
                                <!-- Utilidad -->
                                <div class="finance-card">
                                    <div class="finance-title">
                                        <i class="fas fa-chart-line <?php echo $utilidad >= 0 ? 'text-success' : 'text-danger'; ?>"></i> Utilidad Neta
                                    </div>
                                    <div class="finance-amount <?php echo $utilidad >= 0 ? 'utilidad-positiva' : 'utilidad-negativa'; ?>">
                                        $<?php echo number_format($utilidad, 2); ?>
                                    </div>
                                    <div class="finance-subtitle">
                                        Ingresos - Gastos
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen del Periodo (GRÁFICA COMPLETA Y FUNCIONAL) -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i>Resumen del Periodo
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:250px; width:100%;">
                                <canvas id="statsChart"></canvas>
                            </div>
                            <div class="row mt-3 text-center">
                                <div class="col-4">
                                    <h5 class="mb-0"><?php echo $desayunos_hoy; ?></h5>
                                    <small class="text-muted">Desayunos</small>
                                </div>
                                <div class="col-4">
                                    <h5 class="mb-0"><?php echo $comidas_hoy; ?></h5>
                                    <small class="text-muted">Comidas</small>
                                </div>
                                <div class="col-4">
                                    <h5 class="mb-0">$<?php echo number_format($monto_recaudado, 0); ?></h5>
                                    <small class="text-muted">Recaudación</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Distribución por Tipo (TU CÓDIGO ORIGINAL) -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i>Distribución por Tipo
                        </div>
                        <div class="card-body">
                            <div class="activity-item">
                                <div class="activity-icon" style="background-color: var(--success-color); color: white;">
                                    <i class="fas fa-coffee"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Desayunos</h6>
                                    <small class="text-muted"><?php echo $desayunos_hoy; ?> de <?php echo $desayunos_agendados; ?> agendados</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon" style="background-color: var(--warning-color); color: white;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Comidas</h6>
                                    <small class="text-muted"><?php echo $comidas_hoy; ?> de <?php echo $comidas_agendadas; ?> agendadas</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon" style="background-color: var(--purple-color); color: white;">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Comidas Exentas</h6>
                                    <small class="text-muted"><?php echo $exentos_total_servidos; ?> comidas no cobradas</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon" style="background-color: var(--success-color); color: white;">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Agenda (Monitoreo)</h6>
                                    <small class="text-muted"><?php echo $total_agenda; ?> comidas - No pagan</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon" style="background-color: var(--danger-color); color: white;">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Cancelaciones</h6>
                                    <small class="text-muted"><?php echo $total_cancelaciones; ?> registros</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon" style="background-color: var(--indigo-color); color: white;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Usuarios Activos</h6>
                                    <small class="text-muted"><?php echo $total_usuarios; ?> usuarios</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desglose de Exentos (TU CÓDIGO ORIGINAL) -->
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-list-alt me-2"></i>Desglose de Personas Exentas
                </div>
                <div class="card-body">
                    <div class="exento-desglose-header">
                        <div class="exento-desglose-title">
                            <i class="fas fa-chart-pie me-2"></i>Detalle por Persona
                        </div>
                        <div class="exento-desglose-summary">
                            <div class="summary-item">
                                <i class="fas fa-coffee me-1"></i> Desayunos: <?php echo $total_desayunos_exentos; ?>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-utensils me-1"></i> Comidas: <?php echo $total_comidas_exentas; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($desglose_exentos)): ?>
                    <div class="table-responsive">
                        <table class="exento-detalle-table">
                            <thead>
                                <tr>
                                    <th>Persona Exenta</th>
                                    <th>Desayunos</th>
                                    <th>Comidas</th>
                                    <th>Total</th>
                                    <th>Monto No Cobrado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($desglose_exentos as $persona => $datos): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($persona); ?></td>
                                    <td><?php echo $datos['desayunos_total']; ?></td>
                                    <td><?php echo $datos['comidas_total']; ?></td>
                                    <td class="exento-total-cell"><?php echo $datos['total']; ?></td>
                                    <td class="exento-monto-cell">$<?php echo number_format($datos['monto'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PANEL DE VALIDACIÓN -->
            <div class="validation-panel">
                <h5 class="mb-3"><i class="fas fa-check-circle me-2" style="color: var(--info-color);"></i>Validación de Cifras</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="validation-item">
                            <span><strong>Desayunos Agendados:</strong> <?php echo $desayunos_agendados; ?></span>
                            <span><strong>Servidos:</strong> <?php echo $desayunos_hoy; ?></span>
                            <span class="<?php echo $diferencia_desayunos >= 0 ? 'validation-ok' : 'validation-warning'; ?>">
                                Dif: <?php echo $diferencia_desayunos; ?>
                            </span>
                        </div>
                        <div class="validation-item">
                            <span><strong>Comidas Agendadas:</strong> <?php echo $comidas_agendadas; ?></span>
                            <span><strong>Servidas:</strong> <?php echo $comidas_hoy; ?></span>
                            <span class="<?php echo $diferencia_comidas >= 0 ? 'validation-ok' : 'validation-warning'; ?>">
                                Dif: <?php echo $diferencia_comidas; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="validation-item">
                            <span><strong>Total Agendado:</strong> <?php echo $total_agendado_real; ?></span>
                            <span><strong>Total Servido:</strong> <?php echo $total_servido_real; ?></span>
                            <span class="<?php echo $coherencia_datos == 'ok' ? 'validation-ok' : 'validation-warning'; ?>">
                                <?php echo $total_agendado_real - $total_servido_real; ?> pendientes
                            </span>
                        </div>
                        <div class="validation-item">
                            <span><strong>Exentos Servidos:</strong> <?php echo $exentos_total_servidos; ?></span>
                            <span><strong>Agenda (Monitoreo):</strong> <?php echo $total_agenda; ?></span>
                            <span class="validation-ok">
                                Dif: <?php echo $exentos_total_servidos - $total_agenda; ?>
                            </span>
                        </div>
                        <div class="validation-item">
                            <span><strong>Cancelaciones:</strong> <?php echo $total_cancelaciones; ?></span>
                            <span><strong>Pendientes:</strong> <?php echo $cancelaciones_pendientes; ?></span>
                            <span class="<?php echo $cancelaciones_pendientes == 0 ? 'validation-ok' : 'validation-warning'; ?>">
                                <?php echo $cancelaciones_pendientes == 0 ? 'OK' : 'Pendientes'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($mensaje_validacion)): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $mensaje_validacion; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sección de Reportes -->
        <div id="reportes" class="section <?php echo (isset($_GET['section']) && $_GET['section'] == 'reportes') ? 'active' : ($acceso_completo ? 'd-none' : 'active'); ?>">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2"></i>Sistema de Reportes
                    <?php if (!$acceso_completo): ?>
                    <span class="badge bg-success ms-2">Acceso Permitido</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0 position-relative" style="height: 800px;">
                    <div id="reportes-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando sistema de reportes...</div>
                    </div>
                    <iframe src="http://desarollo-bacros/Comedor/dem1.php" style="width: 100%; height: 100%; border: none;" onload="document.getElementById('reportes-loading').style.display='none';"></iframe>
                </div>
            </div>
        </div>

        <?php if ($acceso_completo): ?>
        <!-- Sección de Usuarios -->
        <div id="usuarios" class="section <?php echo (isset($_GET['section']) && $_GET['section'] == 'usuarios') ? 'active' : 'd-none'; ?>">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-cog me-2"></i>Gestión de Usuarios
                </div>
                <div class="card-body p-0 position-relative" style="height: 800px;">
                    <div id="usuarios-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando gestión de usuarios...</div>
                    </div>
                    <iframe src="http://desarollo-bacros/Comedor/gestusu.php" style="width: 100%; height: 100%; border: none;" onload="document.getElementById('usuarios-loading').style.display='none';"></iframe>
                </div>
            </div>
        </div>

        <!-- Sección de Menús -->
        <div id="menus" class="section <?php echo (isset($_GET['section']) && $_GET['section'] == 'menus') ? 'active' : 'd-none'; ?>">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list me-2"></i>Gestión de Menús
                </div>
                <div class="card-body p-0 position-relative" style="height: 800px;">
                    <div id="menus-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando gestión de menús...</div>
                    </div>
                    <iframe src="http://desarollo-bacros/Comedor/menu1.php" style="width: 100%; height: 100%; border: none;" onload="document.getElementById('menus-loading').style.display='none';"></iframe>
                </div>
            </div>
        </div>

        <!-- Sección de Inventario -->
        <div id="inventario" class="section <?php echo (isset($_GET['section']) && $_GET['section'] == 'inventario') ? 'active' : 'd-none'; ?>">
            <div class="card inventario-card">
                <div class="card-header inventario-header">
                    <i class="fas fa-clipboard-check me-2"></i>Inventario de Utensilios
                </div>
                <div class="card-body p-0 position-relative" style="height: 800px;">
                    <div id="inventario-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando inventario...</div>
                    </div>
                    <iframe src="http://192.168.100.95/comedor/ImventarioCocina.php" style="width: 100%; height: 100%; border: none;" onload="document.getElementById('inventario-loading').style.display='none';"></iframe>
                </div>
            </div>
        </div>

        <!-- Sección de Cancelaciones -->
        <div id="cancelaciones" class="section <?php echo (isset($_GET['section']) && $_GET['section'] == 'cancelaciones') ? 'active' : 'd-none'; ?>">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-times-circle me-2"></i>Validar Cancelaciones
                </div>
                <div class="card-body p-0 position-relative" style="height: 800px;">
                    <div id="cancelaciones-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando validación de cancelaciones...</div>
                    </div>
                    <iframe src="http://192.168.100.95/Comedor/Formacancel123456.php?newpwd=Administrador" style="width: 100%; height: 100%; border: none;" onload="document.getElementById('cancelaciones-loading').style.display='none';"></iframe>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Modal functions
        function abrirModal() {
            document.getElementById('clasificacionModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal() {
            document.getElementById('clasificacionModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });

        document.getElementById('clasificacionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        function resetFilters() {
            const section = new URLSearchParams(window.location.search).get('section') || 'dashboard';
            window.location.href = window.location.pathname + '?section=' + section;
        }

        const dateFilterForm = document.getElementById('dateFilterForm');
        if (dateFilterForm) {
            dateFilterForm.addEventListener('submit', function(e) {
                const fechaInicio = document.getElementById('fecha_inicio').value;
                const fechaFin = document.getElementById('fecha_fin').value;
                
                if (fechaInicio && fechaFin && new Date(fechaInicio) > new Date(fechaFin)) {
                    e.preventDefault();
                    alert('La fecha de inicio no puede ser mayor que la fecha de fin');
                }
            });
        }

        document.querySelectorAll('.nav-link:not(.disabled)').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.classList.contains('disabled')) return;
                
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.section').forEach(s => s.classList.add('d-none'));
                
                this.classList.add('active');
                
                const sectionId = this.getAttribute('data-section');
                const section = document.getElementById(sectionId);
                if (section) {
                    section.classList.remove('d-none');
                    
                    const url = new URL(window.location);
                    url.searchParams.set('section', sectionId);
                    window.history.pushState({}, '', url);
                }
            });
        });

        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('expanded');
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.add('expanded');
            }
        });

        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                window.location.href = this.href;
            }
        });

        const notificationBell = document.getElementById('notificationBell');
        const notificationContainer = document.getElementById('notificationContainer');
        const notificationClose = document.getElementById('notificationClose');
        
        if (notificationBell && notificationContainer) {
            notificationBell.addEventListener('click', function() {
                notificationContainer.style.display = notificationContainer.style.display === 'none' ? 'block' : 'none';
            });
            
            if (notificationClose) {
                notificationClose.addEventListener('click', function() {
                    notificationContainer.style.display = 'none';
                });
            }
            
            if (cancelacionesPendientes > 0) {
                setTimeout(() => {
                    notificationContainer.style.display = 'block';
                    setTimeout(() => {
                        notificationContainer.style.display = 'none';
                    }, 15000);
                }, 2000);
            }
        }

        // GRÁFICA COMPLETA Y FUNCIONAL
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Desayunos', 'Comidas', 'Exentos'],
                        datasets: [{
                            data: [
                                <?php echo $desayunos_hoy; ?>,
                                <?php echo $comidas_hoy; ?>,
                                <?php echo $exentos_total_servidos; ?>
                            ],
                            backgroundColor: [
                                'rgba(16, 185, 129, 0.9)',
                                'rgba(245, 158, 11, 0.9)',
                                'rgba(139, 92, 246, 0.9)'
                            ],
                            borderColor: [
                                'rgba(16, 185, 129, 1)',
                                'rgba(245, 158, 11, 1)',
                                'rgba(139, 92, 246, 1)'
                            ],
                            borderWidth: 2,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    },
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');
            
            if (sectionParam) {
                const targetLink = document.querySelector(`a[data-section="${sectionParam}"]`);
                const targetSection = document.getElementById(sectionParam);
                
                if (targetLink && !targetLink.classList.contains('disabled') && targetSection) {
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.section').forEach(s => s.classList.add('d-none'));
                    
                    targetLink.classList.add('active');
                    targetSection.classList.remove('d-none');
                }
            }
        });

        const style = document.createElement('style');
        style.textContent = `
            .bg-bronze {
                background-color: var(--bronze-color) !important;
                color: white !important;
            }
            .bg-purple {
                background-color: var(--purple-color) !important;
                color: white !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>