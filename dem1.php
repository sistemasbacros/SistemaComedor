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

// ————— Lista de nombres especiales para resaltar en amarillo —————
$nombresEspeciales = [
    'ALEJANDRA CRUZ',
    'ALTA DIRECCION',
    'CRUZ JOSE LUIS',
    'CRUZ RODRIGUEZ ALEJANDRO',
    'JURIDICO',
    'PALMA TREJO SANDY MARK',
    'REYES QUIROZ HILDA',
    'VIGILANCIA',
    'CELAYA YAXI LUIS ENRIQUE',
    'FIRO CORTAZAR FERNANDO',
    'ADAME GARCIA JOSE PAUL',
    'HERRERA CUALI HUGO ALEJANDRO',
    'REYES FONSECA NORMA ANGELICA',
    'JUREZ VZQUEZ MIGUEL ANGEL',
    'SOTO DEL HOYO ISMAEL',
    'GUTIERREZ EZQUIVEL EDGAR',
    'CASTILLO NIETO JESSICA',
    'JOSE FERNANDO OSORIO OJEDA'
];

// Función para verificar si un nombre es especial
function esNombreEspecial($nombre, $listaEspeciales) {
    $nombreUpper = strtoupper(trim($nombre));
    foreach ($listaEspeciales as $especial) {
        if (strpos($nombreUpper, $especial) !== false) {
            return true;
        }
    }
    return false;
}

// ————— CONSULTA DE CANCELACIONES (Solo APROBADO) CON TIPO_CONSUMO Y FECHA —————
$sqlCancelaciones = "
SELECT 
    Nombre, 
    Tipo_Consumo,
    FECHA,
    COUNT(*) as Total 
FROM cancelaciones 
WHERE CONVERT(DATE, FECHA, 102) >= ? 
  AND CONVERT(DATE, FECHA, 102) <= ?
  AND (estatus = 'APROBADO' OR estatus IS NULL OR estatus = '')
GROUP BY Nombre, Tipo_Consumo, FECHA
ORDER BY Nombre, FECHA
";

$paramsCancelaciones = array($fechaInicio, $fechaFin);
$stmtCancelaciones = sqlsrv_query($conn, $sqlCancelaciones, $paramsCancelaciones);

$cancelacionesData = [];
$totalCancelaciones = 0;
$montoTotalCancelaciones = 0;

if ($stmtCancelaciones) {
    while ($row = sqlsrv_fetch_array($stmtCancelaciones, SQLSRV_FETCH_ASSOC)) {
        $cancelacionesData[] = $row;
        $totalCancelaciones += $row['Total'];
        
        // Calcular monto por cancelación según tipo y fecha
        $fechaCancelacion = $row['FECHA'];
        $tipoConsumo = $row['Tipo_Consumo'];
        
        if ($fechaCancelacion) {
            $fechaStr = date('Y-m-d', strtotime($fechaCancelacion));
            $anioCancelacion = date('Y', strtotime($fechaCancelacion));
        } else {
            $fechaStr = 'Fecha no disponible';
            $anioCancelacion = 0;
        }
        
        $es2026OMayor = ($anioCancelacion >= 2026);
        $tipoNormalizado = strtolower(trim($tipoConsumo));
        
        if ($es2026OMayor) {
            if (strpos($tipoNormalizado, 'desayuno') !== false || $tipoNormalizado == 'desayuno') {
                $monto = 35;
            } elseif (strpos($tipoNormalizado, 'comida') !== false || $tipoNormalizado == 'comida') {
                $monto = 45;
            } elseif (strpos($tipoNormalizado, 'ambos') !== false || $tipoNormalizado == 'ambos') {
                $monto = 80;
            } else {
                $monto = 40;
            }
        } else {
            if (strpos($tipoNormalizado, 'desayuno') !== false || 
                strpos($tipoNormalizado, 'comida') !== false || 
                $tipoNormalizado == 'desayuno' || 
                $tipoNormalizado == 'comida') {
                $monto = 30;
            } elseif (strpos($tipoNormalizado, 'ambos') !== false || $tipoNormalizado == 'ambos') {
                $monto = 60;
            } else {
                $monto = 30;
            }
        }
        
        $montoTotalCancelaciones += $monto * $row['Total'];
        
        $cancelacionesData[count($cancelacionesData)-1]['MontoUnitario'] = $monto;
        $cancelacionesData[count($cancelacionesData)-1]['MontoTotal'] = $monto * $row['Total'];
        $cancelacionesData[count($cancelacionesData)-1]['FechaStr'] = $fechaStr;
        $cancelacionesData[count($cancelacionesData)-1]['Anio'] = $anioCancelacion;
        $cancelacionesData[count($cancelacionesData)-1]['TipoNormalizado'] = $tipoNormalizado;
        $cancelacionesData[count($cancelacionesData)-1]['Especial'] = esNombreEspecial($row['Nombre'], $nombresEspeciales);
    }
}

// ————— CONSULTA DE COMPLEMENTOS CON COMIDA PARA LLEVAR —————
$sqlComplementos = "
SELECT 
    Nombre_Limpio AS Nombre,
    ISNULL([CAFÉ O TÉ], 0)     AS [CAFÉ O TÉ],
    ISNULL([TORTILLAS], 0)     AS [TORTILLAS],
    ISNULL([AGUA], 0)          AS [AGUA],
    ISNULL([DESECHABLE], 0)    AS [DESECHABLE],
    ISNULL([COMIDA PARA LLEVAR], 0) AS [COMIDA PARA LLEVAR]
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
    FOR Complemento IN ([CAFÉ O TÉ], [TORTILLAS], [AGUA], [DESECHABLE], [COMIDA PARA LLEVAR])
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
    'DESECHABLE' => 0,
    'COMIDA PARA LLEVAR' => 0
];

// Costos por complemento - ACTUALIZADO CON COMIDA PARA LLEVAR
$costosComplementos = [
    'CAFÉ O TÉ' => 5,
    'TORTILLAS' => 4,
    'AGUA' => 4,
    'DESECHABLE' => 7,
    'COMIDA' => 37,
    'COMIDA PARA LLEVAR' => 52
];

$montoTotalComplementos = 0;
$totalesCostosComplementos = [
    'CAFÉ O TÉ' => 0,
    'TORTILLAS' => 0,
    'AGUA' => 0,
    'DESECHABLE' => 0,
    'COMIDA PARA LLEVAR' => 0,
    'COMIDA' => 0
];

