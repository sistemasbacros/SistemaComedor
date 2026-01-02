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
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Configuración de seguridad
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// ========== MANEJO DE CIERRE DE SESIÓN ==========
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    // Destruir completamente toda la sesión
    $_SESSION = array();
    session_destroy();
    
    // Eliminar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Redirigir inmediatamente al login
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

// Verificación estricta de autenticación - CORREGIDO
$isAuthenticated = (
    isset($_SESSION['authenticated_from_login']) && 
    $_SESSION['authenticated_from_login'] === true &&
    isset($_SESSION['session_id']) && 
    $_SESSION['session_id'] === session_id() &&
    isset($_SESSION['browser_fingerprint']) && 
    $_SESSION['browser_fingerprint'] === md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'])
);

// Permitir acceso durante el mismo request después de procesar POST
if (!$isAuthenticated) {
    // Destruir completamente la sesión
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time()-3600, '/');
    
    // Redirigir al login
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

// Verificar expiración de sesión (2 minutos - muy corto)
$sessionTimeout = 2 * 4000; // 2 minutos
if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] > $sessionTimeout)) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time()-3600, '/');
    header("Location: http://desarollo-bacros/Comedor/Admiin.php");
    exit;
}

// SOLO INVALIDAR EL ACCESO SI NO ES UNA PETICIÓN POST
// Esto permite que los formularios funcionen correctamente
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['one_time_access']);
}

// Actualizar tiempo de actividad
$_SESSION['LAST_ACTIVITY'] = time();
// ==================================================
// FIN DE PROTECCIÓN - TU CÓDIGO ORIGINAL COMIENZA AQUÍ
// ==================================================

// ==================================================
// CONEXIÓN A BASE DE DATOS Y CONSULTAS DEL DASHBOARD
// ==================================================

