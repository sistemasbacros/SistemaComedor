<?php
// ==================== CONFIGURACIÓN Y CONEXIÓN ====================
session_start();

require_once __DIR__ . '/config/database.php';

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// ==================== FUNCIONES DE CÁLCULO DE PRECIOS ====================
function calcularCostoConsumo($fecha, $tipoComida, $tieneEntrada, $year) {
    // Precios según el año y tipo de comida
    if ($year < 2026) {
        // Antes de 2026: todos los consumos a $30
        $precio = 30;
        $costoTotal = $tieneEntrada ? $precio : ($precio * 2);
        
        return [
            'costo_total' => $costoTotal,
            'precio_unitario' => $precio,
            'tiene_descuento' => $tieneEntrada,
            'aplica_doble' => !$tieneEntrada,
            'year' => $year
        ];
    } else {
        // Desde 2026: diferentes precios por tipo de comida
        if ($tipoComida == 'Desayuno') {
            $precio = 35;
        } elseif ($tipoComida == 'Comida') {
            $precio = 45;
        } elseif ($tipoComida == 'Ambos') {
            $precio = 80; // 35 + 45
        } else {
            $precio = 0;
        }
        
        $costoTotal = $tieneEntrada ? $precio : ($precio * 2);
        
        return [
            'costo_total' => $costoTotal,
            'precio_unitario' => $precio,
            'tiene_descuento' => $tieneEntrada,
            'aplica_doble' => !$tieneEntrada,
            'year' => $year
        ];
    }
}

// Función para calcular monto de entradas según rangos de precios
function calcularMontoEntradas($fecha, $tipoComida, $year) {
    if ($year < 2026) {
        if ($tipoComida == 'Ambos') {
            return 60; // 30 + 30
        } else {
            return 30;
        }
    } else {
        if ($tipoComida == 'Desayuno') {
            return 35;
        } elseif ($tipoComida == 'Comida') {
            return 45;
        } elseif ($tipoComida == 'Ambos') {
            return 80; // 35 + 45
        } else {
            return 0;
        }
    }
}

// ==================== VERIFICAR AUTENTICACIÓN ====================
$conn = getComedorConnection();
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
        $user_area = $usuarioFromDB['Area'];
        
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
$montoEntradas = 0;
$diasConConsumo = 0;
$debugInfo = [];
$miFilaData = null;
$consumosPorDia = [];
$detalleConsumosPorDia = [];
$detalleMontosPorDia = [];
$tiposComidaPorFecha = [];
$consumosAgendadosPorFecha = [];