if ($stmtComplementos) {
    while ($row = sqlsrv_fetch_array($stmtComplementos, SQLSRV_FETCH_ASSOC)) {
        $row['Especial'] = esNombreEspecial($row['Nombre'], $nombresEspeciales);
        
        // Inicializar valores para esta fila
        $row['TOTAL'] = 0;
        $row['MONTO_COMIDA'] = 0;
        $row['MONTO_CAFE_TE'] = 0;
        $row['MONTO_TORTILLAS'] = 0;
        $row['MONTO_AGUA'] = 0;
        $row['MONTO_DESECHABLE'] = 0;
        $row['MONTO_COMIDA_LLEVAR'] = 0;
        $row['MONTO_TOTAL'] = 0;
        
        // Calcular montos por complemento
        if (isset($row['CAFÉ O TÉ'])) {
            $cantidad = intval($row['CAFÉ O TÉ']);
            $row['MONTO_CAFE_TE'] = $cantidad * $costosComplementos['CAFÉ O TÉ'];
            $totalesCostosComplementos['CAFÉ O TÉ'] += $row['MONTO_CAFE_TE'];
        }
        
        if (isset($row['TORTILLAS'])) {
            $cantidad = intval($row['TORTILLAS']);
            $row['MONTO_TORTILLAS'] = $cantidad * $costosComplementos['TORTILLAS'];
            $totalesCostosComplementos['TORTILLAS'] += $row['MONTO_TORTILLAS'];
        }
        
        if (isset($row['AGUA'])) {
            $cantidad = intval($row['AGUA']);
            $row['MONTO_AGUA'] = $cantidad * $costosComplementos['AGUA'];
            $totalesCostosComplementos['AGUA'] += $row['MONTO_AGUA'];
        }
        
        if (isset($row['DESECHABLE'])) {
            $cantidad = intval($row['DESECHABLE']);
            $row['MONTO_DESECHABLE'] = $cantidad * $costosComplementos['DESECHABLE'];
            $totalesCostosComplementos['DESECHABLE'] += $row['MONTO_DESECHABLE'];
        }
        
        // Calcular COMIDA PARA LLEVAR
        if (isset($row['COMIDA PARA LLEVAR'])) {
            $cantidad = intval($row['COMIDA PARA LLEVAR']);
            $row['MONTO_COMIDA_LLEVAR'] = $cantidad * $costosComplementos['COMIDA PARA LLEVAR'];
            $totalesCostosComplementos['COMIDA PARA LLEVAR'] += $row['MONTO_COMIDA_LLEVAR'];
        }
        
        // Calcular total general de cantidad
        $row['TOTAL'] = intval($row['CAFÉ O TÉ']) + intval($row['TORTILLAS']) + 
                       intval($row['AGUA']) + intval($row['DESECHABLE']) + 
                       intval($row['COMIDA PARA LLEVAR']);
        
        // Calcular total general de montos
        $row['MONTO_TOTAL'] = $row['MONTO_CAFE_TE'] + $row['MONTO_TORTILLAS'] + 
                             $row['MONTO_AGUA'] + $row['MONTO_DESECHABLE'] + 
                             $row['MONTO_COMIDA_LLEVAR'];
        
        // Agregar a datos
        $complementosData[] = $row;
        
        // Actualizar resumen de cantidades
        foreach ($resumenComplementos as $key => $value) {
            if (isset($row[$key])) {
                $resumenComplementos[$key] += intval($row[$key]);
                $totalComplementos += intval($row[$key]);
            }
        }
        
        $montoTotalComplementos += $row['MONTO_TOTAL'];
    }
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
;WITH EntradasDetalleCTE AS (
    SELECT 
        NE_EXTRAIDO1 AS No_Empleado,
        c.Nombre,
        Tipo_Comida,
        CONVERT(VARCHAR(10), CONVERT(DATE, Hora_Entrada, 105), 23) AS Fecha,
        YEAR(CONVERT(DATE, Hora_Entrada, 105)) AS Anio
    FROM (
        Select * from (
            Select a1.Id_Empleado,a1.Nombre,a1.Area,a1.Hora_Entrada,a1.Fecha as FechaEntrada,a1.NE_Extraido,a1.Tipo_Comida,
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
                        END AS Tipo_Comida
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
),
EntradasCTE AS (
    SELECT 
        No_Empleado as Empleado,
        Nombre,
        COUNT(*) as Total,
        SUM(CASE WHEN Tipo_Comida = ''Desayuno'' THEN 1 ELSE 0 END) as TotalDesayunos,
        SUM(CASE WHEN Tipo_Comida = ''Comida'' THEN 1 ELSE 0 END) as TotalComidas,
        SUM(CASE 
                WHEN Tipo_Comida = ''Desayuno'' AND Anio < 2026 THEN 30
                WHEN Tipo_Comida = ''Desayuno'' AND Anio >= 2026 THEN 35
                ELSE 0
            END) AS MontoDesayunos,
        SUM(CASE 
                WHEN Tipo_Comida = ''Comida'' AND Anio < 2026 THEN 30
                WHEN Tipo_Comida = ''Comida'' AND Anio >= 2026 THEN 45
                ELSE 0
            END) AS MontoComidas
    FROM EntradasDetalleCTE
    GROUP BY No_Empleado, Nombre
),
PedidosCTE AS (
    SELECT 
        Id_Empleado,
        Nombre,
        SUM(CASE WHEN Tipo_Comida = ''Desayuno'' THEN 1 ELSE 0 END) AS TotalConsumosDesayunos,
        SUM(CASE WHEN Tipo_Comida = ''Comida'' THEN 1 ELSE 0 END) AS TotalConsumosComidas,
        SUM(CASE 
                WHEN Tipo_Comida = ''Desayuno'' AND YEAR(Fecha) < 2026 THEN 1 * 30
                WHEN Tipo_Comida = ''Desayuno'' AND YEAR(Fecha) >= 2026 THEN 1 * 35
                WHEN Tipo_Comida = ''Comida'' AND YEAR(Fecha) < 2026 THEN 1 * 30
                WHEN Tipo_Comida = ''Comida'' AND YEAR(Fecha) >= 2026 THEN 1 * 45
                ELSE 0
            END) AS MontoConsumos,
        SUM(CASE 
                WHEN Tipo_Comida = ''Desayuno'' AND YEAR(Fecha) < 2026 THEN 30
                WHEN Tipo_Comida = ''Desayuno'' AND YEAR(Fecha) >= 2026 THEN 35
                ELSE 0
            END) AS MontoConsumosDesayunos,
        SUM(CASE 
                WHEN Tipo_Comida = ''Comida'' AND YEAR(Fecha) < 2026 THEN 30
                WHEN Tipo_Comida = ''Comida'' AND YEAR(Fecha) >= 2026 THEN 45
                ELSE 0
            END) AS MontoConsumosComidas
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
    ) AS PedidosData
    WHERE Fecha >= ''$fechaInicio'' AND Fecha <= ''$fechaFin''
    GROUP BY Id_Empleado, Nombre
),
CancelacionesCTE AS (
    SELECT 
        Nombre,
        SUM(
            CASE 
                WHEN Tipo_Consumo = ''DESAYUNO'' AND YEAR(CONVERT(DATE, FECHA, 102)) < 2026 THEN Total * 30
                WHEN Tipo_Consumo = ''DESAYUNO'' AND YEAR(CONVERT(DATE, FECHA, 102)) >= 2026 THEN Total * 35
                WHEN Tipo_Consumo = ''COMIDA'' AND YEAR(CONVERT(DATE, FECHA, 102)) < 2026 THEN Total * 30
                WHEN Tipo_Consumo = ''COMIDA'' AND YEAR(CONVERT(DATE, FECHA, 102)) >= 2026 THEN Total * 45
				WHEN Tipo_Consumo = ''AMBOS'' AND YEAR(CONVERT(DATE, FECHA, 102)) >= 2026 THEN Total * 80
                ELSE 0
            END
        ) AS MontoCancelaciones
    FROM (
        SELECT 
            Nombre, 
            Tipo_Consumo,
            FECHA,
            COUNT(*) as Total 
        FROM cancelaciones 
        WHERE CONVERT(DATE, FECHA, 102) >= ''$fechaInicio'' 
          AND CONVERT(DATE, FECHA, 102) <= ''$fechaFin''
          AND (estatus = ''APROBADO'' OR estatus IS NULL OR estatus = '''')
        GROUP BY Nombre, Tipo_Consumo, FECHA
    ) AS CancelacionesBase
    GROUP BY Nombre
)

SELECT 
    Id_Empleado = case when pvt.Id_Empleado>0 then pvt.Id_Empleado else ISNULL(e.Empleado, pvt.Id_Empleado) end,   
    Nombre = case when pvt.Id_Empleado>0 then pvt.Nombre else ISNULL(e.Nombre, pvt.Nombre) end, 
    ' + @columns + ',
    ' + @columnsSum + ' AS TotalConsumos,
    ISNULL(p.TotalConsumosDesayunos, 0) AS TotalConsumosDesayunos,
    ISNULL(p.TotalConsumosComidas, 0) AS TotalConsumosComidas,
    ISNULL(p.MontoConsumosDesayunos, 0) AS MontoConsumosDesayunos,
    ISNULL(p.MontoConsumosComidas, 0) AS MontoConsumosComidas,
    ISNULL(p.MontoConsumos, 0) AS MontoConsumos,
    ISNULL(e.Empleado, pvt.Id_Empleado) AS Empleado,
    ISNULL(e.Nombre, pvt.Nombre) AS NombreEntradas,
    ISNULL(e.Total, 0) AS TotalEntradas,
    ISNULL(e.TotalDesayunos, 0) AS TotalDesayunos,
    ISNULL(e.TotalComidas, 0) AS TotalComidas,
    ISNULL(e.MontoDesayunos, 0) AS MontoEntradasDesayunos,
    ISNULL(e.MontoComidas, 0) AS MontoEntradasComidas,
    ISNULL(e.MontoDesayunos + e.MontoComidas, 0) AS MontoEntradasTotal,
    ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0) AS DIFCONSUENTRADAS,
    CASE 
        WHEN ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0) > 0 
        THEN ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0)
        ELSE 0 
    END AS MontoQueNosetomo,
    ISNULL(c.MontoCancelaciones, 0) AS MontoCancelaciones,
    CASE 
        WHEN (ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0)) > 0 
        THEN ((ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0)) - ISNULL(c.MontoCancelaciones, 0)) * 2
        ELSE 0 
    END AS MontoFinalque,
    ISNULL(e.MontoDesayunos + e.MontoComidas, 0) + 
    CASE 
        WHEN (ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0)) > 0 
        THEN ((ISNULL(p.MontoConsumos, 0) - ISNULL(e.MontoDesayunos + e.MontoComidas, 0)) - ISNULL(c.MontoCancelaciones, 0)) * 2
        ELSE 0 
    END AS MontoFinalDescontar
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
LEFT JOIN PedidosCTE p ON pvt.Id_Empleado = p.Id_Empleado
LEFT JOIN CancelacionesCTE c ON e.Nombre = c.Nombre
ORDER BY pvt.Id_Empleado;
';

EXEC sp_executesql @sql;
";

// Ejecutar consulta principal
$stmt = sqlsrv_query($conn, $sql);

// Procesar resultados principales
$totalEmpleados = 0;
$rows = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (empty($row['Id_Empleado']) || $row['Id_Empleado'] <= 0) {
            continue;
        }
        
        $row['Especial'] = esNombreEspecial($row['Nombre'], $nombresEspeciales);
        $rows[] = $row;
        $totalEmpleados++;
    }
}

// ==============================================
// EXPORTACIÓN A EXCEL (.xls) CON COLORES COMPLETOS
// ==============================================
if ($exportarExcel && $stmt && count($rows) > 0) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_comedor_' . date('Y-m-d') . '.xls"');
    
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reporte de Comedor</title></head><body>';
    
    // TABLA PRINCIPAL
    if (count($rows) > 0) {
        echo '<table border="1" cellspacing="0" cellpadding="3" style="font-family:Calibri;font-size:11px;border-collapse:collapse;">';
        
        echo '<tr>';
        echo '<th colspan="' . (count($rows[0]) - 3) . '" style="background:#1e3a5c;color:white;font-size:14px;padding:8px;text-align:center;">';
        echo 'REPORTE DE COMEDOR - REGISTRO DETALLADO DE CONSUMOS';
        echo '</th>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th colspan="' . (count($rows[0]) - 3) . '" style="background:#2d4a72;color:white;font-size:11px;padding:6px;text-align:center;">';
        echo 'PERÍODO: ' . $fechaInicio . ' al ' . $fechaFin;
        echo '</th>';
        echo '</tr>';
        
        // Encabezados
        echo '<tr style="background:#1e3a5c;color:white;font-weight:bold;">';
        $firstRow = $rows[0];
        foreach ($firstRow as $col => $val) {
            if (!in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) {
                echo '<th style="padding:5px;border:1px solid #2d4a72;text-align:center;">' . htmlspecialchars($col) . '</th>';
            }
        }
        echo '</tr>';
        
        // Datos
        foreach ($rows as $row) {
            // Determinar el estilo de fondo para toda la fila
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #ddd;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #ddd;text-align:left;';
            
            if ($row['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;'; // Amarillo claro para toda la fila
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            foreach ($row as $col => $val) {
                if (in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) continue;
                
                $style = $celdaStyle;
                
                // Aplicar estilo especial para celda de nombre
                if ($col === 'Nombre') {
                    $style = $nombreStyle;
                }
                
                // Estilo específico para celdas de ID cuando es especial
                if ($col === 'Id_Empleado' && $row['Especial']) {
                    $style = str_replace('#FFFFE0', '#FFFACD', $style); // Un tono más oscuro para ID
                }
                
                if (strpos($col, 'Monto') === 0) {
                    $style = str_replace('text-align:center;', 'text-align:right;', $style);
                    $val = '$' . number_format($val, 2);
                }
                
                echo '<td style="' . $style . '">' . htmlspecialchars($val) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table><br><br>';
    }
    
    // COMPLEMENTOS CON COSTOS Y COMIDA PARA LLEVAR
    if (count($complementosData) > 0) {
        echo '<table border="1" cellspacing="0" cellpadding="3" style="font-family:Calibri;font-size:11px;border-collapse:collapse;">';
        
        echo '<tr>';
        echo '<th colspan="13" style="background:#229954;color:white;font-size:14px;padding:8px;text-align:center;">';
        echo 'REPORTE DE COMPLEMENTOS CON COSTOS - INCLUYE COMIDA PARA LLEVAR';
        echo '</th>';
        echo '</tr>';
        
        echo '<tr style="background:#229954;color:white;font-weight:bold;">';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:left;">Nombre del Empleado</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">CAFÉ O TÉ</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">TORTILLAS</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">AGUA</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">DESECHABLE</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">COMIDA LLEVAR</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">TOTAL</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">COSTO CAFÉ/TÉ</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">COSTO TORTILLAS</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">COSTO AGUA</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">COSTO DESECHABLE</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">COSTO COMIDA LLEVAR</th>';
        echo '<th style="padding:5px;border:1px solid #1e8449;text-align:center;">TOTAL COSTOS</th>';
        echo '</tr>';
        
        foreach ($complementosData as $complemento) {
            $total = $complemento['TOTAL'];
            $totalCostos = $complemento['MONTO_TOTAL'];
            
            // Estilo para fila completa
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #ddd;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #ddd;text-align:left;';
            
            if ($complemento['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;'; // Amarillo claro para toda la fila
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            echo '<td style="' . $nombreStyle . '">' . htmlspecialchars($complemento['Nombre']) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $complemento['CAFÉ O TÉ'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $complemento['TORTILLAS'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $complemento['AGUA'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $complemento['DESECHABLE'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . ($complemento['COMIDA PARA LLEVAR'] ?? 0) . '</td>';
            
            // Estilo para celda de total
            $totalStyle = $celdaStyle;
            if ($complemento['Especial']) {
                $totalStyle = str_replace('#FFFFE0', '#FFFACD', $totalStyle);
            }
            $totalStyle .= 'font-weight:bold;';
            
            echo '<td style="' . $totalStyle . '">' . $total . '</td>';
            
            // Celdas de costos
            $costoStyle = $celdaStyle;
            $costoStyle .= 'text-align:right;font-family:Courier New;';
            
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_CAFE_TE'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_TORTILLAS'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_AGUA'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_DESECHABLE'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_COMIDA_LLEVAR'], 2) . '</td>';
            
            // Celda de total costos
            $totalCostosStyle = $costoStyle;
            $totalCostosStyle .= 'font-weight:bold;color:#1e8449;';
            
            echo '<td style="' . $totalCostosStyle . '">$' . number_format($totalCostos, 2) . '</td>';
            echo '</tr>';
        }
        
        // RESUMEN DE COSTOS DE COMPLEMENTOS
        echo '<tr style="background:#1e8449;color:white;font-weight:bold;">';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">TOTALES CANTIDAD:</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:center;">' . $resumenComplementos['CAFÉ O TÉ'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:center;">' . $resumenComplementos['TORTILLAS'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:center;">' . $resumenComplementos['AGUA'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:center;">' . $resumenComplementos['DESECHABLE'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:center;">' . $resumenComplementos['COMIDA PARA LLEVAR'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:center;">' . $totalComplementos . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">$' . number_format($totalesCostosComplementos['CAFÉ O TÉ'], 2) . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">$' . number_format($totalesCostosComplementos['TORTILLAS'], 2) . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">$' . number_format($totalesCostosComplementos['AGUA'], 2) . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">$' . number_format($totalesCostosComplementos['DESECHABLE'], 2) . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">$' . number_format($totalesCostosComplementos['COMIDA PARA LLEVAR'], 2) . '</td>';
        echo '<td style="padding:5px;border:1px solid #186a3b;text-align:right;">$' . number_format($montoTotalComplementos, 2) . '</td>';
        echo '</tr>';
        
        // RESUMEN DE TARIFAS
        echo '<tr style="background:#27ae60;color:white;font-weight:bold;">';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:right;">TARIFAS:</td>';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:center;">$' . $costosComplementos['CAFÉ O TÉ'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:center;">$' . $costosComplementos['TORTILLAS'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:center;">$' . $costosComplementos['AGUA'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:center;">$' . $costosComplementos['DESECHABLE'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:center;">$' . $costosComplementos['COMIDA PARA LLEVAR'] . '</td>';
        echo '<td style="padding:5px;border:1px solid #219653;text-align:center;">COMIDA: $' . $costosComplementos['COMIDA'] . '</td>';
        echo '<td colspan="6" style="padding:5px;border:1px solid #219653;text-align:center;">COSTO TOTAL COMPLEMENTOS: $' . number_format($montoTotalComplementos, 2) . '</td>';
        echo '</tr>';
        
        echo '</table><br><br>';
    }
    
    // CANCELACIONES
    if (count($cancelacionesData) > 0) {
        echo '<table border="1" cellspacing="0" cellpadding="3" style="font-family:Calibri;font-size:11px;border-collapse:collapse;">';
        
        echo '<tr>';
        echo '<th colspan="6" style="background:#cb4335;color:white;font-size:14px;padding:8px;text-align:center;">';
        echo 'REPORTE DE CANCELACIONES (SOLO APROBADAS)';
        echo '</th>';
        echo '</tr>';
        
        echo '<tr style="background:#cb4335;color:white;font-weight:bold;">';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:left;">Nombre del Empleado</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Tipo Consumo</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Fecha</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Cantidad</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Tarifa</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Monto</th>';
        echo '</tr>';
        
        foreach ($cancelacionesData as $cancelacion) {
            $fecha = $cancelacion['FechaStr'] ?? 'Fecha no disponible';
            $tipo = $cancelacion['Tipo_Consumo'];
            $cantidad = $cancelacion['Total'];
            $montoUnitario = $cancelacion['MontoUnitario'] ?? 0;
            $montoTotal = $cancelacion['MontoTotal'] ?? 0;
            $anio = $cancelacion['Anio'] ?? 0;
            $tipoNormalizado = $cancelacion['TipoNormalizado'] ?? '';
            $es2026OMayor = ($anio >= 2026);
            
            if ($es2026OMayor) {
                if (strpos($tipoNormalizado, 'desayuno') !== false) {
                    $tarifaTexto = '$35 (2026+ Desayuno)';
                } elseif (strpos($tipoNormalizado, 'comida') !== false) {
                    $tarifaTexto = '$45 (2026+ Comida)';
                } elseif (strpos($tipoNormalizado, 'ambos') !== false) {
                    $tarifaTexto = '$80 (2026+ Ambos)';
                } else {
                    $tarifaTexto = '$' . $montoUnitario . ' (2026+)';
                }
            } else {
                if (strpos($tipoNormalizado, 'desayuno') !== false || strpos($tipoNormalizado, 'comida') !== false) {
                    $tarifaTexto = '$30 (Antes 2026)';
                } elseif (strpos($tipoNormalizado, 'ambos') !== false) {
                    $tarifaTexto = '$60 (Antes 2026 Ambos)';
                } else {
                    $tarifaTexto = '$' . $montoUnitario . ' (Antes 2026)';
                }
            }
            
            // Estilo para fila completa
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #fadbd8;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #fadbd8;text-align:left;';
            
            if ($cancelacion['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;'; // Amarillo claro para toda la fila
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            echo '<td style="' . $nombreStyle . '">' . htmlspecialchars($cancelacion['Nombre']) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $tipo . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $fecha . '</td>';
            
            // Estilo para celda de cantidad
            $cantidadStyle = $celdaStyle;
            $cantidadStyle .= 'font-weight:bold;color:#e74c3c;';
            
            echo '<td style="' . $cantidadStyle . '">' . $cantidad . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $tarifaTexto . '</td>';
            
            // Estilo para celda de monto
            $montoStyle = $celdaStyle;
            $montoStyle .= 'font-weight:bold;color:#c0392b;';
            
            echo '<td style="' . $montoStyle . '">$' . number_format($montoTotal, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="background:#a93226;color:white;font-weight:bold;">';
        echo '<td colspan="3" style="padding:5px;border:1px solid #922b21;text-align:right;">TOTALES:</td>';
        echo '<td style="padding:5px;border:1px solid #922b21;text-align:center;">' . $totalCancelaciones . '</td>';
        echo '<td style="padding:5px;border:1px solid #922b21;text-align:center;">-</td>';
        echo '<td style="padding:5px;border:1px solid #922b21;text-align:center;">$' . number_format($montoTotalCancelaciones, 2) . '</td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    echo '</body></html>';
    exit;
}

// ==============================================
// INTERFAZ WEB
// ==============================================

if (!$exportarExcel):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Comedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.5;
            padding: 15px;
        }
        
        .container {
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(135deg, #1e3a5c, #2d4a72);
            color: white;
            padding: 24px 30px;
            border-bottom: 3px solid #4299e1;
        }
        
        .header-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-title i {
            font-size: 32px;
            color: #63b3ed;
        }
        
        .period-info {
            background: rgba(255,255,255,0.15);
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-left: 3px solid #4299e1;
        }
        
        /* FILTERS */
        .filters-container {
            padding: 25px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .filter-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d4a72;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #38a169, #2f855a);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.2);
        }
        
        /* MAIN CONTENT */
        .content-container {
            padding: 25px 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e3a5c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-actions {
            display: flex;
            gap: 10px;
        }
        
        /* TABLAS CON MISMO ESTILO */
        .table-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: auto;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        /* ESTILO ÚNICO PARA TODAS LAS TABLAS */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .data-table thead th {
            background: #1e3a5c;
            color: white;
            font-weight: 600;
            text-align: center;
            padding: 14px 10px;
            border: 1px solid #2d4a72;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }
        
        .data-table tbody td {
            padding: 12px 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
            vertical-align: middle;
        }
        
        /* Encabezados específicos por tabla */
        .consumos-table thead th {
            background: #1e3a5c !important;
            border-color: #2d4a72 !important;
        }
        
        .complementos-table thead th {
            background: #229954 !important;
            border-color: #1e8449 !important;
        }
        
        .cancelaciones-table thead th {
            background: #cb4335 !important;
            border-color: #b03a2e !important;
        }
        
        /* Filas alternas */
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .data-table tbody tr:hover {
            background-color: #edf2f7 !important;
        }
        
        /* Filas especiales (nombres en amarillo) - COMPLETAS */
        .row-especial-completa {
            background-color: #FFFACD !important;
        }
        
        .row-especial-completa:hover {
            background-color: #FFF8DC !important;
        }
        
        .nombre-especial {
            background-color: #FFFF00 !important;
            font-weight: bold !important;
            color: #000 !important;
            border-left: 3px solid #FFD700 !important;
        }
        
        .celda-especial {
            background-color: #FFFACD !important;
            border-color: #FFEAA7 !important;
        }
        
        /* Columnas de montos alineadas a la derecha */
        .monto-column {
            text-align: right !important;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        
        .costo-column {
            text-align: right !important;
            font-family: 'Courier New', monospace;
            color: #1e8449;
            font-weight: 600;
        }
        
        /* Columnas fijas para tabla principal */
        .fixed-column {
            background: #2d4a72 !important;
            color: white !important;
            font-weight: 600;
            position: sticky;
            left: 0;
            z-index: 5;
            border-right: 2px solid #1e3a5c;
        }
        
        .fixed-column-header {
            background: #1e3a5c !important;
            color: white !important;
            position: sticky;
            left: 0;
            z-index: 15;
            border-right: 2px solid #2d4a72;
        }
        
        /* Columnas especiales fijas */
        .fixed-column-especial {
            background: #FFD700 !important;
            color: #000 !important;
            font-weight: bold;
            position: sticky;
            left: 0;
            z-index: 5;
            border-right: 2px solid #FFA500;
            border-left: 3px solid #FFA500 !important;
        }
        
        /* Totales */
        .data-table tfoot td {
            background: #1e3a5c;
            color: white;
            font-weight: 600;
            padding: 14px 10px;
            border: 1px solid #2d4a72;
            text-align: center;
        }
        
        .complementos-table tfoot td {
            background: #1e8449 !important;
            border-color: #186a3b !important;
        }
        
        .cancelaciones-table tfoot td {
            background: #a93226 !important;
            border-color: #922b21 !important;
        }
        
        /* ACTIONS */
        .actions-container {
            padding: 25px 30px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        
        /* FOOTER */
        .footer {
            padding: 20px 30px;
            background: #1e3a5c;
            color: #cbd5e0;
            text-align: center;
            font-size: 13px;
            border-top: 3px solid #4299e1;
        }
        
        .footer strong {
            color: white;
        }
        
        /* LEYENDA PARA NOMBRES ESPECIALES */
        .leyenda-container {
            padding: 15px 30px;
            background: #f0f4f8;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .leyenda {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            color: #4a5568;
        }
        
        .leyenda-color {
            width: 20px;
            height: 20px;
            background-color: #FFFF00;
            border: 1px solid #FFD700;
            border-radius: 3px;
        }
        
        .leyenda-color-completa {
            width: 20px;
            height: 20px;
            background-color: #FFFACD;
            border: 1px solid #FFD700;
            border-radius: 3px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .container {
                margin: 0 10px;
            }
            
            .header-title {
                font-size: 24px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header-title {
                font-size: 20px;
            }
            
            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .section-actions {
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .filters-container,
            .content-container {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .header-title {
                font-size: 18px;
            }
            
            .period-info {
                font-size: 13px;
                padding: 10px;
            }
            
            .filters-container,
            .content-container {
                padding: 15px;
            }
        }
        
        /* SCROLLBAR */
        .table-wrapper::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        
        /* ESTILOS ESPECIALES PARA COLUMNAS */
        .column-id {
            width: 70px;
        }
        
        .column-nombre {
            width: 250px;
            text-align: left !important;
        }
        
        .column-monto {
            width: 120px;
        }
        
        .column-costo {
            width: 100px;
        }
        
        .column-total {
            width: 100px;
            font-weight: 600;
        }
        
        .column-fecha {
            width: 90px;
        }
        
        .column-cantidad {
            width: 80px;
        }
        
        /* TARIFAS */
        .tarifas-container {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .tarifas-title {
            font-weight: 600;
            color: #1e8449;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tarifas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
        }
        
        .tarifa-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-radius: 4px;
            border: 1px solid #c6f6d5;
        }
        
        .tarifa-nombre {
            font-weight: 500;
            color: #2d3748;
        }
        
        .tarifa-valor {
            font-weight: 700;
            color: #1e8449;
            font-family: 'Courier New', monospace;
        }
        
        /* RESALTADO COMIDA LLEVAR */
        .comida-llevar {
            background-color: #E8F5E9 !important;
            border-color: #C8E6C9 !important;
            font-weight: bold;
        }
        
        .costo-comida-llevar {
            color: #1B5E20 !important;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-title">
                <i class="fas fa-utensils"></i>
                REPORTE DE COMEDOR - SISTEMA DE CONSUMOS CON COSTOS
            </div>
            <div class="period-info">
                <i class="fas fa-calendar-alt"></i>
                <strong>PERÍODO:</strong> 
                <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> - <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
            </div>
        </div>

        <!-- LEYENDA PARA NOMBRES ESPECIALES -->
        <div class="leyenda-container">
            <div class="leyenda">
                <div class="leyenda-color"></div>
                <span><strong>Nombre especial (amarillo intenso)</strong></span>
            </div>
            <div class="leyenda ms-3">
                <div class="leyenda-color-completa"></div>
                <span><strong>Registro completo especial (amarillo claro)</strong></span>
            </div>
            <div class="leyenda ms-3">
                <i class="fas fa-info-circle text-primary"></i>
                <span><strong>Nombres especiales:</strong> 
                ALEJANDRA CRUZ, ALTA DIRECCION, CRUZ JOSE LUIS, CRUZ RODRIGUEZ ALEJANDRO, JURIDICO, 
                PALMA TREJO SANDY MARK, REYES QUIROZ HILDA, VIGILANCIA, CELAYA YAXI LUIS ENRIQUE, 
                FIRO CORTAZAR FERNANDO, ADAME GARCIA JOSE PAUL, HERRERA CUALI HUGO ALEJANDRO, 
                REYES FONSECA NORMA ANGELICA, JUREZ VZQUEZ MIGUEL ANGEL, SOTO DEL HOYO ISMAEL, 
                GUTIERREZ EZQUIVEL EDGAR, CASTILLO NIETO JESSICA, JOSE FERNANDO OSORIO OJEDA</span>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filters-container">
            <div class="filter-card">
                <div class="filter-title">
                    <i class="fas fa-filter"></i>
                    CONFIGURAR REPORTE
                </div>
                <form method="GET" action="" class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <label for="fechaInicio" class="form-label">Fecha de Inicio</label>
                        <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" 
                               value="<?php echo htmlspecialchars($fechaInicio); ?>" required>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <label for="fechaFin" class="form-label">Fecha de Fin</label>
                        <input type="date" class="form-control" id="fechaFin" name="fechaFin" 
                               value="<?php echo htmlspecialchars($fechaFin); ?>" required>
                    </div>
                    <div class="col-lg-4 col-md-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> GENERAR REPORTE
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($stmt && count($rows) > 0): ?>
            <!-- TABLA PRINCIPAL - CONSUMOS -->
            <div class="content-container">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-table"></i>
                        REGISTRO DETALLADO DE CONSUMOS
                    </div>
                    <div class="section-actions">
                        <a href="?fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>&exportar=excel" 
                           class="btn btn-success">
                            <i class="fas fa-file-excel"></i> EXPORTAR EXCEL
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> IMPRIMIR
                        </button>
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaConsumos" class="data-table consumos-table">
                        <thead>
                            <tr>
                                <?php
                                if (count($rows) > 0) {
                                    $firstRow = $rows[0];
                                    $colIndex = 0;
                                    foreach ($firstRow as $col => $val) {
                                        if (!in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) {
                                            $widthClass = '';
                                            $alignClass = 'text-center';
                                            $fixedClass = '';
                                            
                                            // Determinar clases específicas por columna
                                            if ($col === 'Id_Empleado') {
                                                $widthClass = 'column-id';
                                                $fixedClass = 'fixed-column-header';
                                            } elseif ($col === 'Nombre') {
                                                $widthClass = 'column-nombre';
                                                $alignClass = 'text-left';
                                                $fixedClass = 'fixed-column-header';
                                            } elseif (strpos($col, 'Monto') === 0) {
                                                $widthClass = 'column-monto';
                                                $alignClass = 'text-right';
                                            } elseif (strpos($col, 'Total') === 0) {
                                                $widthClass = 'column-total';
                                            }
                                            
                                            // Nombre corto para encabezados
                                            $displayName = $col;
                                            if ($col === 'TotalConsumos') $displayName = 'TOTAL CONSUMOS';
                                            elseif ($col === 'TotalConsumosDesayunos') $displayName = 'CONS. DESAYUNOS';
                                            elseif ($col === 'TotalConsumosComidas') $displayName = 'CONS. COMIDAS';
                                            elseif ($col === 'MontoConsumosDesayunos') $displayName = 'MONTO CONS. DESAY.';
                                            elseif ($col === 'MontoConsumosComidas') $displayName = 'MONTO CONS. COMIDA';
                                            elseif ($col === 'MontoConsumos') $displayName = 'MONTO TOTAL CONS.';
                                            elseif ($col === 'TotalEntradas') $displayName = 'TOTAL ENTRADAS';
                                            elseif ($col === 'TotalDesayunos') $displayName = 'ENTR. DESAYUNOS';
                                            elseif ($col === 'TotalComidas') $displayName = 'ENTR. COMIDAS';
                                            elseif ($col === 'MontoEntradasDesayunos') $displayName = 'MONTO ENTR. DESAY.';
                                            elseif ($col === 'MontoEntradasComidas') $displayName = 'MONTO ENTR. COMIDA';
                                            elseif ($col === 'MontoEntradasTotal') $displayName = 'MONTO TOTAL ENTR.';
                                            elseif ($col === 'DIFCONSUENTRADAS') $displayName = 'DIF. CONS-ENTR';
                                            elseif ($col === 'MontoQueNosetomo') $displayName = 'MONTO NO TOMADO';
                                            elseif ($col === 'MontoCancelaciones') $displayName = 'MONTO CANCELACIONES';
                                            elseif ($col === 'MontoFinalque') $displayName = 'MONTO FINAL (x2)';
                                            elseif ($col === 'MontoFinalDescontar') $displayName = 'TOTAL A DESCONTAR';
                                            
                                            echo '<th class="' . $fixedClass . ' ' . $widthClass . ' ' . $alignClass . '">' . $displayName . '</th>';
                                            $colIndex++;
                                        }
                                    }
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr class="<?php echo $row['Especial'] ? 'row-especial-completa' : ''; ?>">
                                    <?php foreach ($row as $col => $val): ?>
                                        <?php if (in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) continue; ?>
                                        
                                        <?php
                                        $widthClass = '';
                                        $alignClass = 'text-center';
                                        $fixedClass = '';
                                        $especialClass = '';
                                        $celdaEspecialClass = '';
                                        $displayVal = $val;
                                        
                                        // Determinar clases específicas por columna
                                        if ($col === 'Id_Empleado') {
                                            $widthClass = 'column-id';
                                            $fixedClass = 'fixed-column';
                                            if ($row['Especial']) {
                                                $especialClass = 'nombre-especial';
                                                $celdaEspecialClass = 'celda-especial';
                                            }
                                        } elseif ($col === 'Nombre') {
                                            $widthClass = 'column-nombre';
                                            $alignClass = 'text-left';
                                            $fixedClass = 'fixed-column';
                                            if ($row['Especial']) {
                                                $especialClass = 'nombre-especial';
                                                $celdaEspecialClass = 'celda-especial';
                                            }
                                        } elseif (strpos($col, 'Monto') === 0) {
                                            $widthClass = 'column-monto';
                                            $alignClass = 'text-right monto-column';
                                            $displayVal = '$' . number_format($val, 2);
                                            if ($row['Especial']) {
                                                $celdaEspecialClass = 'celda-especial';
                                            }
                                        } elseif (strpos($col, 'Total') === 0) {
                                            $widthClass = 'column-total';
                                            if ($row['Especial']) {
                                                $celdaEspecialClass = 'celda-especial';
                                            }
                                        } else {
                                            if ($row['Especial']) {
                                                $celdaEspecialClass = 'celda-especial';
                                            }
                                        }
                                        ?>
                                        <td class="<?php echo $fixedClass . ' ' . $especialClass . ' ' . $celdaEspecialClass . ' ' . $widthClass . ' ' . $alignClass; ?>">
                                            <?php echo htmlspecialchars($displayVal); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- COMPLEMENTOS CON COSTOS Y COMIDA PARA LLEVAR -->
            <?php if (count($complementosData) > 0): ?>
            <div class="content-container">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-coffee"></i>
                        REPORTE DE COMPLEMENTOS CON COSTOS - INCLUYE COMIDA PARA LLEVAR
                    </div>
                </div>
                
                <!-- TARIFAS DE COMPLEMENTOS -->
                <div class="tarifas-container">
                    <div class="tarifas-title">
                        <i class="fas fa-tag"></i> TARIFAS DE COMPLEMENTOS
                    </div>
                    <div class="tarifas-grid">
                        <div class="tarifa-item">
                            <span class="tarifa-nombre">CAFÉ O TÉ:</span>
                            <span class="tarifa-valor">$<?php echo $costosComplementos['CAFÉ O TÉ']; ?></span>
                        </div>
                        <div class="tarifa-item">
                            <span class="tarifa-nombre">TORTILLAS:</span>
                            <span class="tarifa-valor">$<?php echo $costosComplementos['TORTILLAS']; ?></span>
                        </div>
                        <div class="tarifa-item">
                            <span class="tarifa-nombre">AGUA:</span>
                            <span class="tarifa-valor">$<?php echo $costosComplementos['AGUA']; ?></span>
                        </div>
                        <div class="tarifa-item">
                            <span class="tarifa-nombre">DESECHABLE:</span>
                            <span class="tarifa-valor">$<?php echo $costosComplementos['DESECHABLE']; ?></span>
                        </div>
                        <div class="tarifa-item">
                            <span class="tarifa-nombre">COMIDA PARA LLEVAR:</span>
                            <span class="tarifa-valor">$<?php echo $costosComplementos['COMIDA PARA LLEVAR']; ?></span>
                        </div>
                     
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaComplementos" class="data-table complementos-table">
                        <thead>
                            <tr>
                                <th class="text-left column-nombre">NOMBRE DEL EMPLEADO</th>
                                <th class="column-cantidad">CAFÉ O TÉ</th>
                                <th class="column-cantidad">TORTILLAS</th>
                                <th class="column-cantidad">AGUA</th>
                                <th class="column-cantidad">DESECHABLE</th>
                                <th class="column-cantidad comida-llevar">COMIDA LLEVAR</th>
                                <th class="column-total">TOTAL</th>
                                <th class="column-costo">COSTO CAFÉ/TÉ</th>
                                <th class="column-costo">COSTO TORTILLAS</th>
                                <th class="column-costo">COSTO AGUA</th>
                                <th class="column-costo">COSTO DESECHABLE</th>
                                <th class="column-costo costo-comida-llevar">COSTO COMIDA LLEVAR</th>
                                <th class="column-monto">TOTAL COSTOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complementosData as $complemento): 
                                $total = $complemento['TOTAL'];
                                $totalCostos = $complemento['MONTO_TOTAL'];
                            ?>
                                <tr class="<?php echo $complemento['Especial'] ? 'row-especial-completa' : ''; ?>">
                                    <td class="text-left column-nombre <?php echo $complemento['Especial'] ? 'nombre-especial' : ''; ?>">
                                        <?php echo htmlspecialchars($complemento['Nombre']); ?>
                                    </td>
                                    <td class="column-cantidad <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $complemento['CAFÉ O TÉ']; ?>
                                    </td>
                                    <td class="column-cantidad <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $complemento['TORTILLAS']; ?>
                                    </td>
                                    <td class="column-cantidad <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $complemento['AGUA']; ?>
                                    </td>
                                    <td class="column-cantidad <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $complemento['DESECHABLE']; ?>
                                    </td>
                                    <td class="column-cantidad comida-llevar <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $complemento['COMIDA PARA LLEVAR'] ?? 0; ?>
                                    </td>
                                    <td class="column-total <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>" style="font-weight:bold;">
                                        <?php echo $total; ?>
                                    </td>
                                    <td class="costo-column <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        $<?php echo number_format($complemento['MONTO_CAFE_TE'], 2); ?>
                                    </td>
                                    <td class="costo-column <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        $<?php echo number_format($complemento['MONTO_TORTILLAS'], 2); ?>
                                    </td>
                                    <td class="costo-column <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        $<?php echo number_format($complemento['MONTO_AGUA'], 2); ?>
                                    </td>
                                    <td class="costo-column <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        $<?php echo number_format($complemento['MONTO_DESECHABLE'], 2); ?>
                                    </td>
                                    <td class="costo-column costo-comida-llevar <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>">
                                        $<?php echo number_format($complemento['MONTO_COMIDA_LLEVAR'], 2); ?>
                                    </td>
                                    <td class="monto-column <?php echo $complemento['Especial'] ? 'celda-especial' : ''; ?>" style="font-weight:bold;">
                                        $<?php echo number_format($totalCostos, 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-right" style="font-weight:bold;">TOTALES CANTIDAD:</td>
                                <td><?php echo number_format($resumenComplementos['CAFÉ O TÉ']); ?></td>
                                <td><?php echo number_format($resumenComplementos['TORTILLAS']); ?></td>
                                <td><?php echo number_format($resumenComplementos['AGUA']); ?></td>
                                <td><?php echo number_format($resumenComplementos['DESECHABLE']); ?></td>
                                <td class="comida-llevar"><?php echo number_format($resumenComplementos['COMIDA PARA LLEVAR']); ?></td>
                                <td><?php echo number_format($totalComplementos); ?></td>
                                <td class="text-right">$<?php echo number_format($totalesCostosComplementos['CAFÉ O TÉ'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($totalesCostosComplementos['TORTILLAS'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($totalesCostosComplementos['AGUA'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($totalesCostosComplementos['DESECHABLE'], 2); ?></td>
                                <td class="text-right costo-comida-llevar">$<?php echo number_format($totalesCostosComplementos['COMIDA PARA LLEVAR'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($montoTotalComplementos, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- CANCELACIONES -->
            <?php if (count($cancelacionesData) > 0): ?>
            <div class="content-container">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-ban"></i>
                        REPORTE DE CANCELACIONES (SOLO APROBADAS)
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaCancelaciones" class="data-table cancelaciones-table">
                        <thead>
                            <tr>
                                <th class="text-left column-nombre">NOMBRE DEL EMPLEADO</th>
                                <th class="column-cantidad">TIPO CONSUMO</th>
                                <th class="column-fecha">FECHA</th>
                                <th class="column-cantidad">CANTIDAD</th>
                                <th>TARIFA</th>
                                <th class="column-monto">MONTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelacionesData as $cancelacion): 
                                $fecha = $cancelacion['FechaStr'] ?? 'Fecha no disponible';
                                $tipo = $cancelacion['Tipo_Consumo'];
                                $cantidad = $cancelacion['Total'];
                                $montoUnitario = $cancelacion['MontoUnitario'] ?? 0;
                                $montoTotal = $cancelacion['MontoTotal'] ?? 0;
                                $anio = $cancelacion['Anio'] ?? 0;
                                $tipoNormalizado = $cancelacion['TipoNormalizado'] ?? '';
                                $es2026OMayor = ($anio >= 2026);
                                
                                if ($es2026OMayor) {
                                    if (strpos($tipoNormalizado, 'desayuno') !== false) {
                                        $tarifaTexto = '$35 (2026+ Desayuno)';
                                    } elseif (strpos($tipoNormalizado, 'comida') !== false) {
                                        $tarifaTexto = '$45 (2026+ Comida)';
                                    } elseif (strpos($tipoNormalizado, 'ambos') !== false) {
                                        $tarifaTexto = '$80 (2026+ Ambos)';
                                    } else {
                                        $tarifaTexto = '$' . $montoUnitario . ' (2026+)';
                                    }
                                } else {
                                    if (strpos($tipoNormalizado, 'desayuno') !== false || strpos($tipoNormalizado, 'comida') !== false) {
                                        $tarifaTexto = '$30 (Antes 2026)';
                                    } elseif (strpos($tipoNormalizado, 'ambos') !== false) {
                                        $tarifaTexto = '$60 (Antes 2026 Ambos)';
                                    } else {
                                        $tarifaTexto = '$' . $montoUnitario . ' (Antes 2026)';
                                    }
                                }
                            ?>
                                <tr class="<?php echo $cancelacion['Especial'] ? 'row-especial-completa' : ''; ?>">
                                    <td class="text-left column-nombre <?php echo $cancelacion['Especial'] ? 'nombre-especial' : ''; ?>">
                                        <?php echo htmlspecialchars($cancelacion['Nombre']); ?>
                                    </td>
                                    <td class="column-cantidad <?php echo $cancelacion['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $tipo; ?>
                                    </td>
                                    <td class="column-fecha <?php echo $cancelacion['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $fecha; ?>
                                    </td>
                                    <td class="column-cantidad <?php echo $cancelacion['Especial'] ? 'celda-especial' : ''; ?>" style="font-weight:bold;">
                                        <?php echo $cantidad; ?>
                                    </td>
                                    <td class="<?php echo $cancelacion['Especial'] ? 'celda-especial' : ''; ?>">
                                        <?php echo $tarifaTexto; ?>
                                    </td>
                                    <td class="column-monto monto-column <?php echo $cancelacion['Especial'] ? 'celda-especial' : ''; ?>" style="font-weight:bold;">
                                        $<?php echo number_format($montoTotal, 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right" style="font-weight:bold;">TOTALES:</td>
                                <td><?php echo number_format($totalCancelaciones); ?></td>
                                <td>-</td>
                                <td>$<?php echo number_format($montoTotalCancelaciones, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ACCIONES -->
            <div class="actions-container">
                <a href="?fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>&exportar=excel" 
                   class="btn btn-success" style="min-width: 250px;">
                    <i class="fas fa-file-excel"></i> EXPORTAR REPORTE COMPLETO (.XLS)
                </a>
            </div>

        <?php elseif ($stmt): ?>
            <!-- SIN DATOS -->
            <div class="content-container">
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-4"></i>
                    <h4 class="mb-3">NO HAY DATOS DISPONIBLES</h4>
                    <p class="text-muted">No se encontraron registros para el rango de fechas seleccionado.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- FOOTER -->
        <div class="footer">
            <p class="mb-2">
                <strong>SISTEMA DE REPORTES - COMEDOR CORPORATIVO</strong>
                <span class="ms-3">INCLUYE COMIDA PARA LLEVAR A $<?php echo $costosComplementos['COMIDA PARA LLEVAR']; ?> PESOS</span>
            </p>
            <p class="mb-0">
                <i class="fas fa-clock"></i> Generado: <?php echo date('d/m/Y H:i:s'); ?>
                <span class="ms-3"><i class="fas fa-star text-warning"></i> Nombres especiales: amarillo intenso (nombre) + amarillo claro (registro completo)</span>
            </p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Inicializar DataTables para tabla principal con scroll horizontal
            var tableConsumos = $('#tablaConsumos').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                order: [[0, 'asc']],
                scrollX: true,
                scrollY: '500px',
                scrollCollapse: true,
                fixedColumns: {
                    leftColumns: 2
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                columnDefs: [
                    {
                        targets: '_all',
                        className: 'dt-center'
                    },
                    {
                        targets: [1], // Columna Nombre
                        className: 'dt-left'
                    }
                ],
                initComplete: function() {
                    // Ajustar ancho de columnas
                    this.api().columns.adjust();
                    
                    // Forzar redibujado para alinear encabezados
                    setTimeout(function() {
                        tableConsumos.columns.adjust();
                        tableConsumos.draw();
                    }, 100);
                }
            });

            // Inicializar DataTables para tabla de complementos
            if ($('#tablaComplementos').length) {
                $('#tablaComplementos').DataTable({
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    pageLength: 15,
                    order: [[12, 'desc']], // Ordenar por TOTAL COSTOS
                    scrollX: true,
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    columnDefs: [
                        {
                            targets: [0], // Columna Nombre
                            className: 'dt-left'
                        },
                        {
                            targets: [7, 8, 9, 10, 11, 12], // Columnas de costos
                            className: 'dt-right'
                        },
                        {
                            targets: [5], // Columna COMIDA LLEVAR
                            className: 'dt-center'
                        }
                    ]
                });
            }

            if ($('#tablaCancelaciones').length) {
                $('#tablaCancelaciones').DataTable({
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    pageLength: 15,
                    order: [[5, 'desc']],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    columnDefs: [
                        {
                            targets: [0], // Columna Nombre
                            className: 'dt-left'
                        },
                        {
                            targets: [5], // Columna Monto
                            className: 'dt-right'
                        }
                    ]
                });
            }

            // Ajustar tabla principal cuando cambia el tamaño de ventana
            $(window).resize(function() {
                if ($.fn.dataTable.isDataTable('#tablaConsumos')) {
                    tableConsumos.columns.adjust();
                }
            });
        });
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