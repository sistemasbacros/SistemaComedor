<?php
// ————— Conexión —————
$serverName = "DESAROLLO-BACRO\SQLEXPRESS";
$connectionOptions = [
    "Database" => "Comedor",
    "Uid" => "Larome03",
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("Conexión fallida: " . print_r(sqlsrv_errors(), true));
}

// ————— Recoger fechas —————
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d', strtotime('-2 weeks'));
$fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

if (!strtotime($fechaInicio) || !strtotime($fechaFin)) {
    die("Fechas no válidas.");
}

// ————— Verificar si es exportación a Excel —————
$exportarExcel = isset($_GET['exportar']) && $_GET['exportar'] === 'excel';

// ————— CONSULTA DE CANCELACIONES —————
$sqlCancelaciones = "
SELECT Nombre, COUNT(*) as Total 
FROM cancelaciones 
WHERE CONVERT(DATE, FECHA, 102) >= ? AND CONVERT(DATE, FECHA, 102) <= ?
GROUP BY Nombre
ORDER BY Total DESC
";

$paramsCancelaciones = array($fechaInicio, $fechaFin);
$stmtCancelaciones = sqlsrv_query($conn, $sqlCancelaciones, $paramsCancelaciones);

$cancelacionesData = [];
$totalCancelaciones = 0;

if ($stmtCancelaciones) {
    while ($row = sqlsrv_fetch_array($stmtCancelaciones, SQLSRV_FETCH_ASSOC)) {
        $cancelacionesData[] = $row;
        $totalCancelaciones += $row['Total'];
    }
} else {
    $cancelacionesData = [];
}

// ————— CONSULTA DE COMPLEMENTOS —————
$sqlComplementos = "
SELECT 
    Nombre_Limpio AS Nombre,
    ISNULL([CAFÉ O TÉ], 0)     AS [CAFÉ O TÉ],
    ISNULL([TORTILLAS], 0)     AS [TORTILLAS],
    ISNULL([AGUA], 0)          AS [AGUA],
    ISNULL([DESECHABLE], 0)    AS [DESECHABLE]
FROM (
    SELECT 
        CASE 
            WHEN Nombre LIKE '%se encuentra registrado para el%' 
                THEN LEFT(Nombre, CHARINDEX(' se encuentra registrado para el', Nombre) - 1)
            WHEN Nombre LIKE 'NOMBRE:%N.E%' 
                THEN LTRIM(RTRIM(
                    SUBSTRING(Nombre,
                        CHARINDEX('NOMBRE:', Nombre) + 7,
                        CHARINDEX('N.E', Nombre) - CHARINDEX('NOMBRE:', Nombre) - 7
                    )
                ))
            WHEN Nombre LIKE '%NOMBRE:%DEPARTAMENTO%' 
                THEN LTRIM(RTRIM(
                    SUBSTRING(Nombre,
                        CHARINDEX('NOMBRE:', Nombre) + 7,
                        CHARINDEX('DEPARTAMENTO', Nombre) - CHARINDEX('NOMBRE:', Nombre) - 7
                    )
                ))
            WHEN Nombre LIKE '%NOMBRE:%AREA%' 
                THEN LTRIM(RTRIM(
                    SUBSTRING(Nombre,
                        CHARINDEX('NOMBRE:', Nombre) + 7,
                        CHARINDEX('AREA', Nombre) - CHARINDEX('NOMBRE:', Nombre) - 7
                    )
                ))
            WHEN Nombre LIKE '%N.E%' AND CHARINDEX('N.E', Nombre) > 0
                THEN LTRIM(RTRIM(
                    LEFT(Nombre, CHARINDEX('N.E', Nombre) - 1)
                ))
            ELSE Nombre
        END AS Nombre_Limpio,
        Complemento
    FROM Complementos
    WHERE 
        Nombre IS NOT NULL 
        AND Nombre <> ''
        AND CONVERT(date, FECHA, 103) >= ? 
        AND CONVERT(date, FECHA, 103) <= ?
) AS source
PIVOT (
    COUNT(Complemento)
    FOR Complemento IN ([CAFÉ O TÉ], [TORTILLAS], [AGUA], [DESECHABLE])
) AS pvt
ORDER BY Nombre_Limpio;
";

$paramsComplementos = array($fechaInicio, $fechaFin);
$stmtComplementos = sqlsrv_query($conn, $sqlComplementos, $paramsComplementos);

$complementosData = [];
$totalComplementos = 0;
$resumenComplementos = [
    'CAFÉ O TÉ' => 0,
    'TORTILLAS' => 0,
    'AGUA' => 0,
    'DESECHABLE' => 0
];

if ($stmtComplementos) {
    while ($row = sqlsrv_fetch_array($stmtComplementos, SQLSRV_FETCH_ASSOC)) {
        $complementosData[] = $row;
        
        foreach ($resumenComplementos as $key => $value) {
            if (isset($row[$key])) {
                $resumenComplementos[$key] += intval($row[$key]);
                $totalComplementos += intval($row[$key]);
            }
        }
    }
} else {
    $complementosData = [];
}