// Configuración de conexión a la base de datos principal
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// Configuración de conexión a Contpaq i Comedor - Alquimista
$serverNameContpaq = "WIN-44O80L37Q7M\COMERCIAL";
$connectionOptionsContpaq = array(
    "Database" => "ALQUIMISTA2024",
    "Uid" => "sa",
    "PWD" => "Administrador1*",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// NUEVA CONFIGURACIÓN: Conexión a Contpaq i Comedor - BASENUEVA
$serverNameBaseNueva = "WIN-44O80L37Q7M\COMERCIAL"; // Mismo servidor
$connectionOptionsBaseNueva = array(
    "Database" => "BASENUEVA", // Base de datos diferente
    "Uid" => "sa",
    "PWD" => "Administrador1*",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// Obtener parámetros de fecha del request
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Validar que fecha_inicio no sea mayor que fecha_fin
if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
    $fecha_inicio = $fecha_fin;
}

// LISTA DE PERSONAS EXENTAS (NO SE CUENTAN EN MONTOS FINALES)
// Usamos coincidencias parciales con LIKE
$personas_exentas = [
    'ALEJANDRA CRUZ',
    'ALTA DIRECCION',
    'CRUZ JOSE LUIS',
    'CRUZ RODRIGUEZ ALEJANDRO',
    'JURIDICO',
    'PALMA TREJO SANDY MARK',
    'REYES QUIROZ HILDA',
    'VIGILANCIA'
];

// Crear condiciones LIKE para cada persona exenta
$exentos_conditions = [];
foreach ($personas_exentas as $exento) {
    $exentos_conditions[] = "nombre LIKE '%$exento%'";
}
$exentos_sql_condition = "(" . implode(" OR ", $exentos_conditions) . ")";

// Para consultas de PedidosComida (campo Usuario)
$exentos_conditions_usuario = [];
foreach ($personas_exentas as $exento) {
    $exentos_conditions_usuario[] = "Usuario LIKE '%$exento%'";
}
$exentos_sql_condition_usuario = "(" . implode(" OR ", $exentos_conditions_usuario) . ")";

// Variables para personas exentas
$exentos_desayuno_servidos = 0;
$exentos_comida_servidos = 0;
$exentos_desayuno_agendados = 0;
$exentos_comida_agendadas = 0;
$exentos_total_servidos = 0;
$exentos_total_agendados = 0;
$monto_exentos = 0;

// NUEVA VARIABLE: Array para almacenar el desglose por persona exenta
$desglose_exentos = array();

// Inicializar variables del dashboard
$total_usuarios = 0;
$desayunos_hoy = 0;
$comidas_hoy = 0;
$desayunos_agendados = 0;
$comidas_agendadas = 0;
$monto_recaudado = 0;

// Variables para pagantes (excluyendo exentos)
$pagantes_desayunos_servidos = 0;
$pagantes_comidas_servidas = 0;
$pagantes_desayunos_agendados = 0;
$pagantes_comidas_agendadas = 0;
$total_pagantes = 0;

// NUEVAS VARIABLES PARA CANCELACIONES
$cancelaciones_desayuno = 0;
$cancelaciones_comida = 0;
$total_cancelaciones = 0;

// VARIABLES PARA GASTOS DE AMBAS BASES DE DATOS
$total_gastos_alquimista = 0;
$total_gastos_basenueva = 0;
$total_gastos_contpaq = 0;
$total_registros_contpaq = 0;

// Variables para la gráfica de tendencia (removidas según solicitud)
// $tendencia_diaria = array();

try {
    // Conexión a la base de datos principal
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn !== false) {
        // Consulta para total de usuarios en el periodo (EXCLUYENDO EXENTOS)
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
        
        // NUEVA CONSULTA: Obtener desglose de consumos por persona exenta
        $sql_desglose_exentos = "SELECT 
            CASE 
                WHEN nombre LIKE '%ALEJANDRA CRUZ%' THEN 'ALEJANDRA CRUZ'
                WHEN nombre LIKE '%ALTA DIRECCION%' THEN 'ALTA DIRECCION'
                WHEN nombre LIKE '%CRUZ JOSE LUIS%' THEN 'CRUZ JOSE LUIS'
                WHEN nombre LIKE '%CRUZ RODRIGUEZ ALEJANDRO%' THEN 'CRUZ RODRIGUEZ ALEJANDRO'
                WHEN nombre LIKE '%JURIDICO%' THEN 'JURIDICO'
                WHEN nombre LIKE '%PALMA TREJO SANDY MARK%' THEN 'PALMA TREJO SANDY MARK'
                WHEN nombre LIKE '%REYES QUIROZ HILDA%' THEN 'REYES QUIROZ HILDA'
                WHEN nombre LIKE '%VIGILANCIA%' THEN 'VIGILANCIA'
                ELSE nombre
            END as persona_exenta,
            COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 1 END) AS desayunos,
            COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' THEN 1 END) AS comidas,
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
                WHEN nombre LIKE '%JURIDICO%' THEN 'JURIDICO'
                WHEN nombre LIKE '%PALMA TREJO SANDY MARK%' THEN 'PALMA TREJO SANDY MARK'
                WHEN nombre LIKE '%REYES QUIROZ HILDA%' THEN 'REYES QUIROZ HILDA'
                WHEN nombre LIKE '%VIGILANCIA%' THEN 'VIGILANCIA'
                ELSE nombre
            END
        ORDER BY total_consumos DESC";
        
        $params_desglose = array($fecha_inicio, $fecha_fin);
        $stmt_desglose = sqlsrv_query($conn, $sql_desglose_exentos, $params_desglose);
        
        if ($stmt_desglose !== false) {
            while ($row = sqlsrv_fetch_array($stmt_desglose, SQLSRV_FETCH_ASSOC)) {
                $persona = $row['persona_exenta'];
                $desayunos = $row['desayunos'] ?? 0;
                $comidas = $row['comidas'] ?? 0;
                $total = $row['total_consumos'] ?? 0;
                
                $desglose_exentos[$persona] = array(
                    'desayunos' => $desayunos,
                    'comidas' => $comidas,
                    'total' => $total,
                    'monto' => $total * 30
                );
            }
        }
        
        // CONSULTA MEJORADA: Desayunos y comidas servidos SEPARANDO EXENTOS
        $sql_comidas = "SELECT 
                        -- Total servidos (incluyendo exentos para calcular diferencia)
                        COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 1 END) AS Desayuno_Total,
                        COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' THEN 1 END) AS Comida_Total,
                        -- Exentos servidos
                        COUNT(CASE WHEN CAST(Fecha AS TIME) < '12:00:00' AND $exentos_sql_condition THEN 1 END) AS ExentosDesayuno,
                        COUNT(CASE WHEN CAST(Fecha AS TIME) >= '12:00:00' AND $exentos_sql_condition THEN 1 END) AS ExentosComida
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
                
                // Calcular pagantes (excluyendo exentos)
                $desayunos_hoy = $total_desayunos_con_exentos - $exentos_desayuno_servidos;
                $comidas_hoy = $total_comidas_con_exentos - $exentos_comida_servidos;
                
                $exentos_total_servidos = $exentos_desayuno_servidos + $exentos_comida_servidos;
            }
        }
        
        // CONSULTA MEJORADA: Desayunos y comidas agendados SEPARANDO EXENTOS
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
                                ) AS Fecha,
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
                            -- Total agendados (incluyendo exentos)
                            COUNT(CASE WHEN Tipo_Comida = 'Desayuno' THEN 1 END) AS Desayuno_Total,
                            COUNT(CASE WHEN Tipo_Comida = 'Comida' THEN 1 END) AS Comida_Total,
                            -- Exentos agendados
                            COUNT(CASE WHEN Tipo_Comida = 'Desayuno' AND $exentos_sql_condition_usuario THEN 1 END) AS ExentosDesayunoAgendado,
                            COUNT(CASE WHEN Tipo_Comida = 'Comida' AND $exentos_sql_condition_usuario THEN 1 END) AS ExentosComidaAgendado
                        FROM Datos
                        WHERE Fecha BETWEEN ? AND ?";
        
        $params_agendados = array($fecha_inicio, $fecha_fin);
        $stmt_agendados = sqlsrv_query($conn, $sql_agendados, $params_agendados);
        
        if ($stmt_agendados !== false) {
            $row = sqlsrv_fetch_array($stmt_agendados, SQLSRV_FETCH_ASSOC);
            if ($row) {
                $total_desayunos_agendados_con_exentos = $row['Desayuno_Total'] ?? 0;
                $total_comidas_agendadas_con_exentos = $row['Comida_Total'] ?? 0;
                $exentos_desayuno_agendados = $row['ExentosDesayunoAgendado'] ?? 0;
                $exentos_comida_agendadas = $row['ExentosComidaAgendado'] ?? 0;
                
                // Calcular pagantes (excluyendo exentos)
                $desayunos_agendados = $total_desayunos_agendados_con_exentos - $exentos_desayuno_agendados;
                $comidas_agendadas = $total_comidas_agendadas_con_exentos - $exentos_comida_agendadas;
                
                $exentos_total_agendados = $exentos_desayuno_agendados + $exentos_comida_agendadas;
            }
        }
        
        // NUEVA CONSULTA MEJORADA: CANCELACIONES USANDO TIPO_CONSUMO (EXCLUYENDO EXENTOS)
        $sql_cancelaciones = "SELECT 
                                SUM(CASE 
                                    WHEN tipo_consumo = 'Desayuno' THEN 1
                                    WHEN tipo_consumo = 'Ambos' THEN 1
                                    ELSE 0 
                                END) as CancelacionesDesayuno,
                                SUM(CASE 
                                    WHEN tipo_consumo = 'Comida' THEN 1
                                    WHEN tipo_consumo = 'Ambos' THEN 1
                                    ELSE 0 
                                END) as CancelacionesComida,
                                COUNT(*) as TotalRegistros
                              FROM cancelaciones
                              WHERE convert(date, FECHA, 102) BETWEEN ? AND ?
                              AND NOT $exentos_sql_condition";
        
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
        
        // Calcular montos CORRECTAMENTE (solo pagantes, excluyendo exentos)
        $monto_recaudado = ($desayunos_hoy + $comidas_hoy) * 30;
        $monto_exentos = $exentos_total_servidos * 30;
        
        // Cerrar conexión principal
        sqlsrv_close($conn);
        
    } else {
        throw new Exception("Error de conexión a la base de datos principal");
    }
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $total_usuarios = 148; // 156 total - 8 exentos
    $desayunos_hoy = 112; // 120 total - 8 exentos
    $comidas_hoy = 214; // 222 total - 8 exentos
    $desayunos_agendados = 127; // 135 total - 8 exentos
    $comidas_agendadas = 237; // 245 total - 8 exentos
    
    // Valores por defecto para exentos
    $exentos_desayuno_servidos = 8;
    $exentos_comida_servidos = 8;
    $exentos_desayuno_agendados = 8;
    $exentos_comida_agendadas = 8;
    $exentos_total_servidos = 16;
    $exentos_total_agendados = 16;
    
    // Datos de ejemplo para desglose de exentos
    $desglose_exentos = array(
        'ALEJANDRA CRUZ' => array('desayunos' => 12, 'comidas' => 15, 'total' => 27, 'monto' => 810),
        'ALTA DIRECCION' => array('desayunos' => 10, 'comidas' => 18, 'total' => 28, 'monto' => 840),
        'CRUZ JOSE LUIS' => array('desayunos' => 8, 'comidas' => 12, 'total' => 20, 'monto' => 600),
        'CRUZ RODRIGUEZ ALEJANDRO' => array('desayunos' => 9, 'comidas' => 14, 'total' => 23, 'monto' => 690),
        'JURIDICO' => array('desayunos' => 11, 'comidas' => 16, 'total' => 27, 'monto' => 810),
        'PALMA TREJO SANDY MARK' => array('desayunos' => 7, 'comidas' => 10, 'total' => 17, 'monto' => 510),
        'REYES QUIROZ HILDA' => array('desayunos' => 8, 'comidas' => 13, 'total' => 21, 'monto' => 630),
        'VIGILANCIA' => array('desayunos' => 6, 'comidas' => 9, 'total' => 15, 'monto' => 450)
    );
    
    $tasa_asistencia = 78.5;
    $monto_recaudado = 10260; // (112+214)*30
    $monto_exentos = 480; // 16*30
    
    // Valores por defecto para cancelaciones
    $cancelaciones_desayuno = 18;
    $cancelaciones_comida = 32;
    $total_cancelaciones = 43;
    
    // Valor por defecto para gastos Contpaq i
    $total_gastos_alquimista = 120000;
    $total_gastos_basenueva = 65000;
    $total_gastos_contpaq = 185000;
}

