<?php
// ==================== CONFIGURACIÓN Y CONEXIÓN ====================
session_start();

// Configuración centralizada
define('DB_SERVER', "DESAROLLO-BACRO\SQLEXPRESS");
define('DB_DATABASE', "Comedor");
define('DB_USERNAME', "Larome03");
define('DB_PASSWORD', "Larome03");
define('DB_CHARSET', "UTF-8");

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para conexión a base de datos
function getDatabaseConnection() {
    $connectionOptions = [
        "Database" => DB_DATABASE,
        "Uid" => DB_USERNAME,
        "PWD" => DB_PASSWORD,
        "CharacterSet" => DB_CHARSET,
        "TrustServerCertificate" => true,
    ];
    
    $conn = sqlsrv_connect(DB_SERVER, $connectionOptions);
    if (!$conn) {
        die("Error de conexión: " . print_r(sqlsrv_errors(), true));
    }
    
    return $conn;
}

// Función para validar usuario
function validarUsuario($conn, $idEmpleado, $nombre) {
    $sql = "SELECT Id_Empleado, Nombre, Area FROM ConPed WHERE Id_Empleado = ? AND Nombre LIKE ?";
    $params = array($idEmpleado, '%' . $nombre . '%');
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return false;
    }
    
    $usuarioData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    
    return $usuarioData ? $usuarioData : false;
}

// Función para obtener datos del usuario por nombre
function obtenerUsuarioPorNombre($conn, $nombre) {
    $sql = "SELECT Id_Empleado, Nombre, Area FROM ConPed WHERE Nombre LIKE ?";
    $params = array('%' . $nombre . '%');
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return false;
    }
    
    $usuarioData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    
    return $usuarioData ? $usuarioData : false;
}

// ==================== VERIFICAR AUTENTICACIÓN ====================
$conn = getDatabaseConnection();
$usuarioAutenticado = false;
$usuarioData = null;

// Variables de sesión del usuario
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_area = $_SESSION['user_area'] ?? 'Sistema de Comedor';

// Valores por defecto para los controles
$default_id_empleado = '';
$default_nombre = $user_name;

// Obtener automáticamente el ID del empleado basado en el nombre de sesión
if ($conn && !empty($user_name) && $user_name != 'Usuario') {
    $usuarioFromDB = obtenerUsuarioPorNombre($conn, $user_name);
    if ($usuarioFromDB) {
        $default_id_empleado = $usuarioFromDB['Id_Empleado'];
        $default_nombre = $usuarioFromDB['Nombre'];
        $user_area = $usuarioFromDB['Area']; // Actualizar área desde la base de datos
        
        // Si encontramos el usuario automáticamente, lo autenticamos directamente
        $usuarioAutenticado = true;
        $usuarioData = $usuarioFromDB;
        $_SESSION['usuario_autenticado'] = true;
        $_SESSION['usuario_data'] = $usuarioFromDB;
        $_SESSION['user_name'] = $usuarioFromDB['Nombre'];
        $_SESSION['user_area'] = $usuarioFromDB['Area'];
    }
}

// Verificar si ya está autenticado
if (isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado'] === true) {
    $usuarioAutenticado = true;
    $usuarioData = $_SESSION['usuario_data'];
    
    // Actualizar variables de sesión con datos del usuario autenticado
    if (isset($usuarioData['Nombre'])) {
        $_SESSION['user_name'] = $usuarioData['Nombre'];
        $user_name = $usuarioData['Nombre'];
        $default_nombre = $usuarioData['Nombre'];
    }
    if (isset($usuarioData['Area'])) {
        $_SESSION['user_area'] = $usuarioData['Area'];
        $user_area = $usuarioData['Area'];
    }
    if (isset($usuarioData['Id_Empleado'])) {
        $default_id_empleado = $usuarioData['Id_Empleado'];
    }
}