// ————— CONSULTA PRINCIPAL —————
$sql = "
DECLARE @columns AS NVARCHAR(MAX), @sql AS NVARCHAR(MAX);
DECLARE @columnsSum NVARCHAR(MAX);

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

SELECT @columnsSum = STRING_AGG('ISNULL(' + TRIM(value) + ', 0)', ' + ')
FROM STRING_SPLIT(REPLACE(@columns, ' ', ''), ',');

SET @sql = N'
;WITH EntradasCTE AS (
    Select Empleado, Nombre as Nombre, Total from (
        Select No_Empleado as Empleado, Nombre, Count(*) as Total from (
            SELECT 
                Fecha1,
                NE_EXTRAIDO1 AS No_Empleado,
                c.Nombre,
                Tipo_Comida
            FROM (
                Select * from (
                    Select a1.Id_Empleado,a1.Nombre,a1.Area,a1.Hora_Entrada,a1.Fecha,a1.NE_Extraido,a1.Tipo_Comida,a1.Fecha1,
                    NE_EXTRAIDO1 = case when a1.NE_EXTRAIDO1 is NULL or a1.NE_EXTRAIDO1='''' or a1.NE_EXTRAIDO1=''NULL'' then a2.Id_Empleado else a1.NE_EXTRAIDO1 end 
                    from (
                        SELECT *, 
                            NE_EXTRAIDO1 = 
                                CASE 
                                    WHEN Nombre LIKE ''%dionisio%'' THEN ''46''
                                    WHEN Nombre LIKE ''%esquivel edgar%'' OR nombre LIKE ''%edgar gutie%'' OR nombre LIKE ''%GUTIERREZ EZQUIVEL%'' THEN ''18'' 
                                    WHEN Nombre LIKE ''%Luna castro%'' THEN ''1'' 
                                    ELSE NE_Extraido 
                                END
                        FROM (
                            SELECT *,
                                LTRIM(RTRIM(
                                    CASE 
                                        WHEN CHARINDEX(''N.E:'', nombre) > 0 THEN
                                            SUBSTRING(
                                                nombre,
                                                CHARINDEX(''N.E:'', nombre) + LEN(''N.E:''), 
                                                CASE 
                                                    WHEN CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''N.E:'', nombre)) > 0 THEN
                                                        CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''N.E:'', nombre)) - (CHARINDEX(''N.E:'', nombre) + LEN(''N.E:''))
                                                    ELSE LEN(nombre)
                                                END
                                            )
                                        WHEN CHARINDEX(''NE: '', nombre) > 0 THEN
                                            SUBSTRING(
                                                nombre,
                                                CHARINDEX(''NE: '', nombre) + LEN(''NE: ''), 
                                                CASE 
                                                    WHEN CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''NE: '', nombre)) > 0 THEN
                                                        CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''NE: '', nombre)) - (CHARINDEX(''NE: '', nombre) + LEN(''NE: ''))
                                                    ELSE LEN(nombre)
                                                END
                                            )
                                        WHEN CHARINDEX(''NE:'', nombre) > 0 THEN
                                            SUBSTRING(
                                                nombre,
                                                CHARINDEX(''NE:'', nombre) + LEN(''NE:''), 
                                                CASE 
                                                    WHEN CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''NE:'', nombre)) > 0 THEN
                                                        CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''NE:'', nombre)) - (CHARINDEX(''NE:'', nombre) + LEN(''NE:''))
                                                    ELSE LEN(nombre)
                                                END
                                            )
                                        WHEN CHARINDEX(''ID:NE0'', nombre) > 0 THEN
                                            SUBSTRING(
                                                nombre,
                                                CHARINDEX(''ID:NE0'', nombre) + LEN(''ID:NE0''), 
                                                CASE 
                                                    WHEN CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''ID:NE0'', nombre)) > 0 THEN
                                                        CHARINDEX(''DEPARTAMENTO'', nombre, CHARINDEX(''ID:NE0'', nombre)) - (CHARINDEX(''ID:NE0'', nombre) + LEN(''ID:NE0''))
                                                    ELSE LEN(nombre)
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
                            WHERE not nombre=''.'' and not nombre='''' and not nombre LIKE ''[0-9]%'' AND (convert(date,Hora_Entrada, 103) >= ''$fechaInicio'' and convert(date,Hora_Entrada, 103) <= ''$fechaFin'')  
                        ) AS a 
                    ) as a1
                    left JOIN (
                        select m1.Nombre,m1.NombreV,m2.Id_Empleado,Hora_Entrada,fecha from (
                            Select Nombre, LEFT(Nombre, CHARINDEX('' se encuentra registrado para el'', Nombre) - 1) as NombreV ,hora_entrada,fecha from entradas
                            where Nombre LIKE ''%se encuentra registrado para el%'' AND (convert(date,Hora_Entrada, 103) >= ''$fechaInicio'' and convert(date,Hora_Entrada, 103) <= ''$fechaFin'') 
                        ) as m1
                        left join (Select * from conped) as m2 on m1.NombreV=m2.Nombre
                    ) as a2 on a1.Nombre=a2.Nombre and a1.Hora_Entrada=a2.Hora_Entrada and a1.fecha=a2.fecha 
                ) as a
            ) AS b
            LEFT JOIN (SELECT Id_Empleado, Nombre FROM ConPed) AS c ON b.NE_EXTRAIDO1 = c.Id_Empleado
        ) as tt1
        Group by No_Empleado, Nombre 
    ) as tt3
)