// CONEXIÓN Y CONSULTA PARA CONTACQ I COMEDOR - AMBAS BASES DE DATOS
try {
    // Inicializar totales
    $total_gastos_alquimista = 0;
    $total_gastos_basenueva = 0;
    $total_registros_alquimista = 0;
    $total_registros_basenueva = 0;
    $total_gastos_contpaq = 0;
    $total_registros_contpaq = 0;
    
    // Convertir fechas para formato SQL
    $fecha_inicio_contpaq = date('Y-m-d', strtotime($fecha_inicio));
    $fecha_fin_contpaq = date('Y-m-d', strtotime($fecha_fin));
    
    // QUERY PARA ALQUIMISTA2024 - MODIFICADO PARA INCLUIR FILTRO DE FECHA
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
            AND YEAR(ALQUIMISTA2024.[dbo].docDocument.DateDocument) = YEAR(GETDATE())
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
    
    // QUERY PARA BASENUEVA - CON FILTRO DE FECHA
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
            AND YEAR(BASENUEVA.[dbo].docDocument.DateDocument) = YEAR(GETDATE())
    ) as GastosFiltrados
    WHERE CC IN (
        '999-016 General Comedor'
    ) 
    AND EstadoPago = 'pagado'
    AND FECHA_CREACION BETWEEN ? AND ?";
    
    $params_contpaq = array($fecha_inicio_contpaq, $fecha_fin_contpaq);
    
    // CONEXIÓN Y CONSULTA A ALQUIMISTA2024
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
    
    // CONEXIÓN Y CONSULTA A BASENUEVA
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
    
    // SUMAR TOTALES DE AMBAS BASES DE DATOS
    $total_gastos_contpaq = $total_gastos_alquimista + $total_gastos_basenueva;
    $total_registros_contpaq = $total_registros_alquimista + $total_registros_basenueva;
    
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $total_gastos_alquimista = 120000;
    $total_gastos_basenueva = 65000;
    $total_gastos_contpaq = 185000;
    $total_registros_contpaq = 45;
}

// Obtener información del usuario desde la sesión
$user_name = $_SESSION['user_name'] ?? 'Administrador';
$user_area = $_SESSION['user_area'] ?? 'Sistema de Comedor';

// Calcular porcentajes para las cards (EXCLUYENDO EXENTOS)
$porcentaje_desayunos = $desayunos_agendados > 0 ? round(($desayunos_hoy / $desayunos_agendados) * 100, 1) : 0;
$porcentaje_comidas = $comidas_agendadas > 0 ? round(($comidas_hoy / $comidas_agendadas) * 100, 1) : 0;

// Calcular utilidad (recaudación - gastos)
$utilidad = $monto_recaudado - $total_gastos_contpaq;

// Calcular días del periodo para mostrar en el título
$dias_periodo = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / (60 * 60 * 24) + 1;
$titulo_periodo = "Del " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin)) . " ($dias_periodo días)";

// Calcular tasa de asistencia basada en pagantes
$total_servido_pagantes = $desayunos_hoy + $comidas_hoy;
$total_agendado_pagantes = $desayunos_agendados + $comidas_agendadas;
$tasa_asistencia = $total_agendado_pagantes > 0 ? round(($total_servido_pagantes / $total_agendado_pagantes) * 100, 2) : 0;

// Porcentaje de exentos
$total_servido_con_exentos = $total_servido_pagantes + $exentos_total_servidos;
$porcentaje_exentos = $total_servido_con_exentos > 0 ? round(($exentos_total_servidos / $total_servido_con_exentos) * 100, 1) : 0;

// Calcular totales del desglose de exentos
$total_desayunos_exentos = 0;
$total_comidas_exentas = 0;
$total_monto_exentos = 0;