if ($usuarioAutenticado && $conn) {
    $miIdEmpleado = $usuarioData['Id_Empleado'];
    $miNombre = $usuarioData['Nombre'];
    
    $fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');
    
    $consumosPorDia = [
        'Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0,
        'Thursday' => 0, 'Friday' => 0
    ];
    
    $detalleConsumosPorDia = [
        'Monday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0, 'monto' => 0],
        'Tuesday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0, 'monto' => 0],
        'Wednesday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0, 'monto' => 0],
        'Thursday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0, 'monto' => 0],
        'Friday' => ['desayunos' => 0, 'comidas' => 0, 'total' => 0, 'monto' => 0]
    ];
    
    // 1. PRIMERO: Obtener los tipos de comida desde la tabla Entradas (lo que realmente consumió)
    $sqlEntradas = "
    SELECT 
        CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha,
        CASE
            WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 'Desayuno'
            ELSE 'Comida'
        END AS Tipo_Comida
    FROM (
        SELECT *, 
            NE_EXTRAIDO1 =
                CASE 
                    WHEN Nombre LIKE '%dionisio%' THEN '46'
                    WHEN Nombre LIKE '%esquivel edgar%' OR Nombre LIKE '%edgar gutie%' OR Nombre LIKE '%GUTIERREZ EZQUIVEL%' THEN '18'
                    WHEN Nombre LIKE '%Luna castro%' THEN '1'
                    ELSE NE_Extraido
                END
        FROM (
            SELECT *,
                LTRIM(RTRIM(
                    CASE
                        WHEN CHARINDEX('N.E:', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('N.E:', Nombre) + LEN('N.E:'), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('N.E:', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('N.E:', Nombre)) - (CHARINDEX('N.E:', Nombre) + LEN('N.E:')) 
                                    ELSE LEN(Nombre)
                                END
                            )
                        WHEN CHARINDEX('NE: ', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('NE: ', Nombre) + LEN('NE: '), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE: ', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE: ', Nombre)) - (CHARINDEX('NE: ', Nombre) + LEN('NE: '))
                                    ELSE LEN(Nombre)
                                END
                            )
                        WHEN CHARINDEX('NE:', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('NE:', Nombre) + LEN('NE:'), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE:', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE:', Nombre)) - (CHARINDEX('NE:', Nombre) + LEN('NE:')) 
                                    ELSE LEN(Nombre)
                                END
                            )
                        WHEN CHARINDEX('ID:NE0', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('ID:NE0', Nombre) + LEN('ID:NE0'), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('ID:NE0', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('ID:NE0', Nombre)) - (CHARINDEX('ID:NE0', Nombre) + LEN('ID:NE0')) 
                                    ELSE LEN(Nombre)
                                END
                            )
                        ELSE NULL
                    END
                )) AS NE_Extraido,
                CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha1
            FROM Entradas
            WHERE Nombre LIKE '%N.E:%' OR Nombre LIKE '%NE:%' OR Nombre LIKE '%ID:NE0%'
        ) AS a
    ) AS b
    LEFT JOIN (SELECT Id_Empleado, Nombre FROM ConPed) AS c ON b.NE_EXTRAIDO1 = c.Id_Empleado
    WHERE NE_EXTRAIDO1 = ?
    AND LTRIM(RTRIM(Fecha1)) >= ? 
    AND LTRIM(RTRIM(Fecha1)) <= ?
    ORDER BY Fecha, Tipo_Comida
    ";
    
    $paramsEntradas = array($miIdEmpleado, $fechaInicio, $fechaFin);
    $stmtEntradas = sqlsrv_query($conn, $sqlEntradas, $paramsEntradas);
    
    if ($stmtEntradas) {
        while ($row = sqlsrv_fetch_array($stmtEntradas, SQLSRV_FETCH_ASSOC)) {
            $fecha = $row['Fecha'];
            $tipoComida = $row['Tipo_Comida'];
            
            if (!isset($tiposComidaPorFecha[$fecha])) {
                $tiposComidaPorFecha[$fecha] = [];
            }
            $tiposComidaPorFecha[$fecha][] = $tipoComida;
        }
        sqlsrv_free_stmt($stmtEntradas);
    }
    
    // 2. SEGUNDO: Obtener los consumos agendados desde PedidosComida (lo que tenía programado)
    $sqlAgendados = "
    SELECT 
        Fecha_Dia AS Fecha,
        Tipo_Comida,
        Costo
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
    WHERE Id_Empleado = ?
    AND Fecha_Dia >= ?
    AND Fecha_Dia <= ?
    AND Tipo_Comida != ''
    ORDER BY Fecha_Dia
    ";
    
    $paramsAgendados = array($miIdEmpleado, $fechaInicio, $fechaFin);
    $stmtAgendados = sqlsrv_query($conn, $sqlAgendados, $paramsAgendados);
    
    if ($stmtAgendados) {
        while ($row = sqlsrv_fetch_array($stmtAgendados, SQLSRV_FETCH_ASSOC)) {
            $fecha = $row['Fecha']->format('Y-m-d');
            $tipoComida = $row['Tipo_Comida'];
            
            if (!isset($consumosAgendadosPorFecha[$fecha])) {
                $consumosAgendadosPorFecha[$fecha] = [];
            }
            $consumosAgendadosPorFecha[$fecha][] = $tipoComida;
        }
        sqlsrv_free_stmt($stmtAgendados);
    }
    
    // 3. OBTENER TODAS LAS FECHAS CON ENTRADAS (aunque no tengan agendado)
    $sqlFechasConEntrada = "
    SELECT DISTINCT CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha
    FROM (
        SELECT *, 
            NE_EXTRAIDO1 =
                CASE 
                    WHEN Nombre LIKE '%dionisio%' THEN '46'
                    WHEN Nombre LIKE '%esquivel edgar%' OR Nombre LIKE '%edgar gutie%' OR Nombre LIKE '%GUTIERREZ EZQUIVEL%' THEN '18'
                    WHEN Nombre LIKE '%Luna castro%' THEN '1'
                    ELSE NE_Extraido
                END
        FROM (
            SELECT *,
                LTRIM(RTRIM(
                    CASE
                        WHEN CHARINDEX('N.E:', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('N.E:', Nombre) + LEN('N.E:'), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('N.E:', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('N.E:', Nombre)) - (CHARINDEX('N.E:', Nombre) + LEN('N.E:')) 
                                    ELSE LEN(Nombre)
                                END
                            )
                        WHEN CHARINDEX('NE: ', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('NE: ', Nombre) + LEN('NE: '), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE: ', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE: ', Nombre)) - (CHARINDEX('NE: ', Nombre) + LEN('NE: '))
                                    ELSE LEN(Nombre)
                                END
                            )
                        WHEN CHARINDEX('NE:', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('NE:', Nombre) + LEN('NE:'), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE:', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('NE:', Nombre)) - (CHARINDEX('NE:', Nombre) + LEN('NE:')) 
                                    ELSE LEN(Nombre)
                                END
                            )
                        WHEN CHARINDEX('ID:NE0', Nombre) > 0 THEN
                            SUBSTRING(
                                Nombre,
                                CHARINDEX('ID:NE0', Nombre) + LEN('ID:NE0'), 
                                CASE
                                    WHEN CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('ID:NE0', Nombre)) > 0 THEN
                                        CHARINDEX('DEPARTAMENTO', Nombre, CHARINDEX('ID:NE0', Nombre)) - (CHARINDEX('ID:NE0', Nombre) + LEN('ID:NE0')) 
                                    ELSE LEN(Nombre)
                                END
                            )
                        ELSE NULL
                    END
                )) AS NE_Extraido,
                CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha1
            FROM Entradas
            WHERE Nombre LIKE '%N.E:%' OR Nombre LIKE '%NE:%' OR Nombre LIKE '%ID:NE0%'
        ) AS a
    ) AS b
    LEFT JOIN (SELECT Id_Empleado, Nombre FROM ConPed) AS c ON b.NE_EXTRAIDO1 = c.Id_Empleado
    WHERE NE_EXTRAIDO1 = ?
    AND LTRIM(RTRIM(Fecha1)) >= ? 
    AND LTRIM(RTRIM(Fecha1)) <= ?
    ORDER BY Fecha
    ";
    
    $paramsFechasEntrada = array($miIdEmpleado, $fechaInicio, $fechaFin);
    $stmtFechasEntrada = sqlsrv_query($conn, $sqlFechasConEntrada, $paramsFechasEntrada);
    
    $fechasConEntrada = [];
    if ($stmtFechasEntrada) {
        while ($row = sqlsrv_fetch_array($stmtFechasEntrada, SQLSRV_FETCH_ASSOC)) {
            $fecha = $row['Fecha'];
            $fechasConEntrada[] = $fecha;
        }
        sqlsrv_free_stmt($stmtFechasEntrada);
    }
    
    // 4. COMBINAR TODAS LAS FECHAS: las que tienen agendado + las que tienen entrada
    $todasFechas = array_unique(array_merge(
        array_keys($consumosAgendadosPorFecha),
        $fechasConEntrada
    ));
    
    // Ordenar fechas
    sort($todasFechas);
    
    // Procesar cada fecha para determinar consumo
    foreach ($todasFechas as $fecha) {
        $year = date('Y', strtotime($fecha));
        $diaSemanaIngles = date('l', strtotime($fecha));
        
        // Verificar si tenía agendado consumir este día
        $teniaAgendado = isset($consumosAgendadosPorFecha[$fecha]) && !empty($consumosAgendadosPorFecha[$fecha]);
        
        // Verificar qué tipo(s) de comida tomó
        $tiposTomados = $tiposComidaPorFecha[$fecha] ?? [];
        $tomoDesayuno = in_array('Desayuno', $tiposTomados);
        $tomoComida = in_array('Comida', $tiposTomados);
        $tomoAlgo = $tomoDesayuno || $tomoComida;
        
        // Determinar tipo de comida para cálculos
        $tipoComidaCalculo = '';
        $cantidadConsumos = 0;
        
        if ($tomoDesayuno && $tomoComida) {
            $tipoComidaCalculo = 'Ambos';
            $cantidadConsumos = 2;
        } elseif ($tomoDesayuno) {
            $tipoComidaCalculo = 'Desayuno';
            $cantidadConsumos = 1;
        } elseif ($tomoComida) {
            $tipoComidaCalculo = 'Comida';
            $cantidadConsumos = 1;
        } else {
            $tipoComidaCalculo = 'Ninguno';
            $cantidadConsumos = 0;
        }
        
        // Si tomó algo, siempre se registra en el reporte (aunque no tenga agendado)
        if ($tomoAlgo) {
            $tieneEntrada = true; // Si tomó, significa que tuvo entrada
            
            $costoCalculado = calcularCostoConsumo($fecha, $tipoComidaCalculo, $tieneEntrada, $year);
            $montoEntradaCalculado = calcularMontoEntradas($fecha, $tipoComidaCalculo, $year);
            
            // Actualizar estadísticas
            if (isset($consumosPorDia[$diaSemanaIngles])) {
                $consumosPorDia[$diaSemanaIngles] += $cantidadConsumos;
                $detalleConsumosPorDia[$diaSemanaIngles]['monto'] += $costoCalculado['costo_total'];
                
                if ($tomoDesayuno) {
                    $detalleConsumosPorDia[$diaSemanaIngles]['desayunos'] += 1;
                    $detalleConsumosPorDia[$diaSemanaIngles]['total'] += 1;
                }
                if ($tomoComida) {
                    $detalleConsumosPorDia[$diaSemanaIngles]['comidas'] += 1;
                    $detalleConsumosPorDia[$diaSemanaIngles]['total'] += 1;
                }
            }
            
            $consumosDiarios[$fecha] = [
                'fecha' => $fecha,
                'dia_semana' => $diaSemanaIngles,
                'consumo' => true,
                'tenia_agendado' => $teniaAgendado,
                'tomo_algo' => $tomoAlgo,
                'cantidad' => $cantidadConsumos,
                'tomo_desayuno' => $tomoDesayuno,
                'tomo_comida' => $tomoComida,
                'tipos_tomados' => $tiposTomados,
                'tipos_agendados' => $consumosAgendadosPorFecha[$fecha] ?? [],
                'tipo_calculo' => $tipoComidaCalculo,
                'costo_calculado' => $costoCalculado,
                'tiene_entrada' => $tieneEntrada,
                'monto' => $costoCalculado['costo_total'],
                'monto_entrada' => $montoEntradaCalculado,
                'year' => $year
            ];
            
            $totalConsumos += $cantidadConsumos;
            $diasConConsumo++;
            
        } elseif ($teniaAgendado && !$tomoAlgo) {
            // Tenía agendado pero NO tomó - esto es importante para mostrar
            $consumosDiarios[$fecha] = [
                'fecha' => $fecha,
                'dia_semana' => $diaSemanaIngles,
                'consumo' => false,
                'tenia_agendado' => $teniaAgendado,
                'tomo_algo' => $tomoAlgo,
                'cantidad' => 0,
                'tomo_desayuno' => false,
                'tomo_comida' => false,
                'tipos_tomados' => [],
                'tipos_agendados' => $consumosAgendadosPorFecha[$fecha] ?? [],
                'tipo_calculo' => 'Ninguno',
                'costo_calculado' => null,
                'tiene_entrada' => false,
                'monto' => 0,
                'monto_entrada' => 0,
                'year' => $year
            ];
        } elseif (!$teniaAgendado && !$tomoAlgo) {
            // No tenía agendado y no tomó - esto también se debe mostrar
            $consumosDiarios[$fecha] = [
                'fecha' => $fecha,
                'dia_semana' => $diaSemanaIngles,
                'consumo' => false,
                'tenia_agendado' => $teniaAgendado,
                'tomo_algo' => $tomoAlgo,
                'cantidad' => 0,
                'tomo_desayuno' => false,
                'tomo_comida' => false,
                'tipos_tomados' => [],
                'tipos_agendados' => $consumosAgendadosPorFecha[$fecha] ?? [],
                'tipo_calculo' => 'Ninguno',
                'costo_calculado' => null,
                'tiene_entrada' => false,
                'monto' => 0,
                'monto_entrada' => 0,
                'year' => $year
            ];
        }
        
        // Contar entradas (días donde tomó algo)
        if ($tomoAlgo) {
            $totalEntradas++;
        }
    }
    
    // Calcular montos totales
    $montoTotal = 0;
    $montoEntradas = 0;
    
    foreach ($consumosDiarios as $consumoDia) {
        if ($consumoDia['consumo']) {
            $montoTotal += $consumoDia['monto'];
            $montoEntradas += $consumoDia['monto_entrada'];
        }
    }
    
    $debugInfo = [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'usuario' => $miIdEmpleado . ' - ' . $miNombre,
        'total_consumos' => $totalConsumos,
        'total_entradas' => $totalEntradas,
        'monto_total' => $montoTotal,
        'monto_entradas' => $montoEntradas,
        'dias_con_consumo' => $diasConConsumo,
        'tipos_comida_por_fecha' => $tiposComidaPorFecha,
        'consumos_agendados_por_fecha' => $consumosAgendadosPorFecha,
        'fechas_con_entrada' => $fechasConEntrada,
        'todas_fechas' => $todasFechas
    ];
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
    <title>Reporte de Consumos - Comedor</title>
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
            --dark-red: #8b0000;
            --purple: #6f42c1;
            --orange: #fd7e14;
        }
        
        body {
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy-blue) 100%);
            color: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 20px;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .user-header {
            background: linear-gradient(135deg, var(--gold), #b8941f);
            color: var(--navy-dark);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border-left: 4px solid var(--gold);
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--gold);
        }
        
        .stat-subtitle {
            font-size: 0.8rem;
            opacity: 0.8;
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
        
        .table-custom {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            color: white;
            margin-bottom: 0;
            font-size: 0.85rem;
        }
        
        .table-custom th {
            background: var(--navy-light);
            color: white;
            position: sticky;
            top: 0;
            padding: 10px 8px;
            text-align: center;
            border: none;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .table-custom td {
            color: white;
            border-color: rgba(255, 255, 255, 0.1);
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
        }
        
        .table-responsive-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            padding: 5px;
        }
        
        .consumo-cell {
            background: rgba(40, 167, 69, 0.3);
            font-weight: bold;
        }
        
        .desayuno-cell {
            background: rgba(23, 162, 184, 0.3);
            font-weight: bold;
        }
        
        .comida-cell {
            background: rgba(40, 167, 69, 0.3);
            font-weight: bold;
        }
        
        .ambos-cell {
            background: rgba(255, 193, 7, 0.3);
            font-weight: bold;
        }
        
        .sin-consumo-cell {
            background: rgba(220, 53, 69, 0.3);
            border: 1px solid var(--danger);
        }
        
        .sin-consumo-cell:hover {
            background: rgba(220, 53, 69, 0.4);
        }
        
        .agendado-no-tomado {
            background: rgba(111, 66, 193, 0.3);
            border: 1px solid var(--purple);
        }
        
        .sin-entrada {
            background: rgba(139, 0, 0, 0.3);
            border: 1px solid var(--dark-red);
        }
        
        .consumo-sin-agendado {
            background: rgba(253, 126, 20, 0.3);
            border: 1px solid var(--orange);
        }
        
        .badge-consumo {
            background: var(--success);
            color: white;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 10px;
        }
        
        .badge-desayuno {
            background: var(--info);
            color: white;
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 8px;
        }
        
        .badge-comida {
            background: var(--success);
            color: white;
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 8px;
        }
        
        .badge-doble {
            background: var(--danger);
            color: white;
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 8px;
        }
        
        .badge-total {
            background: var(--warning);
            color: var(--navy-dark);
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 10px;
        }
        
        .badge-agendado {
            background: var(--purple);
            color: white;
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 8px;
        }
        
        .badge-sin-agendado {
            background: var(--orange);
            color: white;
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 8px;
        }
        
        .resumen-dia {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            margin: 5px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
        
        .dias-habiles {
            font-size: 0.8rem;
            color: var(--gold);
            font-weight: bold;
        }
        
        .detalle-consumo {
            font-size: 0.75rem;
            color: #ccc;
            margin-top: 3px;
        }
        
        .debug-panel {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid var(--warning);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .auto-login-notice {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--success);
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        
        .monto-detalle {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        .columna-fecha {
            min-width: 50px;
        }
        
        .periodo-2025 {
            border-left: 3px solid var(--info);
        }
        
        .periodo-2026 {
            border-left: 3px solid var(--warning);
        }
        
        .user-info-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .monto-entradas {
            font-weight: bold;
            color: var(--info);
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 10px;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .glass-card {
                padding: 15px;
            }
            
            .table-custom {
                font-size: 0.8rem;
            }
            
            .table-custom th,
            .table-custom td {
                padding: 6px 4px;
            }
            
            .badge-consumo {
                font-size: 0.7rem;
                padding: 3px 6px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .stat-number {
                font-size: 1.3rem;
            }
            
            .user-header h2 {
                font-size: 1.3rem;
            }
            
            .resumen-dia {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .resumen-dia > div:last-child {
                align-self: flex-end;
            }
        }
        
        .price-highlight {
            font-weight: bold;
            color: var(--gold);
        }
        
        .scroll-hint {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .no-consumo-text {
            color: var(--danger);
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .agendado-text {
            color: var(--purple);
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .sin-agendado-text {
            color: var(--orange);
            font-weight: bold;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php if (!$usuarioAutenticado): ?>
            <!-- PANTALLA DE LOGIN -->
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="glass-card">
                        <div class="text-center mb-4">
                            <h1 class="mb-3"><i class="fas fa-utensils"></i> COMEDOR BACROCORP</h1>
                            <p class="lead">Reporte Personal de Consumos</p>
                        </div>
                        
                        <?php if (!empty($user_name) && $user_name != 'Usuario'): ?>
                            <div class="auto-login-notice">
                                <i class="fas fa-info-circle"></i> 
                                Hola <strong><?php echo htmlspecialchars($user_name); ?></strong>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="login" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">ID Empleado</label>
                                <input type="text" class="form-control" name="id_empleado" 
                                       value="<?php echo htmlspecialchars($default_id_empleado); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" name="nombre" 
                                       value="<?php echo htmlspecialchars($default_nombre); ?>" 
                                       required>
                            </div>
                            
                            <?php if (isset($errorAuth)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $errorAuth; ?>
                                </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-gold btn-lg w-100">
                                <i class="fas fa-chart-bar"></i> VER REPORTE
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- HEADER DEL USUARIO -->
            <div class="user-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="h4 mb-1">
                            <i class="fas fa-user"></i> 
                            ID: <?php echo htmlspecialchars($usuarioData['Id_Empleado']); ?> - 
                            <?php echo htmlspecialchars($user_name); ?>
                        </h2>
                        <p class="mb-0">
                            Área: <?php echo htmlspecialchars($user_area); ?>
                        </p>
                    </div>
                    <div class="col-md-4 mt-2 mt-md-0">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="?debug=1" class="btn btn-outline-dark btn-sm">
                                <i class="fas fa-bug"></i>
                            </a>
                            <a href="?logout=1" class="btn btn-outline-dark">
                                <i class="fas fa-sign-out-alt"></i> Salir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['debug']) && !empty($debugInfo)): ?>
            <div class="debug-panel">
                <h5 class="mb-3"><i class="fas fa-bug"></i> Información de Diagnóstico</h5>
                <div class="row">
                    <div class="col-6">
                        <strong>Período:</strong><br>
                        <?php echo $debugInfo['fecha_inicio']; ?> a <?php echo $debugInfo['fecha_fin']; ?>
                    </div>
                    <div class="col-6">
                        <strong>Usuario:</strong><br>
                        <?php echo $debugInfo['usuario']; ?>
                    </div>
                    <div class="col-12 mt-3">
                        <strong>Fechas con entrada detectadas:</strong><br>
                        <pre style="color: white; font-size: 0.8rem; margin: 0; max-height: 200px; overflow: auto;">
<?php 
if (!empty($debugInfo['fechas_con_entrada'])) {
    foreach ($debugInfo['fechas_con_entrada'] as $fecha) {
        echo htmlspecialchars($fecha) . "\n";
    }
} else {
    echo "No se encontraron fechas con entrada.";
}
?>
                        </pre>
                    </div>
                    <div class="col-12 mt-3">
                        <strong>Tipos de comida detectados (Entradas):</strong><br>
                        <pre style="color: white; font-size: 0.8rem; margin: 0; max-height: 200px; overflow: auto;">
<?php 
if (!empty($debugInfo['tipos_comida_por_fecha'])) {
    foreach ($debugInfo['tipos_comida_por_fecha'] as $fecha => $tipos) {
        echo htmlspecialchars($fecha) . ": " . implode(", ", $tipos) . "\n";
    }
} else {
    echo "No se encontraron registros de tipos de comida en Entradas.";
}
?>
                        </pre>
                    </div>
                    <div class="col-12 mt-3">
                        <strong>Consumos agendados (PedidosComida):</strong><br>
                        <pre style="color: white; font-size: 0.8rem; margin: 0; max-height: 200px; overflow: auto;">
<?php 
if (!empty($debugInfo['consumos_agendados_por_fecha'])) {
    foreach ($debugInfo['consumos_agendados_por_fecha'] as $fecha => $tipos) {
        echo htmlspecialchars($fecha) . ": " . implode(", ", $tipos) . "\n";
    }
} else {
    echo "No se encontraron consumos agendados en PedidosComida.";
}
?>
                        </pre>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- FILTROS -->
            <div class="glass-card">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Fecha Inicio:</label>
                        <input type="date" name="fechaInicio" class="form-control" 
                               value="<?php echo $fechaInicio; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Fin:</label>
                        <input type="date" name="fechaFin" class="form-control" 
                               value="<?php echo $fechaFin; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-gold w-100">
                            <i class="fas fa-sync-alt"></i> ACTUALIZAR
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- RESUMEN PRINCIPAL -->
            <div class="row mb-3">
                <div class="col-6 col-md-3 mb-2">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalConsumos; ?></div>
                        <div>Consumos Tomados</div>
                        <div class="stat-subtitle"><?php echo $diasConConsumo; ?> días con consumo</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format($montoTotal, 0); ?></div>
                        <div>Monto Total</div>
                        <div class="stat-subtitle monto-detalle">
                            <?php 
                            $antes2026 = 0;
                            $desde2026 = 0;
                            foreach ($consumosDiarios as $consumo) {
                                if ($consumo['consumo']) {
                                    if ($consumo['year'] < 2026) {
                                        $antes2026 += $consumo['monto'];
                                    } else {
                                        $desde2026 += $consumo['monto'];
                                    }
                                }
                            }
                            if ($antes2026 > 0): ?>
                                <span class="d-block">2025: $<?php echo number_format($antes2026, 0); ?></span>
                            <?php endif; ?>
                            <?php if ($desde2026 > 0): ?>
                                <span class="d-block">2026+: $<?php echo number_format($desde2026, 0); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="stat-card">
                        <div class="stat-number">$<?php echo number_format($montoEntradas, 0); ?></div>
                        <div>Monto Entradas</div>
                        <div class="stat-subtitle">Con descuento aplicado</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $totalEntradas; ?></div>
                        <div>Días con Entrada</div>
                        <div class="stat-subtitle">Registradas en sistema</div>
                    </div>
                </div>
            </div>
            
            <!-- POLÍTICA DE PRECIOS -->
            <div class="glass-card mb-3" style="background: rgba(255, 193, 7, 0.1);">
                <div class="row">
                    <div class="col-md-8">
                        <strong><i class="fas fa-money-bill-wave"></i> Política de Precios y Colores:</strong><br>
                        <small>
                            <strong>Antes 2026:</strong> Todos los consumos: $30<br>
                            <strong>2026+:</strong> Desayuno: $35, Comida: $45<br>
                            <strong>Colores:</strong> 
                            <span class="badge-desayuno me-1">Desayuno</span>
                            <span class="badge-comida me-1">Comida</span>
                            <span class="badge bg-warning me-1">Ambos</span>
                            <span class="badge-agendado me-1">Agendado No Tomado</span>
                            <span class="badge-sin-agendado me-1">Consumo Sin Agendado</span><br>
                            <strong>Rojo:</strong> Día sin consumo agendado y sin entrada
                        </small>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <small class="text-muted">
                            Período: <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> - <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- REPORTE DETALLADO -->
            <div class="glass-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Reporte Diario</h5>
                    <div class="position-relative">
                        <div class="user-info-badge">
                            <span class="fw-bold">ID: <?php echo htmlspecialchars($miIdEmpleado); ?></span>
                            <span class="text-warning"><?php echo htmlspecialchars($user_name); ?></span>
                        </div>
                        <div class="scroll-hint d-none d-md-block">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($consumosDiarios)): ?>
                    <?php 
                    // Ordenar las fechas
                    ksort($consumosDiarios);
                    $fechasPeriodo = array_keys($consumosDiarios);
                    ?>
                    
                    <div class="table-responsive-container position-relative">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Consumos</th>
                                    <th>Entradas</th>
                                    <th>Monto Entradas</th>
                                    <?php foreach ($fechasPeriodo as $fecha): 
                                        $fechaFormateada = date('d/m', strtotime($fecha));
                                        $diaSemana = date('D', strtotime($fecha));
                                        $year = date('Y', strtotime($fecha));
                                    ?>
                                        <th class="text-center columna-fecha" 
                                            title="Año: <?php echo $year; ?> - <?php echo date('d/m/Y', strtotime($fecha)); ?>">
                                            <div class="small">
                                                <?php echo $fechaFormateada; ?><br>
                                                <span class="badge <?php echo $year < 2026 ? 'bg-info' : 'bg-warning'; ?>" style="font-size: 0.65rem;">
                                                    <?php echo substr($diaSemana, 0, 1); ?>
                                                </span>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-total">
                                            <?php echo $totalConsumos; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?php echo $totalEntradas; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">
                                            $<?php echo number_format($montoEntradas, 0); ?>
                                        </span>
                                    </td>
                                    <?php foreach ($consumosDiarios as $fecha => $consumoDia): 
                                        $tieneConsumo = $consumoDia['consumo'];
                                        $teniaAgendado = $consumoDia['tenia_agendado'];
                                        $tomoAlgo = $consumoDia['tomo_algo'];
                                        $tomoDesayuno = $consumoDia['tomo_desayuno'];
                                        $tomoComida = $consumoDia['tomo_comida'];
                                        $cantidadConsumos = $consumoDia['cantidad'];
                                        $year = $consumoDia['year'];
                                        $tieneEntrada = $consumoDia['tiene_entrada'];
                                        
                                        // Determinar clase CSS - CORRECCIÓN AQUÍ
                                        $claseCelda = '';
                                        if ($tomoAlgo) {
                                            if ($teniaAgendado) {
                                                if ($tomoDesayuno && $tomoComida) {
                                                    $claseCelda = 'ambos-cell';
                                                } elseif ($tomoDesayuno) {
                                                    $claseCelda = 'desayuno-cell';
                                                } elseif ($tomoComida) {
                                                    $claseCelda = 'comida-cell';
                                                }
                                                if (!$tieneEntrada) {
                                                    $claseCelda .= ' sin-entrada';
                                                }
                                            } else {
                                                // Consumió pero NO tenía agendado
                                                $claseCelda = 'consumo-sin-agendado';
                                            }
                                        } elseif ($teniaAgendado && !$tomoAlgo) {
                                            $claseCelda = 'agendado-no-tomado';
                                        } else {
                                            $claseCelda = 'sin-consumo-cell';
                                        }
                                        
                                        $claseCelda .= $year < 2026 ? ' periodo-2025' : ' periodo-2026';
                                    ?>
                                        <td class="text-center <?php echo $claseCelda; ?>" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top"
                                            title="<?php
                                                echo "<strong>" . date('d/m/Y', strtotime($fecha)) . "</strong><br>";
                                                echo "<strong>Estado:</strong> ";
                                                
                                                if ($tomoAlgo) {
                                                    if ($teniaAgendado) {
                                                        echo "<span class='text-success'>Consumió (Agendado)</span><br>";
                                                    } else {
                                                        echo "<span class='text-orange'><strong>Consumió SIN agendado</strong></span><br>";
                                                    }
                                                    echo "<strong>Tipo(s):</strong> ";
                                                    $tipos = [];
                                                    if ($tomoDesayuno) {
                                                        $precio = $year < 2026 ? "30" : "35";
                                                        $tipos[] = "Desayuno (\$" . $precio . ")";
                                                    }
                                                    if ($tomoComida) {
                                                        $precio = $year < 2026 ? "30" : "45";
                                                        $tipos[] = "Comida (\$" . $precio . ")";
                                                    }
                                                    echo implode(" + ", $tipos) . "<br>";
                                                    echo "<strong>Costo Total:</strong> $" . $consumoDia['monto'] . "<br>";
                                                    echo "<strong>Monto Entrada:</strong> $" . $consumoDia['monto_entrada'] . "<br>";
                                                    if (!$tieneEntrada) {
                                                        echo "<span class='text-danger'><strong>⚠ Sin entrada (Precio doble)</strong></span><br>";
                                                    }
                                                } elseif ($teniaAgendado && !$tomoAlgo) {
                                                    echo "<span class='text-purple'>Agendado pero NO consumió</span><br>";
                                                    if (!empty($consumoDia['tipos_agendados'])) {
                                                        echo "<strong>Agendado:</strong> " . implode(", ", $consumoDia['tipos_agendados']) . "<br>";
                                                    }
                                                } else {
                                                    echo "<span class='text-danger'>No tenía agendado y no consumió</span><br>";
                                                }
                                                echo "<strong>Año:</strong> " . $year;
                                            ?>">
                                            <?php if ($tomoAlgo): ?>
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="badge-consumo mb-1">
                                                        <?php echo $cantidadConsumos; ?>
                                                    </span>
                                                    <div class="d-flex gap-1">
                                                        <?php if ($tomoDesayuno): ?>
                                                            <span class="badge-desayuno">D</span>
                                                        <?php endif; ?>
                                                        <?php if ($tomoComida): ?>
                                                            <span class="badge-comida">C</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!$teniaAgendado): ?>
                                                        <span class="badge-sin-agendado mt-1">Sin Agend.</span>
                                                    <?php elseif (!$tieneEntrada): ?>
                                                        <span class="badge-doble mt-1">2×</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($teniaAgendado && !$tomoAlgo): ?>
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="agendado-text">Agend.</span>
                                                    <?php if (!empty($consumoDia['tipos_agendados'])): ?>
                                                        <div class="d-flex gap-1 mt-1">
                                                            <?php 
                                                            $tieneAgendadoDesayuno = in_array('Desayuno', $consumoDia['tipos_agendados']);
                                                            $tieneAgendadoComida = in_array('Comida', $consumoDia['tipos_agendados']);
                                                            ?>
                                                            <?php if ($tieneAgendadoDesayuno): ?>
                                                                <span class="badge-desayuno" style="opacity: 0.6;">D</span>
                                                            <?php endif; ?>
                                                            <?php if ($tieneAgendadoComida): ?>
                                                                <span class="badge-comida" style="opacity: 0.6;">C</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="no-consumo-text">No</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- RESUMEN Y LEYENDA -->
                    <div class="row mt-4">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="glass-card">
                                <h6 class="mb-3"><i class="fas fa-key"></i> Leyenda del Reporte</h6>
                                <div class="resumen-dia desayuno-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <span class="badge-desayuno">D</span>
                                        </div>
                                        <div>
                                            <strong>Desayuno</strong><br>
                                            <small class="detalle-consumo">Consumió desayuno (agendado)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia comida-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <span class="badge-comida">C</span>
                                        </div>
                                        <div>
                                            <strong>Comida</strong><br>
                                            <small class="detalle-consumo">Consumió comida (agendado)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia ambos-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <div class="d-flex gap-1">
                                                <span class="badge-desayuno">D</span>
                                                <span class="badge-comida">C</span>
                                            </div>
                                        </div>
                                        <div>
                                            <strong>Desayuno + Comida</strong><br>
                                            <small class="detalle-consumo">Consumió ambos (agendado)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia consumo-sin-agendado">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge-desayuno">D</span>
                                                <span class="badge-sin-agendado mt-1">Sin Agend.</span>
                                            </div>
                                        </div>
                                        <div>
                                            <strong>Consumo Sin Agendado</strong><br>
                                            <small class="detalle-consumo">Consumió pero NO tenía agendado</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia agendado-no-tomado">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <span class="agendado-text">Agend.</span>
                                        </div>
                                        <div>
                                            <strong>Agendado No Tomado</strong><br>
                                            <small class="detalle-consumo">Tenía agendado pero no consumió</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia sin-consumo-cell">
                                    <div class="d-flex align-items-center">
                                        <div class="sin-consumo-cell me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <span class="no-consumo-text">No</span>
                                        </div>
                                        <div>
                                            <strong>Sin consumo</strong><br>
                                            <small class="detalle-consumo">No tenía agendado y no consumió</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia sin-entrada">
                                    <div class="d-flex align-items-center">
                                        <div class="sin-entrada me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge-desayuno">D</span>
                                                <span class="badge-doble mt-1">2×</span>
                                            </div>
                                        </div>
                                        <div>
                                            <strong>Sin entrada</strong><br>
                                            <small class="detalle-consumo">Precio doble aplicado</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="resumen-dia">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex gap-2">
                                            <span class="badge bg-info">2025</span>
                                            <span class="badge bg-warning">2026</span>
                                        </div>
                                        <div>
                                            <strong>Periodo de precios</strong><br>
                                            <small class="detalle-consumo">Color indica año de aplicación</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="glass-card">
                                <h6 class="mb-3"><i class="fas fa-chart-pie"></i> Resumen por Día de la Semana <span class="dias-habiles">(Lunes a Viernes)</span></h6>
                                <?php
                                $diasEsp = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
                                $diasEng = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                $totalGeneral = 0;
                                
                                for ($i = 0; $i < 5; $i++):
                                    $count = $consumosPorDia[$diasEng[$i]] ?? 0;
                                    $detalle = $detalleConsumosPorDia[$diasEng[$i]] ?? ['desayunos' => 0, 'comidas' => 0, 'total' => 0, 'monto' => 0];
                                    $totalGeneral += $detalle['monto'];
                                    $porcentaje = $totalConsumos > 0 ? round(($count / $totalConsumos) * 100, 1) : 0;
                                ?>
                                    <div class="resumen-dia <?php echo ($detalle['desayunos'] > 0 && $detalle['comidas'] > 0) ? 'ambos-cell' : ($detalle['desayunos'] > 0 ? 'desayuno-cell' : 'comida-cell'); ?>">
                                        <div>
                                            <strong><?php echo $diasEsp[$i]; ?></strong>
                                            <?php if ($porcentaje > 0): ?>
                                                <small class="text-muted ms-2">(<?php echo $porcentaje; ?>%)</small>
                                            <?php endif; ?>
                                            <div class="detalle-consumo">
                                                <?php if ($detalle['desayunos'] > 0): ?>
                                                    <span class="badge-desayuno me-1"><?php echo $detalle['desayunos']; ?> desayuno(s)</span>
                                                <?php endif; ?>
                                                <span class="badge-comida"><?php echo $detalle['comidas']; ?> comida(s)</span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success"><?php echo $count; ?> consumos</span>
                                            <div class="small text-warning mt-1">$<?php echo number_format($detalle['monto'], 2); ?></div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                                
                                <!-- TOTAL -->
                                <div class="resumen-dia mt-3" style="background: rgba(255, 193, 7, 0.3); border: 1px solid var(--warning);">
                                    <div>
                                        <strong class="text-warning">TOTAL GENERAL</strong>
                                        <div class="detalle-consumo">
                                            <?php 
                                            $totalDesayunos = array_sum(array_column($detalleConsumosPorDia, 'desayunos'));
                                            $totalComidas = array_sum(array_column($detalleConsumosPorDia, 'comidas'));
                                            ?>
                                            <span class="badge-desayuno me-1"><?php echo $totalDesayunos; ?> desayuno(s)</span>
                                            <span class="badge-comida"><?php echo $totalComidas; ?> comida(s)</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-total"><?php echo $totalConsumos; ?> consumos</span>
                                        <div class="fw-bold text-warning mt-1">$<?php echo number_format($montoTotal, 2); ?></div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="resumen-dia" style="background: rgba(23, 162, 184, 0.2);">
                                        <div>
                                            <strong class="text-info">MONTO ENTRADAS</strong><br>
                                            <small class="detalle-consumo">Total con descuento aplicado</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-info">$<?php echo number_format($montoEntradas, 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Solo se consideran días hábiles (Lunes a Viernes)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-database fa-3x text-muted"></i>
                        </div>
                        <h5 class="mb-3">No se encontraron registros</h5>
                        <p class="text-muted mb-4">
                            No hay datos de consumo para el período seleccionado.
                            <?php if (isset($errorQuery)): ?>
                                <br><small class="text-danger">Error: <?php echo $errorQuery; ?></small>
                            <?php endif; ?>
                        </p>
                        <a href="?" class="btn btn-gold">
                            <i class="fas fa-redo me-2"></i> Volver al inicio
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- PIE DE PÁGINA -->
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-copyright me-1"></i>
                    Sistema de Reportes BACROCORP • <?php echo date('Y'); ?>
                </small>
            </div>
            
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar tooltips de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true,
                    trigger: 'hover',
                    placement: 'top'
                });
            });
        });
        
        // Mostrar/ocultar hint de scroll
        function toggleScrollHint() {
            const hint = document.querySelector('.scroll-hint');
            const tableContainer = document.querySelector('.table-responsive-container');
            
            if (hint && tableContainer) {
                if (tableContainer.scrollWidth > tableContainer.clientWidth) {
                    hint.style.display = 'flex';
                } else {
                    hint.style.display = 'none';
                }
            }
        }
        
        // Configurar al cargar
        window.addEventListener('load', function() {
            toggleScrollHint();
            
            // Ocultar hint después de 5 segundos
            setTimeout(function() {
                const hint = document.querySelector('.scroll-hint');
                if (hint) {
                    hint.style.display = 'none';
                }
            }, 5000);
        });
        
        // Reconfigurar al redimensionar
        window.addEventListener('resize', function() {
            toggleScrollHint();
        });
    </script>
</body>
</html>