SELECT 
    Id_Empleado = case when pvt.Id_Empleado>0 then pvt.Id_Empleado else ISNULL(e.Empleado, pvt.Id_Empleado) end,   
    Nombre = case when pvt.Id_Empleado>0 then pvt.Nombre else ISNULL(e.Nombre, pvt.Nombre) end, 
    ' + @columns + ',
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
        ) AS E5
    ) AS E5
    WHERE CAST(Fecha AS CHAR) >= ''$fechaInicio'' AND CAST(Fecha AS CHAR) <= ''$fechaFin''
) AS SourceTable
PIVOT (
    SUM(Tipo_Comida)
    FOR fecha IN (' + @columns + ')
) AS pvt
RIGHT JOIN EntradasCTE e ON pvt.Id_Empleado = e.Empleado
ORDER BY pvt.Id_Empleado;
';

EXEC sp_executesql @sql;
";

// Ejecutar consulta principal
$stmt = sqlsrv_query($conn, $sql);

// Procesar resultados principales
$totalEmpleados = 0;
$totalConsumos = 0;
$totalEntradas = 0;
$montoTotalConsumos = 0;
$montoTotalEntradas = 0;

$rows = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $totalConsumosValue = isset($row['TotalConsumos']) ? intval($row['TotalConsumos']) : 0;
        $totalEntradasValue = isset($row['TotalEntradas']) ? intval($row['TotalEntradas']) : 0;
        
        $row['MontoConsumos'] = $totalConsumosValue * 30;
        $row['MontoEntradas'] = $totalEntradasValue * 30;
        
        $montoTotalConsumos += $row['MontoConsumos'];
        $montoTotalEntradas += $row['MontoEntradas'];
        $totalConsumos += $totalConsumosValue;
        $totalEntradas += $totalEntradasValue;
        
        // Calcular total complementos y cancelaciones para este empleado
        $totalComplementosEmpleado = 0;
        $totalCancelacionesEmpleado = 0;
        
        // Buscar complementos para este empleado
        foreach ($complementosData as $complemento) {
            if ($complemento['Nombre'] == $row['Nombre']) {
                $totalComplementosEmpleado = 
                    $complemento['CAFÉ O TÉ'] + 
                    $complemento['TORTILLAS'] + 
                    $complemento['AGUA'] + 
                    $complemento['DESECHABLE'];
                break;
            }
        }
        
        // Buscar cancelaciones para este empleado
        foreach ($cancelacionesData as $cancelacion) {
            if ($cancelacion['Nombre'] == $row['Nombre']) {
                $totalCancelacionesEmpleado = $cancelacion['Total'];
                break;
            }
        }
        
        // Agregar las columnas nuevas al array
        $row['TotalComplementos'] = $totalComplementosEmpleado;
        $row['TotalCancelaciones'] = $totalCancelacionesEmpleado;
        
        $rows[] = $row;
        $totalEmpleados++;
    }
}