// Verificar login manual
if (isset($_POST['login']) && $conn) {
    $idEmpleado = $_POST['id_empleado'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    
    if (!empty($idEmpleado) && !empty($nombre)) {
        $usuarioData = validarUsuario($conn, $idEmpleado, $nombre);
        if ($usuarioData) {
            $usuarioAutenticado = true;
            $_SESSION['usuario_autenticado'] = true;
            $_SESSION['usuario_data'] = $usuarioData;
            
            // Establecer variables de sesión del usuario
            $_SESSION['user_name'] = $usuarioData['Nombre'];
            $_SESSION['user_area'] = $usuarioData['Area'];
            $user_name = $usuarioData['Nombre'];
            $user_area = $usuarioData['Area'];
            $default_id_empleado = $usuarioData['Id_Empleado'];
            $default_nombre = $usuarioData['Nombre'];
        } else {
            $errorAuth = "Credenciales incorrectas";
        }
    } else {
        $errorAuth = "Complete todos los campos";
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ==================== EJECUTAR QUERY COMPLEJO ====================
$reporteData = [];
$consumosDiarios = [];
$totalConsumos = 0;
$totalEntradas = 0;
$montoTotal = 0;
$diasConConsumo = 0;
$debugInfo = [];
$miFilaData = null;
$consumosPorDia = [];
$detalleConsumosPorDia = [];

if ($usuarioAutenticado && $conn) {
    // Obtener datos del usuario autenticado
    $miIdEmpleado = $usuarioData['Id_Empleado'];
    $miNombre = $usuarioData['Nombre'];
    
    // Fechas por defecto
    $fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');
    
    // Inicializar arrays de consumos por día (SOLO DÍAS HÁBILES)
    $consumosPorDia = [
        'Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0,
        'Thursday' => 0, 'Friday' => 0
    ];
    
    $detalleConsumosPorDia = [
        'Monday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
        'Tuesday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
        'Wednesday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
        'Thursday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
        'Friday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0]
    ];
    
    // Query completo proporcionado
    $sql = "
    DECLARE @columns AS NVARCHAR(MAX), @sql AS NVARCHAR(MAX);
    DECLARE @columnsSum NVARCHAR(MAX);

    -- 1. Obtener lista de fechas para columnas pivote
    SELECT @columns = STRING_AGG(QUOTENAME(CAST(Fecha AS CHAR)), ', ')
                      WITHIN GROUP (ORDER BY Fecha ASC)
    FROM (
        SELECT DISTINCT CAST(Fecha AS date) as Fecha
        FROM (
            SELECT 
                CAST(Fecha_Dia AS DATE) AS Fecha,
                Id_Empleado,
                Nombre,
                Tipo_Comida
            FROM (
                SELECT 
                    Id_Empleado,
                    Usuario,
                    Fecha AS Fecha_Base,
                    Dia,
                    DATEADD(DAY,
                            CASE Dia
                                WHEN 'Lunes' THEN 0
                                WHEN 'Martes' THEN 1
                                WHEN 'Miercoles' THEN 2
                                WHEN 'Jueves' THEN 3
                                WHEN 'Viernes' THEN 4
                            END
                            - (DATEPART(WEEKDAY, Fecha) + @@DATEFIRST - 2) % 7
                        , Fecha) AS Fecha_Dia,
                    Tipo_Comida,
                    Costo
                FROM PedidosComida
                CROSS APPLY (
                    VALUES 
                        ('Lunes', Lunes),
                        ('Martes', Martes),
                        ('Miercoles', Miercoles),
                        ('Jueves', Jueves),
                        ('Viernes', Viernes)
                ) AS unpivoted(Dia, Tipo_Comida)
            ) AS e1
            RIGHT JOIN
                (SELECT Id_Empleado AS Id_Empleado2, Nombre FROM ConPed) AS e2
            ON e1.Id_Empleado = e2.Id_Empleado2
        ) AS E4
        WHERE CAST(Fecha AS CHAR) >= '$fechaInicio' AND CAST(Fecha AS CHAR) <= '$fechaFin'
    ) AS FechaList;

    -- 2. Construir la suma de columnas dinámicamente
    SELECT @columnsSum = STRING_AGG('ISNULL(' + TRIM(value) + ', 0)', ' + ')
    FROM STRING_SPLIT(REPLACE(@columns, ' ', ''), ',');

    -- 3. Construir la consulta dinámica completa
    SET @sql = N'
    ;WITH EntradasCTE AS (
        SELECT Empleado, Nombre, Total FROM (
            SELECT No_Empleado AS Empleado, Nombre, COUNT(*) AS Total FROM (
                SELECT 
                    Fecha1,
                    NE_EXTRAIDO1 AS No_Empleado,
                    c.Nombre,
                    Tipo_Comida
                FROM (
                    SELECT *, 
                        NE_EXTRAIDO1 =
                            CASE 
                                WHEN Nombre LIKE ''%dionisio%'' THEN ''46''
                                WHEN Nombre LIKE ''%esquivel edgar%'' OR Nombre LIKE ''%edgar gutie%'' OR Nombre LIKE ''%GUTIERREZ EZQUIVEL%'' THEN ''18''
                                WHEN Nombre LIKE ''%Luna castro%'' THEN ''1''
                                ELSE NE_Extraido
                            END
                    FROM (
                        SELECT *,
                            LTRIM(RTRIM(
                                CASE
                                    WHEN CHARINDEX(''N.E:'', Nombre) > 0 THEN
                                        SUBSTRING(
                                            Nombre,
                                            CHARINDEX(''N.E:'', Nombre) + LEN(''N.E:''), 
                                            CASE
                                                WHEN CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''N.E:'', Nombre)) > 0 THEN
                                                    CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''N.E:'', Nombre)) - (CHARINDEX(''N.E:'', Nombre) + LEN(''N.E:'')) 
                                                ELSE LEN(Nombre)
                                            END
                                        )
                                    WHEN CHARINDEX(''NE: '', Nombre) > 0 THEN
                                        SUBSTRING(
                                            Nombre,
                                            CHARINDEX(''NE: '', Nombre) + LEN(''NE: ''), 
                                            CASE
                                                WHEN CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''NE: '', Nombre)) > 0 THEN
                                                    CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''NE: '', Nombre)) - (CHARINDEX(''NE: '', Nombre) + LEN(''NE: ''))
                                                ELSE LEN(Nombre)
                                            END
                                        )
                                    WHEN CHARINDEX(''NE:'', Nombre) > 0 THEN
                                        SUBSTRING(
                                            Nombre,
                                            CHARINDEX(''NE:'', Nombre) + LEN(''NE:''), 
                                            CASE
                                                WHEN CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''NE:'', Nombre)) > 0 THEN
                                                    CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''NE:'', Nombre)) - (CHARINDEX(''NE:'', Nombre) + LEN(''NE:'')) 
                                                ELSE LEN(Nombre)
                                            END
                                        )
                                    WHEN CHARINDEX(''ID:NE0'', Nombre) > 0 THEN
                                        SUBSTRING(
                                            Nombre,
                                            CHARINDEX(''ID:NE0'', Nombre) + LEN(''ID:NE0''), 
                                            CASE
                                                WHEN CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''ID:NE0'', Nombre)) > 0 THEN
                                                    CHARINDEX(''DEPARTAMENTO'', Nombre, CHARINDEX(''ID:NE0'', Nombre)) - (CHARINDEX(''ID:NE0'', Nombre) + LEN(''ID:NE0'')) 
                                                ELSE LEN(Nombre)
                                            END
                                        )
                                    ELSE NULL
                                END
                            )) AS NE_Extraido,
                            CASE
                                WHEN CAST(Fecha AS TIME) < ''12:00:00'' THEN ''Desayuno''
                                ELSE ''Comida''
                            END AS Tipo_Comida,
                            CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha1
                        FROM Entradas
                        WHERE Nombre LIKE ''%N.E:%'' OR Nombre LIKE ''%NE:%'' OR Nombre LIKE ''%ID:NE0%''
                    ) AS a
                ) AS b
                LEFT JOIN (SELECT Id_Empleado, Nombre FROM ConPed) AS c ON b.NE_EXTRAIDO1 = c.Id_Empleado
                WHERE LTRIM(RTRIM(Fecha1)) >= ''$fechaInicio'' AND LTRIM(RTRIM(Fecha1)) <= ''$fechaFin''
            ) AS tt1
            GROUP BY No_Empleado, Nombre
        ) AS tt3
    )

    SELECT pvt.Id_Empleado, pvt.Nombre, ' + @columns + ',
           ' + @columnsSum + ' AS TotalConsumos,
           ISNULL(e.Empleado, pvt.Id_Empleado) AS Empleado,
           ISNULL(e.Nombre, pvt.Nombre) AS NombreEntradas,
           ISNULL(e.Total, 0) AS TotalEntradas
    FROM (
        SELECT 
            CAST(Fecha AS CHAR) AS fecha,
            Id_Empleado,
            Nombre,
            Tipo_Comida = CASE WHEN Tipo_Comida = '''' THEN 0 ELSE 1 END
        FROM (
            SELECT 
                CAST(Fecha_Dia AS DATE) AS Fecha,
                Id_Empleado,
                Nombre,
                Tipo_Comida
            FROM (
                SELECT * FROM (
                    SELECT 
                        Id_Empleado,
                        Usuario,
                        Fecha AS Fecha_Base,
                        Dia,
                        DATEADD(DAY,
                                CASE Dia
                                    WHEN ''Lunes'' THEN 0
                                    WHEN ''Martes'' THEN 1
                                    WHEN ''Miercoles'' THEN 2
                                    WHEN ''Jueves'' THEN 3
                                    WHEN ''Viernes'' THEN 4
                                END
                                - (DATEPART(WEEKDAY, Fecha) + @@DATEFIRST - 2) % 7
                            , Fecha) AS Fecha_Dia,
                        Tipo_Comida,
                        Costo
                    FROM PedidosComida
                    CROSS APPLY (
                        VALUES
                            (''Lunes'', Lunes),
                            (''Martes'', Martes),
                            (''Miercoles'', Miercoles),
                            (''Jueves'', Jueves),
                            (''Viernes'', Viernes)
                    ) AS unpivoted(Dia, Tipo_Comida)
                ) AS e1
                RIGHT JOIN
                    (SELECT Id_Empleado AS Id_Empleado2, Nombre FROM ConPed) AS e2
                ON e1.Id_Empleado = e2.Id_Empleado2
            ) AS E4
        ) AS E5
        WHERE CAST(Fecha AS CHAR) >= ''$fechaInicio'' AND CAST(Fecha AS CHAR) <= ''$fechaFin''
    ) AS SourceTable
    PIVOT (
        SUM(Tipo_Comida)
        FOR fecha IN (' + @columns + ')
    ) AS pvt
    LEFT JOIN EntradasCTE e ON pvt.Id_Empleado = e.Empleado
    ORDER BY pvt.Id_Empleado;
    ';

    -- 4. Ejecutar el SQL dinámico
    EXEC sp_executesql @sql;
    ";
    
    // Ejecutar el query
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt) {
        // ==================== DIAGNÓSTICO MEJORADO ====================
        $debugData = [];
        $allFechas = [];
        
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Filtrar solo los registros del usuario actual
            if ($row['Id_Empleado'] == $miIdEmpleado || $row['Nombre'] == $miNombre) {
                $reporteData[] = $row;
                $miFilaData = $row; // Guardar la fila específica del usuario
                
                // Procesamiento MEJORADO de consumos
                $consumosEnEstaFila = 0;
                $fechasConConsumoEnFila = [];
                
                foreach ($row as $campo => $valor) {
                    // Detección MEJORADA de fechas
                    $esFecha = false;
                    $fechaNormalizada = '';
                    
                    // Múltiples patrones de fecha
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $campo)) {
                        $esFecha = true;
                        $fechaNormalizada = $campo;
                    } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $campo)) {
                        $esFecha = true;
                        $fechaNormalizada = str_replace('/', '-', $campo);
                    } elseif (strpos($campo, '202') === 0) {
                        $esFecha = true;
                        // Intentar normalizar cualquier formato que empiece con 202
                        $fechaNormalizada = date('Y-m-d', strtotime($campo));
                    }
                    
                    if ($esFecha && $fechaNormalizada) {
                        // Verificar si esta fecha está en el rango
                        if ($fechaNormalizada >= $fechaInicio && $fechaNormalizada <= $fechaFin) {
                            $allFechas[$fechaNormalizada] = $campo; // Guardar mapeo
                            
                            // Detección MEJORADA de consumo
                            $tieneConsumo = false;
                            $cantidadConsumos = 0;
                            
                            if ($valor === 1 || $valor === '1' || $valor === true) {
                                $tieneConsumo = true;
                                $cantidadConsumos = 1;
                            } elseif (is_numeric($valor) && floatval($valor) > 0) {
                                $tieneConsumo = true;
                                $cantidadConsumos = intval($valor);
                            } elseif ($valor === 'S' || $valor === 'X' || $valor === '✔') {
                                $tieneConsumo = true;
                                $cantidadConsumos = 1;
                            }
                            
                            if ($tieneConsumo) {
                                $consumosEnEstaFila += $cantidadConsumos;
                                $fechasConConsumoEnFila[] = [
                                    'campo_original' => $campo,
                                    'fecha_normalizada' => $fechaNormalizada,
                                    'valor' => $valor,
                                    'cantidad' => $cantidadConsumos
                                ];
                                
                                // ==================== CÁLCULO CORREGIDO DE CONSUMOS POR DÍA ====================
                                $diaSemanaIngles = date('l', strtotime($fechaNormalizada));
                                
                                // SOLO CONTAR DÍAS HÁBILES (Lunes a Viernes)
                                if (isset($consumosPorDia[$diaSemanaIngles])) {
                                    $consumosPorDia[$diaSemanaIngles] += $cantidadConsumos;
                                    
                                    // Detectar tipo de comida basado en la hora (si es posible)
                                    // Por ahora asumimos que cada consumo es una comida completa
                                    // En una implementación real, esto debería venir del query
                                    if (isset($detalleConsumosPorDia[$diaSemanaIngles])) {
                                        // Si hay más de 1 consumo en el mismo día, asumimos desayuno + comida
                                        if ($cantidadConsumos >= 2) {
                                            $detalleConsumosPorDia[$diaSemanaIngles]['desayunos'] += 1;
                                            $detalleConsumosPorDia[$diaSemanaIngles]['comidas'] += 1;
                                            $detalleConsumosPorDia[$diaSemanaIngles]['total'] += 2;
                                        } else {
                                            // Si es 1 consumo, asumimos que es comida
                                            $detalleConsumosPorDia[$diaSemanaIngles]['comidas'] += 1;
                                            $detalleConsumosPorDia[$diaSemanaIngles]['total'] += 1;
                                        }
                                    }
                                }
                                
                                $consumosDiarios[$fechaNormalizada] = [
                                    'fecha' => $fechaNormalizada,
                                    'dia_semana' => $diaSemanaIngles,
                                    'consumo' => true,
                                    'cantidad' => $cantidadConsumos,
                                    'monto' => $cantidadConsumos * 30,
                                    'campo_original' => $campo,
                                    'valor_original' => $valor
                                ];
                                $diasConConsumo++;
                            }
                        }
                    }
                }
                
                // Información de debug
                $debugData[] = [
                    'id_empleado' => $row['Id_Empleado'],
                    'nombre' => $row['Nombre'],
                    'total_consumos_campo' => $row['TotalConsumos'] ?? 0,
                    'consumos_detectados' => $consumosEnEstaFila,
                    'fechas_con_consumo' => $fechasConConsumoEnFila,
                    'total_entradas' => $row['TotalEntradas'] ?? 0
                ];
                
                $totalConsumos += isset($row['TotalConsumos']) ? intval($row['TotalConsumos']) : 0;
                $totalEntradas += isset($row['TotalEntradas']) ? intval($row['TotalEntradas']) : 0;
            }
        }
        
        // Re-procesar para asegurar consistencia
        if (!empty($reporteData)) {
            $totalConsumos = 0;
            foreach ($reporteData as &$fila) {
                $consumosEnFila = 0;
                foreach ($fila as $campo => $valor) {
                    if (isset($allFechas[$campo]) || in_array($campo, $allFechas)) {
                        if (is_numeric($valor)) {
                            $consumosEnFila += intval($valor);
                        } elseif ($valor == 1 || $valor === '1' || $valor === true) {
                            $consumosEnFila += 1;
                        }
                    }
                }
                $fila['TotalConsumosCalculado'] = $consumosEnFila;
                $totalConsumos += $consumosEnFila;
            }
        }
        
        // ==================== CÁLCULO CORREGIDO DEL MONTO TOTAL ====================
        // Usar los datos específicos de la fila del usuario
        if ($miFilaData) {
            $misConsumos = intval($miFilaData['TotalConsumos'] ?? $miFilaData['TotalConsumosCalculado'] ?? 0);
            $misEntradas = intval($miFilaData['TotalEntradas'] ?? 0);
            
            // Lógica corregida: 
            // - Si misConsumos > misEntradas: (misConsumos - misEntradas) * 60 + misEntradas * 30
            // - Si misConsumos <= misEntradas: misConsumos * 30
            
            if ($misConsumos > $misEntradas) {
                $consumosSinEntrada = $misConsumos - $misEntradas;
                $montoTotal = ($consumosSinEntrada * 60) + ($misEntradas * 30);
            } else {
                $montoTotal = $misConsumos * 30;
            }
            
            // Actualizar totales para mostrar correctamente en las tarjetas
            $totalConsumos = $misConsumos;
            $totalEntradas = $misEntradas;
        }
        
        // ==================== VERIFICACIÓN DEL CÁLCULO DE CONSUMOS POR DÍA ====================
        // Recalcular para asegurar que coincida con el total (SOLO DÍAS HÁBILES)
        $totalConsumosDiasHabiles = array_sum($consumosPorDia);
        
        // Si hay discrepancia, recalcular desde consumosDiarios (SOLO DÍAS HÁBILES)
        if ($totalConsumosDiasHabiles != $totalConsumos) {
            $consumosPorDia = [
                'Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0,
                'Thursday' => 0, 'Friday' => 0
            ];
            
            $detalleConsumosPorDia = [
                'Monday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
                'Tuesday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
                'Wednesday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
                'Thursday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0],
                'Friday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0]
            ];
            
            foreach ($consumosDiarios as $consumo) {
                $diaSemana = $consumo['dia_semana'];
                $cantidad = $consumo['cantidad'] ?? 1;
                
                // SOLO CONTAR DÍAS HÁBILES
                if (isset($consumosPorDia[$diaSemana])) {
                    $consumosPorDia[$diaSemana] += $cantidad;
                    
                    // Actualizar detalle
                    if ($cantidad >= 2) {
                        $detalleConsumosPorDia[$diaSemana]['desayunos'] += 1;
                        $detalleConsumosPorDia[$diaSemana]['comidas'] += 1;
                        $detalleConsumosPorDia[$diaSemana]['total'] += 2;
                    } else {
                        $detalleConsumosPorDia[$diaSemana]['comidas'] += 1;
                        $detalleConsumosPorDia[$diaSemana]['total'] += 1;
                    }
                }
            }
        }
        
        // Guardar info de debug
        $debugInfo = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'usuario_buscado' => $miIdEmpleado . ' - ' . $miNombre,
            'filas_encontradas' => count($reporteData),
            'debug_data' => $debugData,
            'all_fechas' => $allFechas,
            'total_consumos_calculado' => $totalConsumos,
            'dias_con_consumo' => $diasConConsumo,
            'consumos_por_dia' => $consumosPorDia,
            'detalle_consumos_por_dia' => $detalleConsumosPorDia,
            'total_consumos_dias_habiles' => array_sum($consumosPorDia),
            'monto_calculo' => [
                'mis_consumos' => $misConsumos ?? 0,
                'mis_entradas' => $misEntradas ?? 0,
                'consumos_sin_entrada' => isset($misConsumos, $misEntradas) && $misConsumos > $misEntradas ? ($misConsumos - $misEntradas) : 0,
                'formula_usada' => isset($misConsumos, $misEntradas) ? 
                    ($misConsumos > $misEntradas ? 
                        "($misConsumos - $misEntradas) * 60 + $misEntradas * 30" : 
                        "$misConsumos * 30") : 
                    "Sin datos"
            ]
        ];
        
        sqlsrv_free_stmt($stmt);
    } else {
        $errorQuery = "Error en la consulta: " . print_r(sqlsrv_errors(), true);
    }
}

// Cerrar conexión
if (isset($conn)) {
    sqlsrv_close($conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Reporte Detallado - Comedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --navy-blue: #1e3a5c;
            --navy-dark: #0f1f38;
            --navy-light: #2d4a72;
            --gold: #d4af37;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
            --danger: #dc3545;
        }
        body {
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-blue) 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-custom {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .table-custom th {
            background: var(--navy-light);
            color: white;
            position: sticky;
            top: 0;
            font-size: 0.8rem;
            padding: 8px 4px;
            text-align: center;
        }
        .table-custom td {
            color: white;
            border-color: rgba(255, 255, 255, 0.1);
            padding: 8px 4px;
            text-align: center;
        }
        .btn-gold {
            background: var(--gold);
            border: none;
            color: var(--navy-dark);
            font-weight: bold;
        }
        .btn-gold:hover {
            background: #e6c86e;
            color: var(--navy-dark);
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--gold);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--gold);
        }
        .stat-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .user-header {
            background: linear-gradient(135deg, var(--gold), #b8941f);
            color: var(--navy-dark);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .consumo-cell {
            background: rgba(40, 167, 69, 0.3);
            font-weight: bold;
        }
        .sin-consumo-cell {
            background: rgba(255, 255, 255, 0.05);
        }
        .scrollable-table {
            max-height: 800px;
            overflow-y: auto;
            overflow-x: auto;
        }
        .info-usuario {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .badge-consumo {
            background: var(--success);
            color: white;
            font-size: 0.7rem;
        }
        .badge-total {
            background: var(--warning);
            color: black;
            font-size: 0.9rem;
        }
        .columna-fecha {
            min-width: 50px;
        }
        .columna-principal {
            min-width: 120px;
            position: sticky;
            left: 0;
            background: var(--navy-light);
            z-index: 2;
        }
        .table-responsive {
            font-size: 0.8rem;
        }
        .debug-panel {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid var(--warning);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .consumo-dia {
            background: rgba(40, 167, 69, 0.2);
            border-radius: 5px;
            padding: 10px;
            margin: 5px 0;
        }
        .monto-detalle {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        .costo-diferente {
            color: #ff6b6b;
            font-weight: bold;
        }
        .resumen-dia {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            margin: 4px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        .dias-habiles {
            font-size: 0.8rem;
            color: var(--gold);
            font-weight: bold;
        }
        .detalle-consumo {
            font-size: 0.75rem;
            color: #ccc;
            margin-top: 2px;
        }
        .badge-desayuno {
            background: #17a2b8;
            color: white;
            font-size: 0.65rem;
        }
        .badge-comida {
            background: #28a745;
            color: white;
            font-size: 0.65rem;
        }
        .multi-consumo {
            background: rgba(255, 193, 7, 0.3);
            border: 1px solid var(--warning);
        }
        .auto-login-notice {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--success);
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$usuarioAutenticado): ?>
            <!-- LOGIN -->
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="glass-card text-center">
                        <h1><i class="fas fa-utensils"></i> COMEDOR BACROCORP</h1>
                        <p class="lead">Mi Reporte Personal de Consumos</p>
                        
                        <?php if (!empty($user_name) && $user_name != 'Usuario'): ?>
                            <div class="auto-login-notice">
                                <i class="fas fa-info-circle"></i> 
                                Hola <strong><?php echo htmlspecialchars($user_name); ?></strong>. 
                                El sistema intentará encontrar tu información automáticamente.
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="login" value="1">
                            <div class="mb-3">
                                <label class="form-label">ID Empleado</label>
                                <input type="text" class="form-control" name="id_empleado" placeholder="ID Empleado" value="<?php echo htmlspecialchars($default_id_empleado); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre" placeholder="Nombre" value="<?php echo htmlspecialchars($default_nombre); ?>" required>
                            </div>
                            <?php if (isset($errorAuth)): ?>
                                <div class="alert alert-danger"><?php echo $errorAuth; ?></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-gold btn-lg w-100">
                                <i class="fas fa-chart-bar"></i> VER MI REPORTE
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- SISTEMA PRINCIPAL -->
            <div class="user-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?></h2>
                        <p class="mb-0">ID: <?php echo htmlspecialchars($usuarioData['Id_Empleado']); ?> | Área: <?php echo htmlspecialchars($user_area); ?></p>
                    </div>
                    <div>
                        <a href="?debug=1" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-bug"></i> Debug
                        </a>
                        <a href="?logout=1" class="btn btn-outline-dark">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </div>
                </div>
            </div>

            <!-- PANEL DE DEBUG (opcional) -->
            <?php if (isset($_GET['debug']) && !empty($debugInfo)): ?>
            <div class="debug-panel">
                <h4><i class="fas fa-bug"></i> Información de Diagnóstico</h4>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Período:</strong> <?php echo $debugInfo['fecha_inicio']; ?> a <?php echo $debugInfo['fecha_fin']; ?><br>
                        <strong>Usuario:</strong> <?php echo $debugInfo['usuario_buscado']; ?><br>
                        <strong>Filas encontradas:</strong> <?php echo $debugInfo['filas_encontradas']; ?><br>
                        <strong>Total consumos (calculado):</strong> <?php echo $debugInfo['total_consumos_calculado']; ?><br>
                        <strong>Días con consumo:</strong> <?php echo $debugInfo['dias_con_consumo']; ?><br>
                        <strong>Total consumos días hábiles:</strong> <?php echo $debugInfo['total_consumos_dias_habiles']; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Fechas detectadas:</strong> <?php echo count($debugInfo['all_fechas']); ?><br>
                        <strong>Cálculo Monto:</strong><br>
                        <small>
                            Mis Consumos: <?php echo $debugInfo['monto_calculo']['mis_consumos']; ?><br>
                            Mis Entradas: <?php echo $debugInfo['monto_calculo']['mis_entradas']; ?><br>
                            <?php if ($debugInfo['monto_calculo']['consumos_sin_entrada'] > 0): ?>
                                <span class="costo-diferente">Consumos sin entrada: <?php echo $debugInfo['monto_calculo']['consumos_sin_entrada']; ?> × $60</span><br>
                                Consumos con entrada: <?php echo $debugInfo['monto_calculo']['mis_entradas']; ?> × $30<br>
                                Fórmula: <?php echo $debugInfo['monto_calculo']['formula_usada']; ?>
                            <?php else: ?>
                                Fórmula: <?php echo $debugInfo['monto_calculo']['formula_usada']; ?>
                            <?php endif; ?>
                        </small>
                        <?php if (isset($debugInfo['detalle_consumos_por_dia'])): ?>
                        <br><strong>Detalle consumos por día:</strong><br>
                        <small>
                            <?php 
                            $diasEng = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                            $diasEsp = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
                            foreach ($diasEng as $index => $diaEng): 
                                $detalle = $debugInfo['detalle_consumos_por_dia'][$diaEng] ?? ['desayunos' => 0, 'comidas' => 0, 'total' => 0];
                            ?>
                                <?php echo $diasEsp[$index]; ?>: D=<?php echo $detalle['desayunos']; ?> C=<?php echo $detalle['comidas']; ?> T=<?php echo $detalle['total']; ?><br>
                            <?php endforeach; ?>
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- FILTROS -->
            <div class="glass-card">
                <h3><i class="fas fa-filter"></i> Filtros del Reporte</h3>
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label>Fecha Inicio:</label>
                        <input type="date" name="fechaInicio" class="form-control" value="<?php echo $fechaInicio; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Fecha Fin:</label>
                        <input type="date" name="fechaFin" class="form-control" value="<?php echo $fechaFin; ?>">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-gold w-100">
                            <i class="fas fa-sync-alt"></i> ACTUALIZAR
                        </button>
                    </div>
                </form>
            </div>

            <!-- RESUMEN PRINCIPAL -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalConsumos; ?></div>
                        <div>Total Consumos</div>
                        <div class="stat-subtitle"><?php echo $diasConConsumo; ?> días con consumo</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format($montoTotal, 0); ?></div>
                        <div>Monto Total</div>
                        <div class="stat-subtitle monto-detalle">
                            <?php 
                            $misConsumos = $miFilaData ? intval($miFilaData['TotalConsumos'] ?? $miFilaData['TotalConsumosCalculado'] ?? 0) : 0;
                            $misEntradas = $miFilaData ? intval($miFilaData['TotalEntradas'] ?? 0) : 0;
                            
                            if ($misConsumos > $misEntradas): 
                                $consumosSinEntrada = $misConsumos - $misEntradas;
                            ?>
                                <span class="costo-diferente"><?php echo $consumosSinEntrada; ?> × $60</span><br>
                                + <?php echo $misEntradas; ?> × $30
                            <?php else: ?>
                                <?php echo $misConsumos; ?> × $30
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalEntradas; ?></div>
                        <div>Registros Entrada</div>
                        <div class="stat-subtitle">Control de acceso</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($consumosDiarios); ?></div>
                        <div>Días Registrados</div>
                        <div class="stat-subtitle">En el período</div>
                    </div>
                </div>
            </div>

            <!-- INFORMACIÓN DEL USUARIO -->
            <div class="info-usuario">
                <h4><i class="fas fa-id-card"></i> Mi Información</h4>
                <div class="row">
                    <div class="col-md-3">
                        <strong>ID Empleado:</strong> <?php echo htmlspecialchars($miIdEmpleado); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Nombre:</strong> <?php echo htmlspecialchars($user_name); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Área:</strong> <?php echo htmlspecialchars($user_area); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> al <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
                    </div>
                </div>
            </div>

            <!-- VISTA DETALLADA CON TODAS LAS COLUMNAS DE DÍAS -->
            <div class="glass-card">
                <h3><i class="fas fa-calendar-day"></i> Mi Consumo Diario Detallado</h3>
                <p class="mb-3">Mostrando todos los días del período para: <strong><?php echo htmlspecialchars($user_name); ?></strong> (ID: <?php echo htmlspecialchars($miIdEmpleado); ?>)</p>
                
                <?php if (!empty($reporteData)): ?>
                    <div class="table-responsive">
                        <div class="scrollable-table">
                            <table class="table table-custom table-bordered">
                                <thead>
                                    <tr>
                                        <th class="columna-principal">ID Empleado</th>
                                        <th class="columna-principal">Nombre</th>
                                        <th class="columna-principal">Total Consumos</th>
                                        <th class="columna-principal">Total Entradas</th>
                                        <?php 
                                        // Obtener TODAS las columnas de fecha del período
                                        $fechasPeriodo = [];
                                        if (!empty($reporteData[0])) {
                                            foreach ($reporteData[0] as $campo => $valor) {
                                                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $campo) || 
                                                    preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $campo) ||
                                                    strpos($campo, '202') === 0) {
                                                    $fechasPeriodo[] = $campo;
                                                }
                                            }
                                        }
                                        
                                        // Ordenar fechas cronológicamente
                                        usort($fechasPeriodo, function($a, $b) {
                                            return strtotime($a) - strtotime($b);
                                        });
                                        
                                        foreach ($fechasPeriodo as $fecha): 
                                            $fechaFormateada = date('d/m', strtotime($fecha));
                                            $diaSemana = date('D', strtotime($fecha));
                                        ?>
                                            <th class="columna-fecha" title="<?php echo $fecha; ?>">
                                                <?php 
                                                echo '<small>' . $fechaFormateada . '</small><br>';
                                                echo '<small>' . $diaSemana . '</small>';
                                                ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reporteData as $fila): ?>
                                        <?php if ($fila['Id_Empleado'] == $miIdEmpleado || $fila['Nombre'] == $user_name): ?>
                                            <tr>
                                                <td class="columna-principal fw-bold text-warning">
                                                    <?php echo $fila['Id_Empleado']; ?>
                                                </td>
                                                <td class="columna-principal fw-bold">
                                                    <?php echo htmlspecialchars($user_name); ?>
                                                </td>
                                                <td class="columna-principal">
                                                    <span class="badge badge-total">
                                                        <?php echo $fila['TotalConsumos'] ?? $fila['TotalConsumosCalculado'] ?? 0; ?> consumos
                                                    </span>
                                                </td>
                                                <td class="columna-principal">
                                                    <span class="badge bg-info">
                                                        <?php echo $fila['TotalEntradas'] ?? 0; ?> entradas
                                                    </span>
                                                </td>
                                                <?php 
                                                foreach ($fechasPeriodo as $fecha): 
                                                    $valor = $fila[$fecha] ?? 0;
                                                    $tieneConsumo = false;
                                                    $cantidadConsumos = 0;
                                                    
                                                    // Detección ROBUSTA de consumo
                                                    if ($valor === 1 || $valor === '1' || $valor === true) {
                                                        $tieneConsumo = true;
                                                        $cantidadConsumos = 1;
                                                    } elseif (is_numeric($valor) && floatval($valor) > 0) {
                                                        $tieneConsumo = true;
                                                        $cantidadConsumos = intval($valor);
                                                    }
                                                ?>
                                                    <td class="<?php echo $tieneConsumo ? ($cantidadConsumos > 1 ? 'multi-consumo' : 'consumo-cell') : 'sin-consumo-cell'; ?>"
                                                        title="<?php 
                                                            if ($tieneConsumo) {
                                                                echo $cantidadConsumos > 1 ? 
                                                                    'Dos consumos registrados (Desayuno + Comida) - $60' : 
                                                                    'Un consumo registrado - $30';
                                                            } else {
                                                                echo 'Sin consumo este día';
                                                            }
                                                        ?>">
                                                        <?php if ($tieneConsumo): ?>
                                                            <span class="badge badge-consumo">
                                                                <i class="fas fa-utensils"></i> <?php echo $cantidadConsumos; ?>
                                                            </span>
                                                            <?php if ($cantidadConsumos > 1): ?>
                                                                <div class="detalle-consumo">
                                                                    <span class="badge badge-desayuno">D</span>
                                                                    <span class="badge badge-comida">C</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- LEYENDA Y RESUMEN DIARIO -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="bg-dark rounded p-3">
                                <h5><i class="fas fa-info-circle"></i> Leyenda</h5>
                                <p class="mb-1">
                                    <span class="badge badge-consumo me-2"><i class="fas fa-utensils"></i> 1</span> = Un consumo (Comida) - $30
                                </p>
                                <p class="mb-1">
                                    <span class="badge multi-consumo me-2"><i class="fas fa-utensils"></i> 2</span> = Dos consumos (Desayuno + Comida) - $60
                                </p>
                                <p class="mb-0">
                                    <span class="badge bg-secondary me-2">-</span> = Sin consumo ese día
                                </p>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <span class="badge badge-desayuno me-1">D</span> = Desayuno<br>
                                        <span class="badge badge-comida me-1">C</span> = Comida
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-dark rounded p-3">
                                <h5><i class="fas fa-chart-pie"></i> Resumen por Día de la Semana <span class="dias-habiles">(Días Hábitos)</span></h5>
                                <?php
                                $diasEsp = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
                                $diasEng = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                
                                for ($i = 0; $i < 5; $i++):
                                    $count = $consumosPorDia[$diasEng[$i]] ?? 0;
                                    $detalle = $detalleConsumosPorDia[$diasEng[$i]] ?? ['desayunos' => 0, 'comidas' => 0, 'total' => 0];
                                    $porcentaje = $totalConsumos > 0 ? round(($count / $totalConsumos) * 100, 1) : 0;
                                ?>
                                    <div class="resumen-dia <?php echo $detalle['desayunos'] > 0 ? 'multi-consumo' : ''; ?>">
                                        <div>
                                            <strong><?php echo $diasEsp[$i]; ?>:</strong>
                                            <?php if ($porcentaje > 0): ?>
                                                <small class="text-muted">(<?php echo $porcentaje; ?>%)</small>
                                            <?php endif; ?>
                                            <div class="detalle-consumo">
                                                <?php if ($detalle['desayunos'] > 0): ?>
                                                    <span class="badge badge-desayuno me-1"><?php echo $detalle['desayunos']; ?> desayuno(s)</span>
                                                <?php endif; ?>
                                                <span class="badge badge-comida"><?php echo $detalle['comidas']; ?> comida(s)</span>
                                            </div>
                                        </div>
                                        <span class="badge bg-success"><?php echo $count; ?> consumos</span>
                                    </div>
                                <?php endfor; ?>
                                
                                <!-- Total -->
                                <div class="resumen-dia mt-2" style="background: rgba(255, 193, 7, 0.3); border: 1px solid var(--warning);">
                                    <div>
                                        <strong>TOTAL:</strong>
                                        <div class="detalle-consumo">
                                            <?php 
                                            $totalDesayunos = array_sum(array_column($detalleConsumosPorDia, 'desayunos'));
                                            $totalComidas = array_sum(array_column($detalleConsumosPorDia, 'comidas'));
                                            ?>
                                            <span class="badge badge-desayuno me-1"><?php echo $totalDesayunos; ?> desayuno(s)</span>
                                            <span class="badge badge-comida"><?php echo $totalComidas; ?> comida(s)</span>
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark"><?php echo $totalConsumos; ?> consumos</span>
                                </div>
                                
                                <!-- Nota sobre días hábiles -->
                                <div class="mt-2 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Solo se consideran días hábiles (Lunes a Viernes)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i> No se encontraron datos para tu usuario en el período seleccionado.
                        <?php if (isset($errorQuery)): ?>
                            <br><small class="text-muted">Error: <?php echo $errorQuery; ?></small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>