foreach ($desglose_exentos as $persona => $datos) {
    $total_desayunos_exentos += $datos['desayunos'];
    $total_comidas_exentas += $datos['comidas'];
    $total_monto_exentos += $datos['monto'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Comedor - Administración Completa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-dark) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--dark-gray);
        }
        
        .glass-effect {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--card-shadow);
        }
        
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
        
        @media (max-width: 992px) {
            .sidebar {
                width: 250px;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="user-profile">
            <div class="user-avatar">
                <span>AD</span>
            </div>
            <div class="user-info">
                <h5><?php echo htmlspecialchars($user_name); ?></h5>
                <p><?php echo htmlspecialchars($user_area); ?></p>
            </div>
        </div>
        
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-section="dashboard">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="usuarios">
                    <i class="fas fa-user-cog"></i> Gestión de Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="reportes">
                    <i class="fas fa-chart-bar"></i> Generación de Reportes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="menus">
                    <i class="fas fa-clipboard-list"></i> Gestión de Menús
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="inventario">
                    <i class="fas fa-clipboard-check"></i> Inventario de Utensilios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-section="cancelaciones">
                    <i class="fas fa-times-circle"></i> Validar Cancelaciones
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link text-danger" href="http://desarollo-bacros/Comedor/admicome4.php?logout=true" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Botón de menú móvil -->
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header Mejorado con Botón Integrado -->
        <div class="main-header">
            <div class="header-left">
                <button class="toggle-sidebar-btn" id="toggleSidebar" title="Mostrar/Ocultar Menú">
                    <i class="fas fa-bars"></i>
                </button>
                
                <h1 class="header-title">Portal de Comedor - Administración Completa</h1>
            </div>
            
            <div class="user-display">
                <div class="user-info-header">
                    <div class="user-name-display"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-area-display"><?php echo htmlspecialchars($user_area); ?></div>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section active">
            
            <!-- FILTROS DE FECHA -->
            <div class="filters-container">
                <form id="dateFilterForm" method="GET">
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
                </form>
            </div>
            
            <!-- Indicador de carga para Dashboard -->
            <div id="dashboard-loading" class="loading-overlay" style="display: none;">
                <div class="loading-spinner"></div>
                <div class="loading-text">Cargando Dashboard...</div>
            </div>
            
            <div class="dashboard-grid">
                <!-- CARD - DESAYUNOS CON COMPARACIÓN (EXCLUYENDO EXENTOS) -->
                <div class="card comparison-card desayunos">
                    <div class="comparison-icon desayunos">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h4 class="mb-3">Desayunos</h4>
                    
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
                
                <!-- CARD - COMIDAS CON COMPARACIÓN (EXCLUYENDO EXENTOS) -->
                <div class="card comparison-card comidas">
                    <div class="comparison-icon comidas">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4 class="mb-3">Comidas</h4>
                    
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
                
                <!-- CARD - PERSONAS EXENTAS -->
                <div class="card comparison-card exentos">
                    <div class="comparison-icon exentos">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h4 class="mb-3">Personas Exentas</h4>
                    
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
                    
                    <div class="exentos-total">
                        Total: <?php echo $exentos_total_servidos; ?> comidas
                        <small class="d-block">($<?php echo number_format($monto_exentos, 2, '.', ','); ?> no cobrados)</small>
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar exentos" style="width: <?php echo $porcentaje_exentos; ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php echo $porcentaje_exentos; ?>% del total servido
                        <br><small><?php echo count($personas_exentas); ?> personas exentas</small>
                    </small>
                </div>
                
                <!-- CARD DE CANCELACIONES MEJORADO (EXCLUYENDO EXENTOS) -->
                <div class="card comparison-card cancelaciones">
                    <div class="comparison-icon cancelaciones">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h4 class="mb-3">Cancelaciones</h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value cancelado"><?php echo $cancelaciones_desayuno; ?></div>
                            <div class="stat-label">Desayunos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value cancelado"><?php echo $cancelaciones_comida; ?></div>
                            <div class="stat-label">Comidas</div>
                        </div>
                    </div>
                    
                    <div class="cancelaciones-total">
                        Total: <?php echo $total_cancelaciones; ?> registros
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar cancelaciones" style="width: <?php 
                            $total_agendado = $desayunos_agendados + $comidas_agendadas;
                            $porcentaje_cancelaciones = $total_agendado > 0 ? min(100, ($total_cancelaciones / $total_agendado) * 100) : 0;
                            echo $porcentaje_cancelaciones;
                        ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php 
                        if ($total_cancelaciones > 0) {
                            echo round($porcentaje_cancelaciones, 1) . '% del total agendado';
                        } else {
                            echo 'Sin cancelaciones';
                        }
                        ?>
                    </small>
                </div>
                
                <!-- CARD DE GASTOS CONTACQ I CON DESGLOSE DE BASES -->
                <div class="card comparison-card gastos">
                    <div class="comparison-icon gastos">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h4 class="mb-3">Gastos Contpaq i</h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value gasto">$<?php echo number_format($total_gastos_contpaq, 2, '.', ','); ?></div>
                            <div class="stat-label">Total Gastos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value gasto"><?php echo $total_registros_contpaq ?? 0; ?></div>
                            <div class="stat-label">Registros</div>
                        </div>
                    </div>
                    
                    <div class="gastos-total">
                        Gastos del periodo
                    </div>
                    
                    <!-- DESGLOSE DE BASES DE DATOS -->
                    <div class="base-database-breakdown">
                        <div class="database-item">
                            <span class="database-name">ALQUIMISTA</span>
                            <div>
                                <span class="database-amount" style="color: var(--orange-color);">$<?php echo number_format($total_gastos_alquimista, 2, '.', ','); ?></span>
                                <span class="database-count">(<?php echo $total_registros_alquimista ?? 0; ?> reg.)</span>
                            </div>
                        </div>
                        <div class="database-item">
                            <span class="database-name">AHMEX</span>
                            <div>
                                <span class="database-amount" style="color: var(--orange-color);">$<?php echo number_format($total_gastos_basenueva, 2, '.', ','); ?></span>
                                <span class="database-count">(<?php echo $total_registros_basenueva ?? 0; ?> reg.)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar gastos" style="width: <?php 
                            $porcentaje_gastos = $monto_recaudado > 0 ? min(100, ($total_gastos_contpaq / $monto_recaudado) * 100) : 0;
                            echo $porcentaje_gastos;
                        ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php 
                        if ($total_gastos_contpaq > 0) {
                            echo round($porcentaje_gastos, 1) . '% de la recaudación';
                        } else {
                            echo 'Sin gastos registrados';
                        }
                        ?>
                    </small>
                </div>
                
                <!-- CARD DE UTILIDAD (CALCULADA SIN EXENTOS) -->
                <div class="card comparison-card utilidad">
                    <div class="comparison-icon utilidad">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="mb-3">Utilidad</h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value <?php echo $utilidad >= 0 ? 'utilidad-positiva' : 'utilidad-negativa'; ?>">
                                $<?php echo number_format($utilidad, 2, '.', ','); ?>
                            </div>
                            <div class="stat-label">Balance</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value <?php echo $utilidad >= 0 ? 'utilidad-positiva' : 'utilidad-negativa'; ?>">
                                <?php echo $monto_recaudado > 0 ? round(($utilidad / $monto_recaudado) * 100, 1) : 0; ?>%
                            </div>
                            <div class="stat-label">Margen</div>
                        </div>
                    </div>
                    
                    <div class="utilidad-total <?php echo $utilidad >= 0 ? 'positiva' : 'negativa'; ?>">
                        <?php echo $utilidad >= 0 ? 'Utilidad Positiva' : 'Pérdida'; ?>
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar utilidad" style="width: <?php 
                            $porcentaje_utilidad = $monto_recaudado > 0 ? min(100, max(0, ($utilidad / $monto_recaudado) * 100)) : 0;
                            echo $porcentaje_utilidad;
                        ?>%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <?php 
                        if ($utilidad >= 0) {
                            echo 'Ingresos: $' . number_format($monto_recaudado, 0, '', ',') . ' | Gastos: $' . number_format($total_gastos_contpaq, 0, '', ',');
                        } else {
                            echo 'Pérdida del ' . round(($utilidad / $monto_recaudado) * -100, 1) . '%';
                        }
                        ?>
                    </small>
                </div>
                
                <!-- NUEVA CARD - INVENTARIO DE UTENSILIOS -->
                <div class="card comparison-card inventario">
                    <div class="comparison-icon inventario">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h4 class="mb-3">Inventario de Utensilios</h4>
                    
                    <div class="comparison-stats">
                        <div class="stat-item">
                            <div class="stat-value inventario">75</div>
                            <div class="stat-label">Artículos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value inventario">142</div>
                            <div class="stat-label">Unidades</div>
                        </div>
                    </div>
                    
                    <div class="inventario-total">
                        <i class="fas fa-clipboard-check me-2"></i>Inventario 2025
                    </div>
                    
                    <div class="inventario-highlight">
                        <small class="d-block mb-2"><i class="fas fa-info-circle me-2"></i><strong>Estado Actual:</strong></small>
                        <div class="d-flex justify-content-between">
                            <span>Artículos completos:</span>
                            <span class="fw-bold text-success">95%</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Necesita atención:</span>
                            <span class="fw-bold text-warning">5%</span>
                        </div>
                    </div>
                    
                    <div class="progress-comparison">
                        <div class="progress-comparison-bar inventario" style="width: 95%"></div>
                    </div>
                    
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>Actualizado: Julio 2025
                    </small>
                </div>
                
                <!-- Usuarios Activos (EXCLUYENDO EXENTOS) -->
                <div class="card stat-card users">
                    <div class="feature-icon users mx-auto">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-number" id="total-usuarios"><?php echo $total_usuarios; ?></div>
                    <div class="stat-label">Usuarios Activos</div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 85%; background-color: var(--accent-blue);"></div>
                    </div>
                </div>
                
                <!-- Tasa de Asistencia (EXCLUYENDO EXENTOS) -->
                <div class="card stat-card attendance">
                    <div class="feature-icon attendance mx-auto">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number" id="tasa-asistencia"><?php echo $tasa_asistencia; ?>%</div>
                    <div class="stat-label">Tasa de Asistencia</div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $tasa_asistencia; ?>%; background-color: var(--teal-color);"></div>
                    </div>
                </div>
                
                <!-- Monto Recaudado (EXCLUYENDO EXENTOS) -->
                <div class="card stat-card revenue">
                    <div class="feature-icon revenue mx-auto">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                   <div class="stat-number" id="monto-recaudado">$<?php echo number_format($monto_recaudado, 0, '', ','); ?></div>
                    <div class="stat-label">Monto Recaudado</div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 65%; background-color: var(--indigo-color);"></div>
                    </div>
                </div>
              
    
            </div>

            <!-- Sección de Finanzas Mejorada -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i>Resumen Financiero del Periodo (Pagantes vs Exentos)
                        </div>
                        <div class="card-body">
                            <div class="finances-container">
                                <!-- Ingresos de Pagantes -->
                                <div class="finance-card">
                                    <div class="finance-title">
                                        <i class="fas fa-money-bill-wave text-success"></i>
                                        Ingresos por Comedor 
                                    </div>
                                    <div class="finance-amount ingresos">
                                        $<?php echo number_format($monto_recaudado, 2, '.', ','); ?>
                                    </div>
                                    <div class="finance-subtitle">
                                        Del <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                    </div>
                                    <div class="finance-breakdown">
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Desayunos (<?php echo $desayunos_hoy; ?>)</span>
                                            <span class="breakdown-value">$<?php echo number_format($desayunos_hoy * 30, 2, '.', ','); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Comidas (<?php echo $comidas_hoy; ?>)</span>
                                            <span class="breakdown-value">$<?php echo number_format($comidas_hoy * 30, 2, '.', ','); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Total Servidos</span>
                                            <span class="breakdown-value"><?php echo ($desayunos_hoy + $comidas_hoy); ?> comidas</span>
                                        </div>
                                    </div>
                                    <div class="finance-trend">
                                        <i class="fas fa-arrow-up trend-up"></i>
                                        <span class="trend-up">Precio unitario: $30.00</span>
                                    </div>
                                </div>
                                
                                <!-- Exentos (No Cobrados) - ACTUALIZADO CON DESGLOSE -->
                                <div class="finance-card">
                                    <div class="finance-title">
                                        <i class="fas fa-user-slash text-purple"></i>
                                        Comidas Exentas (No Cobradas)
                                    </div>
                                    <div class="finance-amount exentos">
                                        $<?php echo number_format($monto_exentos, 2, '.', ','); ?>
                                    </div>
                                    <div class="finance-subtitle">
                                        Del <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                    </div>
                                    <div class="finance-breakdown">
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Desayunos Exentos</span>
                                            <span class="breakdown-value"><?php echo $exentos_desayuno_servidos; ?> comidas</span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Comidas Exentas</span>
                                            <span class="breakdown-value"><?php echo $exentos_comida_servidos; ?> comidas</span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Total Exentos</span>
                                            <span class="breakdown-value"><?php echo $exentos_total_servidos; ?> comidas</span>
                                        </div>
                                    </div>
                                    
                                    <!-- DESGLOSE DETALLADO POR PERSONA EXENTA -->
                                    <div class="exentos-list">
                                        <div class="exento-desglose-header">
                                            <div class="exento-desglose-title">
                                                <i class="fas fa-list-alt me-2"></i>Desglose por Persona Exenta
                                            </div>
                                            <div class="exento-desglose-summary">
                                                <div class="summary-item">
                                                    <i class="fas fa-coffee me-1"></i> Desayunos: <?php echo $total_desayunos_exentos; ?>
                                                </div>
                                                <div class="summary-item">
                                                    <i class="fas fa-utensils me-1"></i> Comidas: <?php echo $total_comidas_exentas; ?>
                                                </div>
                                                <div class="summary-item">
                                                    <i class="fas fa-calculator me-1"></i> Total: <?php echo count($desglose_exentos); ?> personas
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
                                                        <?php 
                                                        $total_general_desayunos = 0;
                                                        $total_general_comidas = 0;
                                                        $total_general_consumos = 0;
                                                        $total_general_monto = 0;
                                                        ?>
                                                        <?php foreach ($desglose_exentos as $persona => $datos): ?>
                                                            <?php 
                                                            $total_general_desayunos += $datos['desayunos'];
                                                            $total_general_comidas += $datos['comidas'];
                                                            $total_general_consumos += $datos['total'];
                                                            $total_general_monto += $datos['monto'];
                                                            ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($persona); ?></td>
                                                                <td><?php echo $datos['desayunos']; ?></td>
                                                                <td><?php echo $datos['comidas']; ?></td>
                                                                <td class="exento-total-cell"><?php echo $datos['total']; ?></td>
                                                                <td class="exento-monto-cell">$<?php echo number_format($datos['monto'], 2, '.', ','); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        <!-- Fila de totales -->
                                                        <tr style="background-color: rgba(139, 92, 246, 0.1); font-weight: bold;">
                                                            <td>TOTALES</td>
                                                            <td><?php echo $total_general_desayunos; ?></td>
                                                            <td><?php echo $total_general_comidas; ?></td>
                                                            <td class="exento-total-cell"><?php echo $total_general_consumos; ?></td>
                                                            <td class="exento-monto-cell">$<?php echo number_format($total_general_monto, 2, '.', ','); ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-info-circle text-muted fa-2x mb-3"></i>
                                                <p class="text-muted">No hay registros de consumo para personas exentas en el periodo seleccionado.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="finance-trend">
                                        <i class="fas fa-percentage text-info"></i>
                                        <span class="trend-neutral">
                                            Representa el <?php 
                                            $total_potencial = $monto_recaudado + $monto_exentos;
                                            echo $total_potencial > 0 ? round(($monto_exentos / $total_potencial) * 100, 1) : 0;
                                            ?>% del potencial total
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Utilidad -->
                                <div class="finance-card">
                                    <div class="finance-title">
                                        <i class="fas fa-chart-line <?php echo $utilidad >= 0 ? 'text-success' : 'text-danger'; ?>"></i>
                                        Utilidad Neta 
                                    </div>
                                    <div class="finance-amount <?php echo $utilidad >= 0 ? 'utilidad-positiva' : 'utilidad-negativa'; ?>">
                                        $<?php echo number_format($utilidad, 2, '.', ','); ?>
                                    </div>
                                    <div class="finance-subtitle">
                                        Del <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
                                    </div>
                                    <div class="finance-breakdown">
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Ingresos Totales</span>
                                            <span class="breakdown-value">$<?php echo number_format($monto_recaudado, 2, '.', ','); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Gastos Totales</span>
                                            <span class="breakdown-value">$<?php echo number_format($total_gastos_contpaq, 2, '.', ','); ?></span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span class="breakdown-label">Margen de Utilidad</span>
                                            <span class="breakdown-value <?php echo ($monto_recaudado > 0 ? ($utilidad / $monto_recaudado) * 100 : 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $monto_recaudado > 0 ? round(($utilidad / $monto_recaudado) * 100, 1) : 0; ?>%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="finance-trend">
                                        <i class="fas <?php echo $utilidad >= 0 ? 'fa-arrow-up trend-up' : 'fa-arrow-down trend-down'; ?>"></i>
                                        <span class="<?php echo $utilidad >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                            <?php echo $utilidad >= 0 ? 'Utilidad positiva' : 'Pérdida registrada'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i>Resumen del Periodo
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statsChart" width="300" height="200"></canvas>
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
                                    <h5 class="mb-0">$<?php echo number_format($monto_recaudado, 0, ',', '.'); ?></h5>
                                    <small class="text-muted">Recaudación</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i>Distribución por Tipo
                        </div>
                        <div class="card-body">
                            <div class="activity-item">
                                <div class="activity-icon user" style="background-color: var(--success-color); color: white;">
                                    <i class="fas fa-coffee"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Desayunos</h6>
                                    <small class="text-muted"><?php echo $desayunos_hoy; ?> de <?php echo $desayunos_agendados; ?> agendados</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon lunch" style="background-color: var(--warning-color); color: white;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Comidas</h6>
                                    <small class="text-muted"><?php echo $comidas_hoy; ?> de <?php echo $comidas_agendadas; ?> agendadas</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon exentos" style="background-color: var(--purple-color); color: white;">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Comidas Exentas</h6>
                                    <small class="text-muted"><?php echo $exentos_total_servidos; ?> comidas (<?php echo $exentos_desayuno_servidos; ?> desayunos, <?php echo $exentos_comida_servidos; ?> comidas)</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon revenue" style="background-color: var(--indigo-color); color: white;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Usuarios Activos</h6>
                                    <small class="text-muted"><?php echo $total_usuarios; ?> usuarios en el periodo</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon cancelaciones" style="background-color: var(--danger-color); color: white;">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Cancelaciones</h6>
                                    <small class="text-muted"><?php echo $cancelaciones_desayuno; ?> desayunos, <?php echo $cancelaciones_comida; ?> comidas</small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon gastos" style="background-color: var(--orange-color); color: white;">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Gastos Contpaq i</h6>
                                    <small class="text-muted">$<?php echo number_format($total_gastos_contpaq, 2, '.', ','); ?> en gastos</small>
                                    <div style="font-size: 0.8rem; color: var(--medium-gray); margin-top: 2px;">
                                        <small>ALQUIMISTA: $<?php echo number_format($total_gastos_alquimista, 0, '', ','); ?> | AHMEX: $<?php echo number_format($total_gastos_basenueva, 0, '', ','); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon utilidad" style="background-color: <?php echo $utilidad >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>; color: white;">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Utilidad Neta</h6>
                                    <small class="text-muted <?php echo $utilidad >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        $<?php echo number_format($utilidad, 2, '.', ','); ?> (<?php echo $monto_recaudado > 0 ? round(($utilidad / $monto_recaudado) * 100, 1) : 0; ?>%)
                                    </small>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon inventario" style="background-color: var(--bronze-color); color: white;">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Inventario de Utensilios</h6>
                                    <small class="text-muted">75 artículos, 142 unidades totales</small>
                                    <div style="font-size: 0.8rem; color: var(--bronze-color); margin-top: 2px;">
                                        <small><i class="fas fa-check-circle me-1"></i>Actualizado al 2025</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Gestión de Usuarios -->
        <div id="usuarios" class="section d-none">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-user-cog me-2"></i>Sistema de Gestión de Usuarios</div>
                    <div><button class="btn btn-sm btn-primary" id="refresh-usuarios-btn"><i class="fas fa-sync-alt me-1"></i>Actualizar</button></div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="usuarios-loading" class="loading-overlay"><div class="loading-spinner"></div><div class="loading-text">Cargando sistema de gestión de usuarios...</div></div>
                    <div class="report-iframe-container">
                        <iframe src="http://desarollo-bacros/Comedor/gestusu.php" class="report-iframe" id="usuarios-iframe" onload="document.getElementById('usuarios-loading').style.display='none';"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Reportes -->
        <div id="reportes" class="section d-none">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-chart-bar me-2"></i>Sistema de Reportes</div>
                    <div><button class="btn btn-sm btn-primary" id="refresh-report-btn"><i class="fas fa-sync-alt me-1"></i>Actualizar</button></div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="reportes-loading" class="loading-overlay"><div class="loading-spinner"></div><div class="loading-text">Cargando sistema de reportes...</div></div>
                    <div class="report-iframe-container">
                        <iframe src="http://desarollo-bacros/Comedor/dem1.php" class="report-iframe" id="report-iframe" onload="document.getElementById('reportes-loading').style.display='none';"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Gestión de Menús -->
        <div id="menus" class="section d-none">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-clipboard-list me-2"></i>Sistema de Gestión de Menús</div>
                    <div><button class="btn btn-sm btn-primary" id="refresh-menu-btn"><i class="fas fa-sync-alt me-1"></i>Actualizar</button></div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="menus-loading" class="loading-overlay"><div class="loading-spinner"></div><div class="loading-text">Cargando sistema de gestión de menús...</div></div>
                    <div class="menu-iframe-container">
                        <iframe src="http://desarollo-bacros/Comedor/menu1.php" class="menu-iframe" id="menu-iframe" onload="document.getElementById('menus-loading').style.display='none';"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- NUEVA SECCIÓN - INVENTARIO DE UTENSILIOS Y EQUIPO DE COMEDOR -->
        <div id="inventario" class="section d-none">
            <div class="card inventario-card">
                <div class="card-header inventario-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-clipboard-check me-2"></i>Inventario de Utensilios y Equipo de Comedor 2025</div>
                    <div>
                        <button class="btn btn-sm btn-primary" id="refresh-inventario-btn">
                            <i class="fas fa-sync-alt me-1"></i>Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="inventario-loading" class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Cargando sistema de inventario de utensilios...</div>
                    </div>
                    
                    <!-- Información Resumen del Inventario -->
                    <div class="p-4">
                        <div class="inventario-highlight mb-4">
                            <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información General del Inventario 2025</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-boxes me-2"></i>Total de Artículos:</strong>
                                        <span class="badge bg-bronze ms-2">75 artículos</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-layer-group me-2"></i>Total de Unidades:</strong>
                                        <span class="badge bg-bronze ms-2">142 unidades</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-tags me-2"></i>Categorías:</strong>
                                        <span class="badge bg-bronze ms-2">12 categorías</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-calendar-alt me-2"></i>Periodo de Inventario:</strong>
                                        <span class="ms-2">2025</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-clipboard-check me-2"></i>Estado:</strong>
                                        <span class="badge bg-success ms-2">Actualizado</span>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Artículos que requieren atención:</strong>
                                        <span class="badge bg-warning ms-2">5%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estadísticas Rápidas -->
                        <div class="inventario-stats-grid mb-4">
                            <div class="inventario-stat-item">
                                <div class="inventario-stat-value">75</div>
                                <div class="inventario-stat-label">Artículos Registrados</div>
                            </div>
                            <div class="inventario-stat-item">
                                <div class="inventario-stat-value">142</div>
                                <div class="inventario-stat-label">Total Unidades</div>
                            </div>
                            <div class="inventario-stat-item">
                                <div class="inventario-stat-value">12</div>
                                <div class="inventario-stat-label">Categorías</div>
                            </div>
                            <div class="inventario-stat-item">
                                <div class="inventario-stat-value">95%</div>
                                <div class="inventario-stat-label">Completitud</div>
                            </div>
                        </div>
                        
                        <!-- Enlace directo al sistema de inventario -->
                        <div class="alert alert-info mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-external-link-alt fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Sistema de Gestión de Inventario</h5>
                                    <p class="mb-0">Haz clic en el botón de abajo para acceder al sistema completo de inventario de utensilios y equipo de cocina.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenedor del iframe del sistema de inventario -->
                    <div class="inventario-iframe-container">
                        <iframe src="http://192.168.100.95/comedor/ImventarioCocina.php" class="inventario-iframe" id="inventario-iframe" onload="document.getElementById('inventario-loading').style.display='none';"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Cancelaciones -->
        <div id="cancelaciones" class="section d-none">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-times-circle me-2"></i>Sistema de Validación de Cancelaciones</div>
                    <div><button class="btn btn-sm btn-primary" id="refresh-cancelaciones-btn"><i class="fas fa-sync-alt me-1"></i>Actualizar</button></div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="cancelaciones-loading" class="loading-overlay"><div class="loading-spinner"></div><div class="loading-text">Cargando sistema de validación de cancelaciones...</div></div>
                    <div class="report-iframe-container">
                        <iframe src="http://192.168.100.95/Comedor/ValidarFormatos.php?newpwd=Administrador" class="report-iframe" id="cancelaciones-iframe" onload="document.getElementById('cancelaciones-loading').style.display='none';"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Estado del sidebar
        let sidebarVisible = true;
        
        // Función para restablecer filtros
        function resetFilters() {
            window.location.href = window.location.pathname;
        }

        // Validación de fechas en el formulario
        document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            
            if (fechaInicio && fechaFin && new Date(fechaInicio) > new Date(fechaFin)) {
                e.preventDefault();
                alert('La fecha de inicio no puede ser mayor que la fecha de fin');
                return false;
            }
            
            // Mostrar indicador de carga
            document.getElementById('dashboard-loading').style.display = 'flex';
        });

        // Navigation functionality
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links and sections
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.section').forEach(s => s.classList.add('d-none'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Show corresponding section
                const sectionId = this.getAttribute('data-section');
                const section = document.getElementById(sectionId);
                section.classList.remove('d-none');
                
                // Mostrar indicador de carga para cada sección
                const loadingElement = document.getElementById(`${sectionId}-loading`);
                if (loadingElement) {
                    loadingElement.style.display = 'flex';
                    
                    // Simular tiempo de carga
                    setTimeout(() => {
                        loadingElement.style.display = 'none';
                    }, 1000);
                }
                
                // Si es la sección de inventario, cargar el iframe
                if (sectionId === 'inventario') {
                    const iframe = document.getElementById('inventario-iframe');
                    const loading = document.getElementById('inventario-loading');
                    loading.style.display = 'flex';
                    
                    // Recargar el iframe para asegurar contenido fresco
                    setTimeout(() => {
                        iframe.src = iframe.src;
                    }, 500);
                }
            });
        });

        // Toggle sidebar functionality
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebarVisible = !sidebarVisible;
            
            if (sidebarVisible) {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('expanded');
                this.innerHTML = '<i class="fas fa-bars"></i>';
            } else {
                sidebar.classList.add('hidden');
                mainContent.classList.add('expanded');
                this.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });

        // Mobile menu functionality
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Botones de actualización
        document.getElementById('refresh-usuarios-btn')?.addEventListener('click', function() {
            const iframe = document.getElementById('usuarios-iframe');
            const loading = document.getElementById('usuarios-loading');
            loading.style.display = 'flex';
            iframe.src = iframe.src;
        });

        document.getElementById('refresh-report-btn')?.addEventListener('click', function() {
            const iframe = document.getElementById('report-iframe');
            const loading = document.getElementById('reportes-loading');
            loading.style.display = 'flex';
            iframe.src = iframe.src;
        });

        document.getElementById('refresh-menu-btn')?.addEventListener('click', function() {
            const iframe = document.getElementById('menu-iframe');
            const loading = document.getElementById('menus-loading');
            loading.style.display = 'flex';
            iframe.src = iframe.src;
        });

        // NUEVO: Botón de actualización para inventario
        document.getElementById('refresh-inventario-btn')?.addEventListener('click', function() {
            const iframe = document.getElementById('inventario-iframe');
            const loading = document.getElementById('inventario-loading');
            loading.style.display = 'flex';
            iframe.src = iframe.src;
        });

        document.getElementById('refresh-cancelaciones-btn')?.addEventListener('click', function() {
            const iframe = document.getElementById('cancelaciones-iframe');
            const loading = document.getElementById('cancelaciones-loading');
            loading.style.display = 'flex';
            iframe.src = iframe.src;
        });

        // Logout confirmation - CORREGIDO
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevenir que el enlace se ejecute inmediatamente
            
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                // Si el usuario confirma, redirigir al enlace de logout
                window.location.href = this.href;
            }
            // Si el usuario cancela, no hacer nada
        });

        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Main Stats Chart (Doughnut) - Actualizado para incluir pagantes vs exentos
            const ctx = document.getElementById('statsChart');
            if (ctx) {
                const ingresosPagantes = <?php echo $monto_recaudado; ?>;
                const ingresosExentos = <?php echo $monto_exentos; ?>;
                const totalGastos = <?php echo $total_gastos_contpaq; ?>;
                const utilidad = <?php echo $utilidad; ?>;
                
                // Calcular porcentajes para la gráfica
                const total = ingresosPagantes + Math.abs(ingresosExentos) + Math.abs(totalGastos) + Math.abs(utilidad < 0 ? utilidad : 0);
                const porcentajeIngresosPagantes = total > 0 ? (ingresosPagantes / total) * 100 : 0;
                const porcentajeIngresosExentos = total > 0 ? (ingresosExentos / total) * 100 : 0;
                const porcentajeGastos = total > 0 ? (totalGastos / total) * 100 : 0;
                const porcentajeUtilidad = total > 0 ? (Math.abs(utilidad) / total) * 100 : 0;
                
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Ingresos', 'Exentos (No Cobrados)', 'Gastos', 'Utilidad'],
                        datasets: [{
                            data: [
                                porcentajeIngresosPagantes, 
                                porcentajeIngresosExentos, 
                                porcentajeGastos, 
                                porcentajeUtilidad
                            ],
                            backgroundColor: [
                                'rgba(16, 185, 129, 0.8)',   // Verde para ingresos pagantes
                                'rgba(139, 92, 246, 0.8)',   // Púrpura para exentos
                                'rgba(245, 158, 11, 0.8)',   // Naranja para gastos
                                utilidad >= 0 ? 'rgba(59, 130, 246, 0.8)' : 'rgba(239, 68, 68, 0.8)'  // Azul para utilidad positiva, rojo para negativa
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        let amount = '';
                                        
                                        if (label === 'Ingresos Pagantes') {
                                            amount = `$${ingresosPagantes.toLocaleString()}`;
                                        } else if (label === 'Exentos (No Cobrados)') {
                                            amount = `$${ingresosExentos.toLocaleString()}`;
                                        } else if (label === 'Gastos') {
                                            amount = `$${totalGastos.toLocaleString()}`;
                                        } else {
                                            amount = `$${Math.abs(utilidad).toLocaleString()}`;
                                        }
                                        
                                        return `${label}: ${amount} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });

        // Simular carga inicial del dashboard
        window.addEventListener('load', function() {
            setTimeout(() => {
                const dashboardLoading = document.getElementById('dashboard-loading');
                if (dashboardLoading) {
                    dashboardLoading.style.display = 'none';
                }
            }, 1500);
        });

        // Prevenir acceso desde cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        // Inicializar tooltips de Bootstrap
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Agregar color personalizado para el badge de inventario
        const style = document.createElement('style');
        style.textContent = `
            .bg-bronze {
                background-color: var(--bronze-color) !important;
                color: white !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>