// ————— EXPORTACIÓN A EXCEL —————
if ($exportarExcel && $stmt && count($rows) > 0) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_comedor_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr><th colspan="10" style="background:#1e3a5c;color:white;font-size:16px;padding:10px;">REPORTE DE COMEDOR</th></tr>';
    echo '<tr><th colspan="10" style="background:#2d4a72;color:white;padding:8px;">Periodo: ' . $fechaInicio . ' al ' . $fechaFin . '</th></tr>';
    
    // Encabezados
    echo '<tr style="background:#3d5a80;color:white;font-weight:bold;">';
    if (count($rows) > 0) {
        $firstRow = $rows[0];
        foreach ($firstRow as $col => $val) {
            if (!in_array($col, ['Empleado', 'NombreEntradas'])) {
                echo '<th style="padding:8px;border:1px solid #ddd;">' . $col . '</th>';
            }
        }
    }
    echo '</tr>';
    
    // Datos
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $col => $val) {
            if (in_array($col, ['Empleado', 'NombreEntradas'])) continue;
            
            if ($col === 'MontoConsumos' || $col === 'MontoEntradas') {
                echo '<td style="padding:6px;border:1px solid #ddd;text-align:right;">$' . number_format($val, 2) . '</td>';
            } elseif ($col === 'TotalComplementos' || $col === 'TotalCancelaciones') {
                echo '<td style="padding:6px;border:1px solid #ddd;text-align:center;font-weight:bold;">' . $val . '</td>';
            } else {
                echo '<td style="padding:6px;border:1px solid #ddd;">' . $val . '</td>';
            }
        }
        echo '</tr>';
    }
    
    // Sección de Cancelaciones
    if (count($cancelacionesData) > 0) {
        echo '<tr><td colspan="10" style="background:#dc3545;color:white;font-weight:bold;padding:10px;text-align:center;">CANCELACIONES</td></tr>';
        echo '<tr style="background:#e74c3c;color:white;font-weight:bold;">';
        echo '<th style="padding:8px;border:1px solid #ddd;">Nombre</th>';
        echo '<th style="padding:8px;border:1px solid #ddd;">Total Cancelaciones</th>';
        echo '</tr>';
        
        foreach ($cancelacionesData as $cancelacion) {
            echo '<tr>';
            echo '<td style="padding:6px;border:1px solid #ddd;">' . $cancelacion['Nombre'] . '</td>';
            echo '<td style="padding:6px;border:1px solid #ddd;text-align:center;">' . $cancelacion['Total'] . '</td>';
            echo '</tr>';
        }
    }
    
    // Sección de Complementos
    if (count($complementosData) > 0) {
        echo '<tr><td colspan="10" style="background:#28a745;color:white;font-weight:bold;padding:10px;text-align:center;">COMPLEMENTOS</td></tr>';
        echo '<tr style="background:#34ce57;color:white;font-weight:bold;">';
        echo '<th style="padding:8px;border:1px solid #ddd;">Nombre</th>';
        echo '<th style="padding:8px;border:1px solid #ddd;">CAFÉ O TÉ</th>';
        echo '<th style="padding:8px;border:1px solid #ddd;">TORTILLAS</th>';
        echo '<th style="padding:8px;border:1px solid #ddd;">AGUA</th>';
        echo '<th style="padding:8px;border:1px solid #ddd;">DESECHABLE</th>';
        echo '</tr>';
        
        foreach ($complementosData as $complemento) {
            echo '<tr>';
            echo '<td style="padding:6px;border:1px solid #ddd;">' . $complemento['Nombre'] . '</td>';
            echo '<td style="padding:6px;border:1px solid #ddd;text-align:center;">' . $complemento['CAFÉ O TÉ'] . '</td>';
            echo '<td style="padding:6px;border:1px solid #ddd;text-align:center;">' . $complemento['TORTILLAS'] . '</td>';
            echo '<td style="padding:6px;border:1px solid #ddd;text-align:center;">' . $complemento['AGUA'] . '</td>';
            echo '<td style="padding:6px;border:1px solid #ddd;text-align:center;">' . $complemento['DESECHABLE'] . '</td>';
            echo '</tr>';
        }
    }
    
    echo '</table>';
    exit;
}

if (!$exportarExcel):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Reporte de Comedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ===== ESTILOS MEJORADOS PARA MEJOR LECTURA ===== */
        :root {
            --primary-color: #1e3a5c;
            --secondary-color: #2d4a72;
            --accent-color: #3d5a80;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gold-color: #d4af37;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 15px;
            font-size: 14px;
        }

        .container-fluid {
            max-width: 1400px;
            padding: 0 15px;
        }

        /* ===== MEJORAS EN EL HEADER ===== */
        .header-container {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .header-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .period-info {
            background: rgba(255,255,255,0.15);
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 13px;
            display: inline-block;
        }

        /* ===== MEJORAS EN TARJETAS ===== */
        .card {
            background: white;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* ===== MEJORAS EN FORMULARIOS ===== */
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e1e5eb;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 13px;
        }

        .form-control {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 92, 0.25);
        }

        /* ===== MEJORAS EN BOTONES ===== */
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
        }

        /* ===== MEJORAS EN TABLAS ===== */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e1e5eb;
        }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 800px;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background: var(--primary-color);
            color: white;
            padding: 12px 15px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--secondary-color);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            vertical-align: middle;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .fixed-column {
            background: var(--secondary-color) !important;
            color: white !important;
            font-weight: 600;
            position: sticky;
            left: 0;
            z-index: 5;
            border-right: 2px solid var(--primary-color);
        }

        .money-cell {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--success-color);
            text-align: right;
        }
        
        .complementos-cell {
            background-color: #e8f5e8 !important;
            color: var(--success-color) !important;
            font-weight: 600;
            text-align: center;
        }
        
        .cancelaciones-cell {
            background-color: #ffeaea !important;
            color: var(--danger-color) !important;
            font-weight: 600;
            text-align: center;
        }

        /* ===== MEJORAS EN BADGES ===== */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-success {
            background: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .badge-danger {
            background: #f8d7da;
            color: var(--danger-color);
            border: 1px solid #f5c6cb;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* ===== MEJORAS EN ESTADÍSTICAS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== MEJORAS EN GRÁFICAS ===== */
        .chart-container {
            background: white;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===== MEJORAS EN ACCIONES ===== */
        .actions-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        /* ===== MEJORAS EN FOOTER ===== */
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 13px;
            border-top: 1px solid #e1e5eb;
            margin-top: 30px;
        }

        /* ===== MEJORAS EN RESPONSIVIDAD ===== */
        @media (max-width: 1200px) {
            .container-fluid {
                padding: 0 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .stat-card {
                padding: 15px;
            }
        }

        @media (max-width: 992px) {
            body {
                padding: 10px;
                font-size: 13px;
            }
            
            .header-container {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .header-title {
                font-size: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .table-wrapper {
                margin: 0 -10px;
                padding: 0 10px;
            }
            
            th, td {
                padding: 10px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 8px;
            }
            
            .header-container {
                padding: 12px;
            }
            
            .header-title {
                font-size: 18px;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 12px;
            }
            
            .card-title {
                font-size: 14px;
            }
            
            .form-control {
                padding: 8px 10px;
                font-size: 13px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 13px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 11px;
            }
            
            .table-wrapper {
                margin: 0 -8px;
                padding: 0 8px;
            }
            
            .badge {
                padding: 4px 8px;
                font-size: 11px;
            }
        }

        @media (max-width: 576px) {
            .header-title {
                font-size: 16px;
            }
            
            .header-subtitle {
                font-size: 12px;
            }
            
            .period-info {
                font-size: 11px;
                padding: 8px 12px;
            }
            
            .actions-container {
                flex-direction: column;
            }
            
            .actions-container .btn {
                width: 100%;
                margin-bottom: 8px;
            }
            
            .chart-container {
                padding: 15px;
            }
            
            .chart-title {
                font-size: 14px;
            }
            
            .footer {
                padding: 15px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .container-fluid {
                padding: 0 5px;
            }
            
            body {
                padding: 5px;
            }
            
            .header-container {
                padding: 10px;
            }
            
            .card-header {
                padding: 12px 15px;
            }
            
            .card-body {
                padding: 10px;
            }
            
            .form-container {
                padding: 15px;
            }
            
            th, td {
                padding: 6px 8px;
                font-size: 10px;
            }
            
            .table-wrapper {
                margin: 0 -5px;
                padding: 0 5px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .stat-label {
                font-size: 10px;
            }
        }

        @media (max-width: 360px) {
            .header-title {
                font-size: 14px;
            }
            
            .card-title {
                font-size: 12px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
            
            th, td {
                padding: 5px 6px;
                font-size: 9px;
            }
        }

        /* ===== MEJORAS EN SCROLLBAR ===== */
        .table-wrapper::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* ===== MEJORAS EN ANIMACIONES ===== */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== MEJORAS EN ESTADOS ===== */
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 14px;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #adb5bd;
        }

        /* ===== MEJORAS EN TAMAÑOS DE FUENTE ===== */
        .text-sm {
            font-size: 12px !important;
        }

        .text-xs {
            font-size: 11px !important;
        }

        .text-lg {
            font-size: 16px !important;
        }

        .text-xl {
            font-size: 18px !important;
        }

        /* ===== MEJORAS EN ESPACIADO ===== */
        .p-sm {
            padding: 10px !important;
        }

        .p-md {
            padding: 15px !important;
        }

        .p-lg {
            padding: 20px !important;
        }

        .m-sm {
            margin: 5px !important;
        }

        .m-md {
            margin: 10px !important;
        }

        .m-lg {
            margin: 15px !important;
        }

        /* ===== MEJORAS EN ALINEACIÓN ===== */
        .text-left {
            text-align: left !important;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        /* ===== MEJORAS EN VISIBILIDAD ===== */
        .d-block-mobile {
            display: block !important;
        }

        .d-none-mobile {
            display: none !important;
        }

        @media (min-width: 768px) {
            .d-block-mobile {
                display: none !important;
            }
            
            .d-none-mobile {
                display: block !important;
            }
        }

        /* ===== MEJORAS EN BORDES ===== */
        .border-light {
            border-color: #e1e5eb !important;
        }

        .border-primary {
            border-color: var(--primary-color) !important;
        }

        /* ===== MEJORAS EN SOMBRAS ===== */
        .shadow-sm {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
        }

        .shadow-md {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }

        .shadow-lg {
            box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
        }

        /* ===== MEJORAS EN FONDOS ===== */
        .bg-light {
            background-color: #f8f9fa !important;
        }

        .bg-lighter {
            background-color: #fafbfc !important;
        }
        
        /* ===== ESTILOS ESPECIALES PARA NUEVAS COLUMNAS ===== */
        .th-complementos {
            background: var(--success-color) !important;
        }
        
        .th-cancelaciones {
            background: var(--danger-color) !important;
        }
        
        .highlight-complementos {
            background: linear-gradient(135deg, #d4edda, #e8f5e8) !important;
            color: var(--success-color) !important;
            font-weight: 700;
            border-left: 3px solid var(--success-color) !important;
        }
        
        .highlight-cancelaciones {
            background: linear-gradient(135deg, #f8d7da, #ffeaea) !important;
            color: var(--danger-color) !important;
            font-weight: 700;
            border-left: 3px solid var(--danger-color) !important;
        }
        
        .icon-complementos {
            color: var(--success-color);
        }
        
        .icon-cancelaciones {
            color: var(--danger-color);
        }
        
        /* ===== MEJORAS EN HOVER PARA NUEVAS COLUMNAS ===== */
        tr:hover .highlight-complementos {
            background: linear-gradient(135deg, #c3e6cb, #d4edda) !important;
        }
        
        tr:hover .highlight-cancelaciones {
            background: linear-gradient(135deg, #f5c6cb, #f8d7da) !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Encabezado Mejorado -->
        <header class="header-container fade-in">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="header-title">
                        <i class="fas fa-utensils me-2"></i>
                        Reporte de Comedor
                    </h1>
                    <p class="header-subtitle">
                        Sistema de seguimiento y análisis de consumos, complementos y cancelaciones
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="period-info">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> - <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filtros Mejorados -->
        <div class="card fade-in">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-filter me-2"></i>
                    Configurar Reporte
                </h2>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <label for="fechaInicio" class="form-label">
                            <i class="fas fa-calendar-plus me-2"></i> Fecha de Inicio
                        </label>
                        <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" 
                               value="<?php echo htmlspecialchars($fechaInicio); ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label for="fechaFin" class="form-label">
                            <i class="fas fa-calendar-check me-2"></i> Fecha de Fin
                        </label>
                        <input type="date" class="form-control" id="fechaFin" name="fechaFin" 
                               value="<?php echo htmlspecialchars($fechaFin); ?>" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loading State -->
        <div class="loading card" id="loading" style="display: none;">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mb-0">Procesando datos, por favor espere...</p>
        </div>

        <?php if ($stmt && count($rows) > 0): ?>
            <!-- Resumen Ejecutivo Mejorado -->
            <div class="card fade-in">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-pie me-2"></i>
                        Resumen Ejecutivo
                    </h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-value"><?php echo number_format($totalEmpleados); ?></div>
                            <div class="stat-label">Total Empleados</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-utensils"></i></div>
                            <div class="stat-value"><?php echo number_format($totalConsumos); ?></div>
                            <div class="stat-label">Total Consumos</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                            <div class="stat-value"><?php echo number_format($totalEntradas); ?></div>
                            <div class="stat-label">Total Entradas</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-coffee"></i></div>
                            <div class="stat-value"><?php echo number_format($totalComplementos); ?></div>
                            <div class="stat-label">Complementos</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                            <div class="stat-value"><?php echo number_format($totalCancelaciones); ?></div>
                            <div class="stat-label">Cancelaciones</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="stat-value">$<?php echo number_format($montoTotalConsumos + $montoTotalEntradas, 0); ?></div>
                            <div class="stat-label">Monto Total</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla Principal Mejorada CON NUEVAS COLUMNAS -->
            <div class="card fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>
                        Registro Detallado de Consumos
                        <span class="badge bg-success ms-2">Nuevo: Complementos y Cancelaciones</span>
                    </h2>
                    <div>
                        <a href="?fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>&exportar=excel" 
                           class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-2"></i> Excel
                        </a>
                        <button class="btn btn-primary btn-sm ms-2" onclick="window.print()">
                            <i class="fas fa-print me-2"></i> Imprimir
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <div class="table-wrapper">
                            <table id="tablaPrincipal" class="display stripe row-border" style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="fixed-column">ID</th>
                                        <th class="fixed-column">Nombre del Empleado</th>
                                        <?php
                                        if (count($rows) > 0) {
                                            $firstRow = $rows[0];
                                            foreach ($firstRow as $col => $val) {
                                                if (!in_array($col, ['Id_Empleado', 'Nombre', 'Empleado', 'NombreEntradas'])) {
                                                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $col)) {
                                                        // Aplicar clases especiales para las nuevas columnas
                                                        if ($col === 'TotalComplementos') {
                                                            echo '<th class="th-complementos"><i class="fas fa-coffee me-2"></i>' . htmlspecialchars($col) . '</th>';
                                                        } elseif ($col === 'TotalCancelaciones') {
                                                            echo '<th class="th-cancelaciones"><i class="fas fa-times-circle me-2"></i>' . htmlspecialchars($col) . '</th>';
                                                        } else {
                                                            echo '<th>' . htmlspecialchars($col) . '</th>';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="fixed-column"><?php echo htmlspecialchars($row['Id_Empleado']); ?></td>
                                            <td class="fixed-column"><?php echo htmlspecialchars($row['Nombre']); ?></td>
                                            
                                            <?php foreach ($row as $col => $val): ?>
                                                <?php if (in_array($col, ['Id_Empleado', 'Nombre', 'Empleado', 'NombreEntradas'])) continue; ?>
                                                <?php if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $col)): ?>
                                                    <?php if ($col === 'MontoConsumos' || $col === 'MontoEntradas'): ?>
                                                        <td class="money-cell">$<?php echo number_format($val, 2); ?></td>
                                                    <?php elseif ($col === 'TotalComplementos'): ?>
                                                        <td class="highlight-complementos text-center">
                                                            <i class="fas fa-coffee icon-complementos me-2"></i>
                                                            <strong><?php echo htmlspecialchars($val); ?></strong>
                                                        </td>
                                                    <?php elseif ($col === 'TotalCancelaciones'): ?>
                                                        <td class="highlight-cancelaciones text-center">
                                                            <i class="fas fa-times-circle icon-cancelaciones me-2"></i>
                                                            <strong><?php echo htmlspecialchars($val); ?></strong>
                                                        </td>
                                                    <?php else: ?>
                                                        <td><?php echo htmlspecialchars($val); ?></td>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-end">TOTALES:</th>
                                        <?php
                                        // Calcular totales por columna
                                        $columnTotals = [];
                                        foreach ($rows as $row) {
                                            foreach ($row as $col => $val) {
                                                if (!in_array($col, ['Id_Empleado', 'Nombre', 'Empleado', 'NombreEntradas']) && 
                                                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $col)) {
                                                    if (!isset($columnTotals[$col])) {
                                                        $columnTotals[$col] = 0;
                                                    }
                                                    if (is_numeric($val)) {
                                                        $columnTotals[$col] += $val;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Mostrar totales
                                        foreach ($columnTotals as $col => $total) {
                                            if ($col === 'MontoConsumos' || $col === 'MontoEntradas') {
                                                echo '<th class="money-cell">$' . number_format($total, 2) . '</th>';
                                            } elseif ($col === 'TotalComplementos') {
                                                echo '<th class="highlight-complementos text-center">' . number_format($total) . '</th>';
                                            } elseif ($col === 'TotalCancelaciones') {
                                                echo '<th class="highlight-cancelaciones text-center">' . number_format($total) . '</th>';
                                            } elseif ($col === 'TotalConsumos' || $col === 'TotalEntradas') {
                                                echo '<th class="text-center fw-bold">' . number_format($total) . '</th>';
                                            } else {
                                                echo '<th class="text-center">' . (is_numeric($total) ? number_format($total) : '-') . '</th>';
                                            }
                                        }
                                        ?>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Complementos Mejorados -->
            <?php if (count($complementosData) > 0): ?>
            <div class="card fade-in">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-coffee me-2"></i>
                        Reporte Detallado de Complementos
                    </h2>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <?php foreach ($resumenComplementos as $key => $value): ?>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <?php if ($key === 'CAFÉ O TÉ'): ?>
                                        <i class="fas fa-mug-hot"></i>
                                    <?php elseif ($key === 'TORTILLAS'): ?>
                                        <i class="fas fa-bread-slice"></i>
                                    <?php elseif ($key === 'AGUA'): ?>
                                        <i class="fas fa-tint"></i>
                                    <?php else: ?>
                                        <i class="fas fa-trash"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="stat-value"><?php echo number_format($value); ?></div>
                                <div class="stat-label"><?php echo $key; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-wrapper">
                            <table id="tablaComplementos" class="table">
                                <thead>
                                    <tr>
                                        <th>Nombre del Empleado</th>
                                        <th>CAFÉ O TÉ</th>
                                        <th>TORTILLAS</th>
                                        <th>AGUA</th>
                                        <th>DESECHABLE</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complementosData as $complemento): 
                                        $total = $complemento['CAFÉ O TÉ'] + $complemento['TORTILLAS'] + $complemento['AGUA'] + $complemento['DESECHABLE'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($complemento['Nombre']); ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-warning"><?php echo $complemento['CAFÉ O TÉ']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-warning"><?php echo $complemento['TORTILLAS']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-warning"><?php echo $complemento['AGUA']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-warning"><?php echo $complemento['DESECHABLE']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-success"><?php echo $total; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">TOTALES:</th>
                                        <th class="text-center"><?php echo number_format($resumenComplementos['CAFÉ O TÉ']); ?></th>
                                        <th class="text-center"><?php echo number_format($resumenComplementos['TORTILLAS']); ?></th>
                                        <th class="text-center"><?php echo number_format($resumenComplementos['AGUA']); ?></th>
                                        <th class="text-center"><?php echo number_format($resumenComplementos['DESECHABLE']); ?></th>
                                        <th class="text-center"><?php echo number_format($totalComplementos); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cancelaciones Mejoradas -->
            <?php if (count($cancelacionesData) > 0): ?>
            <div class="card fade-in">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-times-circle me-2"></i>
                        Reporte Detallado de Cancelaciones
                    </h2>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                                <div class="stat-value"><?php echo number_format($totalCancelaciones); ?></div>
                                <div class="stat-label">Total Cancelaciones</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                                <div class="stat-value"><?php echo number_format(count($cancelacionesData)); ?></div>
                                <div class="stat-label">Empleados</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                                <div class="stat-value"><?php echo number_format($totalCancelaciones > 0 ? $totalCancelaciones / count($cancelacionesData) : 0, 1); ?></div>
                                <div class="stat-label">Promedio</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="stat-value"><?php echo number_format($cancelacionesData[0]['Total'] ?? 0); ?></div>
                                <div class="stat-label">Máximo</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <div class="table-wrapper">
                            <table id="tablaCancelaciones" class="table">
                                <thead>
                                    <tr>
                                        <th>Nombre del Empleado</th>
                                        <th>Total Cancelaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cancelacionesData as $cancelacion): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cancelacion['Nombre']); ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-ban me-1"></i>
                                                    <?php echo $cancelacion['Total']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-end">TOTAL:</th>
                                        <th class="text-center"><?php echo number_format($totalCancelaciones); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones Finales -->
            <div class="actions-container fade-in">
                <a href="?fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>&exportar=excel" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i> Exportar a Excel Completo
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Imprimir Reporte Completo
                </button>
                <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                    <i class="fas fa-redo me-2"></i> Actualizar Vista
                </button>
                <button class="btn btn-outline-secondary" onclick="scrollToTop()">
                    <i class="fas fa-arrow-up me-2"></i> Ir al Inicio
                </button>
            </div>

        <?php elseif ($stmt): ?>
            <!-- Estado Sin Datos -->
            <div class="card">
                <div class="card-body">
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <h4 class="mt-3">No hay datos disponibles</h4>
                        <p class="mb-0">No se encontraron registros para el rango de fechas seleccionado.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer Mejorado -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1">
                            <i class="fas fa-shield-alt me-2"></i>
                            Sistema de Reportes - Comedor Corporativo
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-1">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Generado: <?php echo date('d/m/Y H:i'); ?> 
                        </p>
                        <p class="mb-0 text-sm">
                            © <?php echo date('Y'); ?> • 
                            <span class="text-primary">v3.1</span>
                            <span class="badge bg-success ms-2">Nuevo: Columnas agregadas</span>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Inicialización
        $(document).ready(function() {
            // Mostrar loading al enviar formulario
            $('#filterForm').on('submit', function() {
                $('#loading').fadeIn();
            });
            
            // Inicializar DataTables con configuración mejorada
            $('#tablaPrincipal').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']],
                responsive: false,
                scrollX: true,
                autoWidth: false,
                scrollCollapse: true,
                fixedColumns: {
                    leftColumns: 2
                },
                columnDefs: [
                    { width: "80px", targets: 0 },
                    { width: "200px", targets: 1 },
                    { className: "dt-center", targets: "_all" }
                ],
                initComplete: function() {
                    // Ajustar tabla en móviles
                    if ($(window).width() < 768) {
                        $('.dataTables_scrollHead').css('overflow-x', 'auto');
                    }
                    
                    // Resaltar las nuevas columnas
                    $('th.th-complementos, th.th-cancelaciones').addClass('text-white');
                    
                    // Agregar tooltips a las nuevas columnas
                    $('th.th-complementos').attr('title', 'Total de complementos solicitados');
                    $('th.th-cancelaciones').attr('title', 'Total de cancelaciones realizadas');
                }
            });

            // Inicializar tablas secundarias
            if ($('#tablaComplementos').length) {
                $('#tablaComplementos').DataTable({
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    pageLength: 15,
                    order: [[5, 'desc']],
                    responsive: true
                });
            }

            if ($('#tablaCancelaciones').length) {
                $('#tablaCancelaciones').DataTable({
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    pageLength: 15,
                    order: [[1, 'desc']],
                    responsive: true
                });
            }

            // Ajustes responsive
            $(window).resize(function() {
                if ($(window).width() < 768) {
                    $('.dataTables_scrollHead').css('overflow-x', 'auto');
                }
            });
            
            // Función para ordenar por complementos o cancelaciones
            function sortTable(columnIndex) {
                const table = $('#tablaPrincipal').DataTable();
                table.order([columnIndex, 'desc']).draw();
            }
            
            // Agregar eventos a los headers de las nuevas columnas
            $(document).on('click', 'th.th-complementos', function() {
                const columnIndex = $(this).index();
                sortTable(columnIndex);
            });
            
            $(document).on('click', 'th.th-cancelaciones', function() {
                const columnIndex = $(this).index();
                sortTable(columnIndex);
            });
        });

        // Funciones de utilidad
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Reset de fechas
        function resetFechas() {
            const twoWeeksAgo = new Date();
            twoWeeksAgo.setDate(twoWeeksAgo.getDate() - 14);
            const today = new Date();
            
            document.getElementById('fechaInicio').value = twoWeeksAgo.toISOString().split('T')[0];
            document.getElementById('fechaFin').value = today.toISOString().split('T')[0];
        }

        // Fullscreen
        function toggleFullscreen() {
            const elem = document.querySelector('.table-wrapper');
            if (!document.fullscreenElement) {
                elem.requestFullscreen().catch(err => {
                    console.log(`Error: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        // Función para resaltar empleados con muchos complementos o cancelaciones
        function highlightEmployees() {
            $('td.highlight-complementos').each(function() {
                const value = parseInt($(this).text());
                if (value > 10) {
                    $(this).addClass('border-3 border-success');
                }
            });
            
            $('td.highlight-cancelaciones').each(function() {
                const value = parseInt($(this).text());
                if (value > 5) {
                    $(this).addClass('border-3 border-danger');
                }
            });
        }
        
        // Ejecutar al cargar
        setTimeout(highlightEmployees, 1000);
    </script>

    <?php
    // Liberar recursos
    if (isset($stmt)) sqlsrv_free_stmt($stmt);
    if (isset($stmtCancelaciones)) sqlsrv_free_stmt($stmtCancelaciones);
    if (isset($stmtComplementos)) sqlsrv_free_stmt($stmtComplementos);
    sqlsrv_close($conn);
    ?>
</body>
</html>
<?php endif; ?>