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
    'PERSONAL DE NUEVO INGRESO',
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
    'GUTIERREZ ESQUIVEL EDGAR',
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

// ————— CONSULTA DE CANCELACIONES APROBADAS (con ESTATUS_APARTADO y usuario_aparta) —————
$sqlCancelaciones = "
SELECT 
    Nombre, 
    Tipo_Consumo,
    FECHA,
    CAUSA,
    Descripcion,
    ESTATUS_APARTADO,
    usuario_aparta,
    COUNT(*) as Total 
FROM cancelaciones 
WHERE CONVERT(DATE, FECHA, 102) >= ? 
  AND CONVERT(DATE, FECHA, 102) <= ?
  AND (estatus = 'APROBADO' OR estatus IS NULL OR estatus = '')
GROUP BY Nombre, Tipo_Consumo, FECHA, CAUSA, Descripcion, ESTATUS_APARTADO, usuario_aparta
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

// ————— CONSULTA DE CANCELACIONES RECHAZADAS (con ESTATUS_APARTADO y usuario_aparta) —————
$sqlCancelacionesRechazadas = "
SELECT 
    Nombre, 
    Tipo_Consumo,
    FECHA,
    CAUSA,
    Descripcion,
    ESTATUS_APARTADO,
    usuario_aparta,
    COUNT(*) as Total 
FROM cancelaciones 
WHERE CONVERT(DATE, FECHA, 102) >= ? 
  AND CONVERT(DATE, FECHA, 102) <= ?
  AND estatus = 'RECHAZADO'
GROUP BY Nombre, Tipo_Consumo, FECHA, CAUSA, Descripcion, ESTATUS_APARTADO, usuario_aparta
ORDER BY Nombre, FECHA
";

$stmtCancelacionesRechazadas = sqlsrv_query($conn, $sqlCancelacionesRechazadas, $paramsCancelaciones);

$cancelacionesRechazadasData = [];
$totalCancelacionesRechazadas = 0;
$montoTotalCancelacionesRechazadas = 0;

if ($stmtCancelacionesRechazadas) {
    while ($row = sqlsrv_fetch_array($stmtCancelacionesRechazadas, SQLSRV_FETCH_ASSOC)) {
        $cancelacionesRechazadasData[] = $row;
        $totalCancelacionesRechazadas += $row['Total'];
        
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
        
        $montoTotalCancelacionesRechazadas += $monto * $row['Total'];
        
        $cancelacionesRechazadasData[count($cancelacionesRechazadasData)-1]['MontoUnitario'] = $monto;
        $cancelacionesRechazadasData[count($cancelacionesRechazadasData)-1]['MontoTotal'] = $monto * $row['Total'];
        $cancelacionesRechazadasData[count($cancelacionesRechazadasData)-1]['FechaStr'] = $fechaStr;
        $cancelacionesRechazadasData[count($cancelacionesRechazadasData)-1]['Anio'] = $anioCancelacion;
        $cancelacionesRechazadasData[count($cancelacionesRechazadasData)-1]['TipoNormalizado'] = $tipoNormalizado;
        $cancelacionesRechazadasData[count($cancelacionesRechazadasData)-1]['Especial'] = esNombreEspecial($row['Nombre'], $nombresEspeciales);
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

// Costos por complemento
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

// ————— CONSULTA PRINCIPAL (sin cambios) —————
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
                            WHEN Nombre LIKE ''%esquivel edgar%'' OR nombre LIKE ''%edgar gutie%'' OR nombre LIKE ''%GUTIERREZ EZQUIVEL%'' OR nombre LIKE ''%GUTIERREZ ESQUIVEL%'' THEN ''18'' 
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

// ————— CONSULTA DE NO TOMADOS REALES (sin cambios) —————
$sqlNoTomados = "
WITH NombresExcluir AS (
    SELECT 'ALEJANDRA CRUZ' AS Nombre
    UNION SELECT 'ALTA DIRECCION'
    UNION SELECT 'CRUZ JOSE LUIS'
    UNION SELECT 'CRUZ RODRIGUEZ ALEJANDRO'
    UNION SELECT 'REYES QUIROZ HILDA'
    UNION SELECT 'VIGILANCIA'
    UNION SELECT 'JUREZ VAZQUEZ MIGUEL ANGEL'
    UNION SELECT 'SOTO DEL HOYO ISMAEL'
    UNION SELECT 'PALMA TREJO SANDY MARK'
),

ServidosCorregido AS (
    SELECT 
        CONVERT(DATE, Hora_Entrada, 103) AS Fecha_Consumo,
        CASE 
            WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 'Desayuno'
            ELSE 'Comida'
        END AS Tipo_Comida,
        NE_EXTRAIDO1 AS Id_Empleado,
        c.Nombre AS Nombre_Completo
    FROM (
        SELECT * FROM (
            SELECT 
                a1.Id_Empleado,
                a1.Nombre,
                a1.Area,
                a1.Hora_Entrada,
                a1.Fecha,
                a1.NE_Extraido,
                a1.Tipo_Comida,
                NE_EXTRAIDO1 = CASE 
                    WHEN a1.NE_EXTRAIDO1 IS NULL OR a1.NE_EXTRAIDO1 = '' OR a1.NE_EXTRAIDO1 = 'NULL' 
                    THEN a2.Id_Empleado 
                    ELSE a1.NE_EXTRAIDO1 
                END
            FROM (
                SELECT 
                    *,
                    NE_EXTRAIDO1 = CASE 
                        WHEN Nombre LIKE '%dionisio%' THEN '46'
                        WHEN Nombre LIKE '%esquivel edgar%' OR nombre LIKE '%edgar gutie%' OR nombre LIKE '%GUTIERREZ EZQUIVEL%' OR nombre LIKE '%GUTIERREZ ESQUIVEL%' THEN '18' 
                        WHEN Nombre LIKE '%Luna castro%' THEN '1' 
                        ELSE NE_Extraido 
                    END
                FROM (
                    SELECT 
                        *,
                        LTRIM(RTRIM(
                            CASE 
                                WHEN CHARINDEX('N.E:', nombre) > 0 THEN
                                    SUBSTRING(
                                        nombre,
                                        CHARINDEX('N.E:', nombre) + LEN('N.E:'), 
                                        CASE 
                                            WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('N.E:', nombre)) > 0 THEN
                                                CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('N.E:', nombre)) - (CHARINDEX('N.E:', nombre) + LEN('N.E:'))
                                            ELSE LEN(nombre)
                                        END
                                    )
                                WHEN CHARINDEX('NE: ', nombre) > 0 THEN
                                    SUBSTRING(
                                        nombre,
                                        CHARINDEX('NE: ', nombre) + LEN('NE: '), 
                                        CASE 
                                            WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE: ', nombre)) > 0 THEN
                                                CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE: ', nombre)) - (CHARINDEX('NE: ', nombre) + LEN('NE: '))
                                            ELSE LEN(nombre)
                                        END
                                    )
                                WHEN CHARINDEX('NE:', nombre) > 0 THEN
                                    SUBSTRING(
                                        nombre,
                                        CHARINDEX('NE:', nombre) + LEN('NE:'), 
                                        CASE 
                                            WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE:', nombre)) > 0 THEN
                                                CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('NE:', nombre)) - (CHARINDEX('NE:', nombre) + LEN('NE:'))
                                            ELSE LEN(nombre)
                                        END
                                    )
                                WHEN CHARINDEX('ID:NE0', nombre) > 0 THEN
                                    SUBSTRING(
                                        nombre,
                                        CHARINDEX('ID:NE0', nombre) + LEN('ID:NE0'), 
                                        CASE 
                                            WHEN CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('ID:NE0', nombre)) > 0 THEN
                                                CHARINDEX('DEPARTAMENTO', nombre, CHARINDEX('ID:NE0', nombre)) - (CHARINDEX('ID:NE0', nombre) + LEN('ID:NE0'))
                                            ELSE LEN(nombre)
                                        END
                                    )
                                ELSE NULL
                            END
                        )) AS NE_Extraido,
                        CASE 
                            WHEN CAST(Fecha AS TIME) < '12:00:00' THEN 'Desayuno'
                            ELSE 'Comida'
                        END AS Tipo_Comida
                    FROM Entradas
                    WHERE nombre NOT IN ('.', '', '0%')
                      AND (CONVERT(DATE, Hora_Entrada, 103) >= '$fechaInicio' AND CONVERT(DATE, Hora_Entrada, 103) <= '$fechaFin')
                ) AS a 
            ) AS a1
            LEFT JOIN (
                SELECT 
                    m1.Nombre,
                    m1.NombreV,
                    m2.Id_Empleado,
                    m1.Hora_Entrada,
                    CONVERT(DATE, m1.Hora_Entrada, 103) AS fecha
                FROM (
                    SELECT 
                        Nombre, 
                        LEFT(Nombre, CHARINDEX(' se encuentra registrado para el', Nombre) - 1) AS NombreV,
                        Hora_Entrada
                    FROM Entradas
                    WHERE Nombre LIKE '%se encuentra registrado para el%' 
                      AND (CONVERT(DATE, Hora_Entrada, 103) >= '$fechaInicio' AND CONVERT(DATE, Hora_Entrada, 103) <= '$fechaFin')
                ) AS m1
                LEFT JOIN ConPed m2 ON m1.NombreV = m2.Nombre
            ) AS a2 ON a1.Nombre = a2.Nombre AND a1.Hora_Entrada = a2.Hora_Entrada AND CONVERT(DATE, a1.Hora_Entrada, 103) = a2.fecha
        ) AS a
    ) AS a
    LEFT JOIN ConPed c ON a.NE_EXTRAIDO1 = c.Id_Empleado
    WHERE a.NE_EXTRAIDO1 IS NOT NULL AND a.NE_EXTRAIDO1 != ''
),

Agendados AS (
    SELECT 
        CAST(
            DATEADD(DAY,
                CASE Dia
                    WHEN 'Lunes' THEN 0
                    WHEN 'Martes' THEN 1
                    WHEN 'Miercoles' THEN 2
                    WHEN 'Jueves' THEN 3
                    WHEN 'Viernes' THEN 4
                END,
                Fecha
            ) AS DATE
        ) AS Fecha_Consumo,
        P.Usuario AS Nombre_Empleado,
        P.Id_Empleado,
        Tipo_Comida
    FROM PedidosComida P
    CROSS APPLY (
        VALUES ('Lunes', Lunes), ('Martes', Martes), 
               ('Miercoles', Miercoles), ('Jueves', Jueves), ('Viernes', Viernes)
    ) AS Dias(Dia, Tipo_Comida)
    WHERE Tipo_Comida IS NOT NULL AND Tipo_Comida != ''
      AND NOT EXISTS (
          SELECT 1 FROM NombresExcluir N 
          WHERE P.Usuario LIKE '%' + N.Nombre + '%'
      )
      AND CAST(
            DATEADD(DAY,
                CASE Dia
                    WHEN 'Lunes' THEN 0
                    WHEN 'Martes' THEN 1
                    WHEN 'Miercoles' THEN 2
                    WHEN 'Jueves' THEN 3
                    WHEN 'Viernes' THEN 4
                END,
                Fecha
            ) AS DATE
        ) BETWEEN '$fechaInicio' AND '$fechaFin'
),

CancelacionesCTE AS (
    SELECT 
        CONVERT(DATE, FECHA, 102) AS Fecha_Cancelacion,
        LTRIM(RTRIM(Nombre)) AS Nombre,
        CASE 
            WHEN Tipo_Consumo IN ('Desayuno', 'DESAYUNO') THEN 'Desayuno'
            WHEN Tipo_Consumo IN ('Comida', 'COMIDA') THEN 'Comida'
            WHEN Tipo_Consumo IN ('Ambos', 'AMBOS') THEN 'Ambos'
            ELSE Tipo_Consumo
        END AS Tipo_Consumo
    FROM cancelaciones
    WHERE CONVERT(DATE, FECHA, 102) BETWEEN '$fechaInicio' AND '$fechaFin'
      AND (estatus = 'APROBADO' OR estatus IS NULL OR estatus = '')
      AND Nombre IS NOT NULL AND LTRIM(RTRIM(Nombre)) != ''
),

CancelacionesIndividuales AS (
    SELECT 
        Fecha_Cancelacion,
        Nombre,
        Tipo_Consumo
    FROM CancelacionesCTE
    WHERE Tipo_Consumo IN ('Desayuno', 'Comida')
),

CancelacionesAmbos AS (
    SELECT 
        Fecha_Cancelacion,
        Nombre,
        'Desayuno' AS Tipo_Consumo
    FROM CancelacionesCTE
    WHERE Tipo_Consumo = 'Ambos'
    
    UNION ALL
    
    SELECT 
        Fecha_Cancelacion,
        Nombre,
        'Comida' AS Tipo_Consumo
    FROM CancelacionesCTE
    WHERE Tipo_Consumo = 'Ambos'
),

CancelacionesTotal AS (
    SELECT * FROM CancelacionesIndividuales
    UNION ALL
    SELECT * FROM CancelacionesAmbos
),

AgendadosConNombre AS (
    SELECT DISTINCT
        A.Fecha_Consumo,
        A.Tipo_Comida,
        A.Id_Empleado,
        A.Nombre_Empleado,
        S.Nombre_Completo
    FROM Agendados A
    LEFT JOIN ServidosCorregido S ON A.Id_Empleado = S.Id_Empleado
),

Comparacion AS (
    SELECT 
        A.Fecha_Consumo,
        A.Tipo_Comida,
        A.Id_Empleado,
        A.Nombre_Empleado,
        A.Nombre_Completo,
        CASE WHEN S.Id_Empleado IS NOT NULL THEN 1 ELSE 0 END AS En_Entradas,
        CASE WHEN C.Fecha_Cancelacion IS NOT NULL THEN 1 ELSE 0 END AS En_Cancelaciones,
        CASE 
            WHEN A.Fecha_Consumo < '2026-01-01' THEN 30
            WHEN A.Tipo_Comida = 'Desayuno' THEN 35
            ELSE 45
        END AS Costo,
        S.Id_Empleado AS ID_Extraido,
        A.Nombre_Completo AS Nombre_Extraido
    FROM AgendadosConNombre A
    LEFT JOIN ServidosCorregido S ON 
        A.Fecha_Consumo = S.Fecha_Consumo 
        AND A.Tipo_Comida = S.Tipo_Comida
        AND A.Id_Empleado = S.Id_Empleado
    LEFT JOIN CancelacionesTotal C ON 
        A.Fecha_Consumo = C.Fecha_Cancelacion
        AND A.Tipo_Comida = C.Tipo_Consumo
        AND A.Nombre_Completo = C.Nombre
    WHERE A.Fecha_Consumo BETWEEN '$fechaInicio' AND '$fechaFin'
)

SELECT * FROM (
    SELECT 
        1 AS Tipo_Orden,
        Fecha_Consumo,
        Tipo_Comida,
        Id_Empleado,
        Nombre_Empleado,
        Costo,
        '❌ NO TOMADO REAL' AS Estado_Final,
        En_Entradas,
        En_Cancelaciones,
        ID_Extraido,
        ISNULL(Nombre_Extraido, '') AS Nombre_Extraido,
        0 AS EsTotal
    FROM Comparacion
    WHERE En_Entradas = 0 
      AND En_Cancelaciones = 0
      AND Nombre_Empleado NOT LIKE '%ismael.soto%'
      AND Id_Empleado != '219'
    
    UNION ALL
    
    SELECT 
        2 AS Tipo_Orden,
        NULL AS Fecha_Consumo,
        NULL AS Tipo_Comida,
        NULL AS Id_Empleado,
        CONCAT('TOTAL NO TOMADOS REALES (', '$fechaInicio', ' al ', '$fechaFin', ')') AS Nombre_Empleado,
        SUM(Costo) AS Costo,
        CAST(COUNT(*) AS VARCHAR(10)) AS Estado_Final,
        NULL AS En_Entradas,
        NULL AS En_Cancelaciones,
        NULL AS ID_Extraido,
        '' AS Nombre_Extraido,
        1 AS EsTotal
    FROM Comparacion
    WHERE En_Entradas = 0 
      AND En_Cancelaciones = 0
      AND Nombre_Empleado NOT LIKE '%ismael.soto%'
      AND Id_Empleado != '219'
) AS Resultados
ORDER BY 
    Tipo_Orden,
    Fecha_Consumo, 
    Nombre_Empleado;
";

$stmtNoTomados = sqlsrv_query($conn, $sqlNoTomados);

$noTomadosData = [];
$totalNoTomados = 0;
$montoTotalNoTomados = 0;

if ($stmtNoTomados) {
    while ($row = sqlsrv_fetch_array($stmtNoTomados, SQLSRV_FETCH_ASSOC)) {
        if ($row['EsTotal'] == 1) {
            $totalNoTomados = intval($row['Estado_Final']);
            $montoTotalNoTomados = floatval($row['Costo']);
        } else {
            $row['Especial'] = esNombreEspecial($row['Nombre_Empleado'], $nombresEspeciales);
            $noTomadosData[] = $row;
        }
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
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #ddd;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #ddd;text-align:left;';
            
            if ($row['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;';
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            foreach ($row as $col => $val) {
                if (in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) continue;
                
                $style = $celdaStyle;
                
                if ($col === 'Id_Empleado' || $col === 'Nombre') {
                    $style = $col === 'Nombre' ? $nombreStyle : $celdaStyle;
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
    
    // COMPLEMENTOS
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
            
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #ddd;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #ddd;text-align:left;';
            
            if ($complemento['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;';
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
            
            $totalStyle = $celdaStyle;
            $totalStyle .= 'font-weight:bold;';
            
            echo '<td style="' . $totalStyle . '">' . $total . '</td>';
            
            $costoStyle = $celdaStyle;
            $costoStyle .= 'text-align:right;font-family:Courier New;';
            
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_CAFE_TE'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_TORTILLAS'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_AGUA'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_DESECHABLE'], 2) . '</td>';
            echo '<td style="' . $costoStyle . '">$' . number_format($complemento['MONTO_COMIDA_LLEVAR'], 2) . '</td>';
            
            $totalCostosStyle = $costoStyle;
            $totalCostosStyle .= 'font-weight:bold;color:#1e8449;';
            
            echo '<td style="' . $totalCostosStyle . '">$' . number_format($totalCostos, 2) . '</td>';
            echo '</tr>';
        }
        
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
        
        echo '</table><br><br>';
    }
    
    // CANCELACIONES APROBADAS (con ESTATUS_APARTADO y usuario_aparta)
    if (count($cancelacionesData) > 0) {
        echo '<table border="1" cellspacing="0" cellpadding="3" style="font-family:Calibri;font-size:11px;border-collapse:collapse;">';
        
        echo '<tr>';
        echo '<th colspan="10" style="background:#cb4335;color:white;font-size:14px;padding:8px;text-align:center;">';
        echo 'REPORTE DE CANCELACIONES APROBADAS CON CAUSA Y DESCRIPCIÓN';
        echo '</th>';
        echo '</tr>';
        
        echo '<tr style="background:#cb4335;color:white;font-weight:bold;">';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:left;">Nombre del Empleado</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Tipo Consumo</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Fecha</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:left;">Causa</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:left;">Descripción</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Estatus Apartado</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Usuario Aparta</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Cantidad</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Tarifa</th>';
        echo '<th style="padding:5px;border:1px solid #b03a2e;text-align:center;">Monto</th>';
        echo '</tr>';
        
        foreach ($cancelacionesData as $cancelacion) {
            $fecha = $cancelacion['FechaStr'] ?? 'Fecha no disponible';
            $tipo = $cancelacion['Tipo_Consumo'];
            $causa = $cancelacion['CAUSA'] ?? '-';
            $descripcion = $cancelacion['Descripcion'] ?? '-';
            $estatusApartado = $cancelacion['ESTATUS_APARTADO'] ?? '-';
            $usuarioAparta = $cancelacion['usuario_aparta'] ?? '-';
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
            
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #fadbd8;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #fadbd8;text-align:left;';
            
            if ($cancelacion['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;';
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            echo '<td style="' . $nombreStyle . '">' . htmlspecialchars($cancelacion['Nombre']) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $tipo . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $fecha . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($causa) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($descripcion) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($estatusApartado) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($usuarioAparta) . '</td>';
            
            $cantidadStyle = $celdaStyle;
            $cantidadStyle .= 'font-weight:bold;color:#e74c3c;';
            
            echo '<td style="' . $cantidadStyle . '">' . $cantidad . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $tarifaTexto . '</td>';
            
            $montoStyle = $celdaStyle;
            $montoStyle .= 'font-weight:bold;color:#c0392b;';
            
            echo '<td style="' . $montoStyle . '">$' . number_format($montoTotal, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="background:#a93226;color:white;font-weight:bold;">';
        echo '<td colspan="7" style="padding:5px;border:1px solid #922b21;text-align:right;">TOTALES:</td>';
        echo '<td style="padding:5px;border:1px solid #922b21;text-align:center;">' . $totalCancelaciones . '</td>';
        echo '<td style="padding:5px;border:1px solid #922b21;text-align:center;">-</td>';
        echo '<td style="padding:5px;border:1px solid #922b21;text-align:center;">$' . number_format($montoTotalCancelaciones, 2) . '</td>';
        echo '</tr>';
        
        echo '</table><br><br>';
    }
    
    // CANCELACIONES RECHAZADAS (con ESTATUS_APARTADO y usuario_aparta)
    if (count($cancelacionesRechazadasData) > 0) {
        echo '<table border="1" cellspacing="0" cellpadding="3" style="font-family:Calibri;font-size:11px;border-collapse:collapse;">';
        
        echo '<tr>';
        echo '<th colspan="10" style="background:#7f8c8d;color:white;font-size:14px;padding:8px;text-align:center;">';
        echo 'REPORTE DE CANCELACIONES RECHAZADAS CON CAUSA Y DESCRIPCIÓN';
        echo '</th>';
        echo '</tr>';
        
        echo '<tr style="background:#7f8c8d;color:white;font-weight:bold;">';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:left;">Nombre del Empleado</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Tipo Consumo</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Fecha</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:left;">Causa</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:left;">Descripción</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Estatus Apartado</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Usuario Aparta</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Cantidad</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Tarifa</th>';
        echo '<th style="padding:5px;border:1px solid #6c7a7d;text-align:center;">Monto</th>';
        echo '</tr>';
        
        foreach ($cancelacionesRechazadasData as $cancelacion) {
            $fecha = $cancelacion['FechaStr'] ?? 'Fecha no disponible';
            $tipo = $cancelacion['Tipo_Consumo'];
            $causa = $cancelacion['CAUSA'] ?? '-';
            $descripcion = $cancelacion['Descripcion'] ?? '-';
            $estatusApartado = $cancelacion['ESTATUS_APARTADO'] ?? '-';
            $usuarioAparta = $cancelacion['usuario_aparta'] ?? '-';
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
            
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #d5dbdb;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #d5dbdb;text-align:left;';
            
            if ($cancelacion['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;';
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            echo '<td style="' . $nombreStyle . '">' . htmlspecialchars($cancelacion['Nombre']) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $tipo . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $fecha . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($causa) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($descripcion) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($estatusApartado) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . htmlspecialchars($usuarioAparta) . '</td>';
            
            $cantidadStyle = $celdaStyle;
            $cantidadStyle .= 'font-weight:bold;color:#7f8c8d;';
            
            echo '<td style="' . $cantidadStyle . '">' . $cantidad . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $tarifaTexto . '</td>';
            
            $montoStyle = $celdaStyle;
            $montoStyle .= 'font-weight:bold;color:#7f8c8d;';
            
            echo '<td style="' . $montoStyle . '">$' . number_format($montoTotal, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="background:#6c7a7d;color:white;font-weight:bold;">';
        echo '<td colspan="7" style="padding:5px;border:1px solid #5d6d70;text-align:right;">TOTALES:</td>';
        echo '<td style="padding:5px;border:1px solid #5d6d70;text-align:center;">' . $totalCancelacionesRechazadas . '</td>';
        echo '<td style="padding:5px;border:1px solid #5d6d70;text-align:center;">-</td>';
        echo '<td style="padding:5px;border:1px solid #5d6d70;text-align:center;">$' . number_format($montoTotalCancelacionesRechazadas, 2) . '</td>';
        echo '</tr>';
        
        echo '</table><br><br>';
    }
    
    // NO TOMADOS REALES
    if (count($noTomadosData) > 0) {
        echo '<table border="1" cellspacing="0" cellpadding="3" style="font-family:Calibri;font-size:11px;border-collapse:collapse;">';
        
        echo '<tr>';
        echo '<th colspan="8" style="background:#e67e22;color:white;font-size:14px;padding:8px;text-align:center;">';
        echo 'REPORTE DE NO TOMADOS REALES';
        echo '</th>';
        echo '</tr>';
        
        echo '<tr style="background:#e67e22;color:white;font-weight:bold;">';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:left;">Nombre del Empleado</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:center;">Fecha</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:center;">Tipo</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:center;">ID</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:center;">Estado</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:center;">En Entradas</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:center;">En Cancelaciones</th>';
        echo '<th style="padding:5px;border:1px solid #d35400;text-align:right;">Costo</th>';
        echo '</tr>';
        
        foreach ($noTomadosData as $noTomado) {
            $filaStyle = '';
            $celdaStyle = 'padding:4px;border:1px solid #fadbd8;text-align:center;';
            $nombreStyle = 'padding:4px;border:1px solid #fadbd8;text-align:left;';
            
            if ($noTomado['Especial']) {
                $filaStyle = 'background-color:#FFFFE0;';
                $celdaStyle = 'padding:4px;border:1px solid #FFD700;text-align:center;background-color:#FFFFE0;';
                $nombreStyle = 'padding:4px;border:1px solid #FFD700;text-align:left;background-color:#FFFF00;font-weight:bold;';
            }
            
            echo '<tr style="' . $filaStyle . '">';
            echo '<td style="' . $nombreStyle . '">' . htmlspecialchars($noTomado['Nombre_Empleado']) . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $noTomado['Fecha_Consumo']->format('Y-m-d') . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $noTomado['Tipo_Comida'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $noTomado['Id_Empleado'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . $noTomado['Estado_Final'] . '</td>';
            echo '<td style="' . $celdaStyle . '">' . ($noTomado['En_Entradas'] ? 'Sí' : 'No') . '</td>';
            echo '<td style="' . $celdaStyle . '">' . ($noTomado['En_Cancelaciones'] ? 'Sí' : 'No') . '</td>';
            
            $costoStyle = $celdaStyle;
            $costoStyle .= 'text-align:right;font-weight:bold;color:#e67e22;';
            
            echo '<td style="' . $costoStyle . '">$' . number_format($noTomado['Costo'], 2) . '</td>';
            echo '</tr>';
        }
        
        echo '<tr style="background:#d35400;color:white;font-weight:bold;">';
        echo '<td colspan="7" style="padding:5px;border:1px solid #ba4a00;text-align:right;">TOTAL NO TOMADOS REALES (' . $fechaInicio . ' al ' . $fechaFin . '):</td>';
        echo '<td style="padding:5px;border:1px solid #ba4a00;text-align:right;">$' . number_format($montoTotalNoTomados, 2) . ' (' . $totalNoTomados . ')</td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    echo '</body></html>';
    exit;
}

// ==============================================
// INTERFAZ WEB (con las nuevas columnas en las tablas de cancelaciones)
// ==============================================

if (!$exportarExcel):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
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
        /* ===== VARIABLES ===== */
        :root {
            --primary: #1e3a5c;
            --primary-light: #2d4a72;
            --secondary: #4299e1;
            --success: #229954;
            --success-light: #27ae60;
            --danger: #cb4335;
            --danger-light: #a93226;
            --warning: #e67e22;
            --warning-light: #f39c12;
            --gray-100: #f8f9fa;
            --gray-200: #e2e8f0;
            --gray-600: #718096;
            --gray-900: #1a202c;
            --special-bg: #FFFFE0;
            --special-name: #FFFF00;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            color: var(--gray-900);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: clamp(20px, 4vw, 30px);
            border-bottom: 3px solid var(--secondary);
        }

        .header-content {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header-title i {
            font-size: clamp(2rem, 5vw, 2.5rem);
            color: var(--secondary);
        }

        .header-title h1 {
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 700;
            margin: 0;
        }

        .period-info {
            background: rgba(255, 255, 255, 0.15);
            padding: 12px 20px;
            border-radius: 12px;
            font-size: clamp(0.85rem, 2.5vw, 1rem);
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            border-left: 3px solid var(--secondary);
        }

        /* ===== KPI CARDS ===== */
        .kpi-section {
            padding: 20px clamp(15px, 3vw, 25px);
            background: white;
            border-bottom: 1px solid var(--gray-200);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .kpi-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            background: var(--warning);
            color: white;
        }

        .kpi-icon.warning { background: var(--warning); }
        .kpi-icon.danger { background: var(--danger); }
        .kpi-icon.success { background: var(--success); }
        .kpi-icon.primary { background: var(--primary); }

        .kpi-content {
            flex: 1;
        }

        .kpi-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--gray-600);
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1.2;
        }

        .kpi-subvalue {
            font-size: 0.9rem;
            color: var(--gray-600);
            margin-top: 5px;
        }

        /* ===== FILTROS ===== */
        .filters-section {
            padding: clamp(15px, 3vw, 25px);
            background: var(--gray-100);
            border-bottom: 1px solid var(--gray-200);
        }

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: clamp(15px, 3vw, 20px);
            border: 1px solid var(--gray-200);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-title {
            font-size: clamp(1rem, 3vw, 1.2rem);
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 92, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), var(--success-light));
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 153, 84, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), var(--warning-light));
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
        }

        /* ===== LEYENDA ===== */
        .leyenda-section {
            padding: 15px clamp(15px, 3vw, 25px);
            background: white;
            border-bottom: 1px solid var(--gray-200);
        }

        .leyenda-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: var(--gray-100);
            border-radius: 50px;
            border: 1px solid var(--gray-200);
        }

        .leyenda-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .color-special-name { background: var(--special-name); border: 1px solid #FFD700; }
        .color-special-row { background: var(--special-bg); border: 1px solid #FFD700; }

        /* ===== CONTENIDO ===== */
        .content-section {
            padding: clamp(15px, 3vw, 25px);
        }

        .section-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-200);
        }

        .section-title {
            font-size: clamp(1.1rem, 3vw, 1.3rem);
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ===== TABLAS ===== */
        .table-wrapper {
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            overflow: auto;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .data-table thead th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            padding: 15px 10px;
            border: 1px solid var(--primary-light);
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .data-table tbody td {
            padding: 12px 10px;
            border: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .data-table tbody tr {
            transition: background 0.2s;
        }

        .data-table tbody tr:hover {
            background: rgba(66, 153, 225, 0.05) !important;
        }

        /* ===== COLUMNAS FIJAS SOLO PARA TABLA PRINCIPAL ===== */
        #tablaConsumos thead th:first-child,
        #tablaConsumos thead th:nth-child(2) {
            background: var(--primary);
            position: sticky;
            left: 0;
            z-index: 20;
            border-right: 2px solid var(--primary-light);
        }

        #tablaConsumos tbody td:first-child,
        #tablaConsumos tbody td:nth-child(2) {
            position: sticky;
            left: 0;
            background: white;
            z-index: 5;
            border-right: 2px solid var(--gray-200);
        }

        #tablaConsumos tbody tr:hover td:first-child,
        #tablaConsumos tbody tr:hover td:nth-child(2) {
            background: rgba(66, 153, 225, 0.05);
        }

        #tablaConsumos tbody .row-especial td:first-child,
        #tablaConsumos tbody .row-especial td:nth-child(2) {
            background: var(--special-bg);
        }

        #tablaConsumos tbody .nombre-especial {
            background: var(--special-name) !important;
        }

        /* ===== ESTILOS ESPECIALES ===== */
        .row-especial {
            background-color: var(--special-bg) !important;
        }

        .nombre-especial {
            background-color: var(--special-name) !important;
            font-weight: bold !important;
        }

        .celda-especial {
            background-color: var(--special-bg) !important;
        }

        .monto-column {
            text-align: right !important;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .costo-column {
            text-align: right !important;
            font-family: 'Courier New', monospace;
            color: var(--success);
            font-weight: 600;
        }

        .column-nombre {
            min-width: 250px;
            text-align: left !important;
        }

        .column-descripcion {
            min-width: 200px;
            text-align: left !important;
        }

        .column-causa {
            min-width: 150px;
            text-align: left !important;
        }

        .column-monto {
            min-width: 100px;
        }

        .column-cantidad {
            min-width: 80px;
        }

        .column-fecha {
            min-width: 90px;
        }

        .column-estatus-apartado {
            min-width: 120px;
            text-align: center !important;
        }

        .column-usuario-aparta {
            min-width: 120px;
            text-align: center !important;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: rgba(255, 255, 255, 0.9);
            padding: 20px clamp(15px, 3vw, 25px);
            text-align: center;
            border-top: 3px solid var(--secondary);
        }

        /* ===== TARIFAS ===== */
        .tarifas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            padding: 15px;
            background: #f0fff4;
            border-radius: 12px;
            border: 1px solid #c6f6d5;
            margin-bottom: 20px;
        }

        .tarifa-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid #c6f6d5;
        }

        .tarifa-nombre {
            font-weight: 600;
            color: var(--gray-900);
        }

        .tarifa-valor {
            font-weight: 700;
            color: var(--success);
        }

        /* ===== ACCIONES FLOTANTES ===== */
        .floating-actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-floating {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .btn-floating:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .btn-floating.excel {
            background: var(--success);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1200px) {
            body { padding: 15px; }
        }

        @media (max-width: 992px) {
            .floating-actions {
                bottom: 20px;
                right: 20px;
            }
            
            .btn-floating {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            
            .header-content { flex-direction: column; align-items: flex-start; }
            
            .filter-grid { grid-template-columns: 1fr; }
            
            .section-header { flex-direction: column; align-items: flex-start; }
            
            .section-actions { width: 100%; }
            
            .section-actions .btn { width: 100%; }
            
            .data-table { font-size: 0.75rem; }
            
            .data-table th, 
            .data-table td { padding: 8px 5px; }
            
            .column-nombre { min-width: 180px; }
            
            .column-descripcion { min-width: 150px; }
            
            .floating-actions {
                bottom: 15px;
                right: 15px;
            }
            
            .btn-floating {
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }

            #tablaConsumos thead th:first-child,
            #tablaConsumos thead th:nth-child(2),
            #tablaConsumos tbody td:first-child,
            #tablaConsumos tbody td:nth-child(2) {
                position: sticky;
            }
        }

        @media (max-width: 576px) {
            .data-table { font-size: 0.7rem; }
            
            .data-table th, 
            .data-table td { padding: 6px 3px; }
            
            .column-nombre { min-width: 150px; }
            
            .column-descripcion { min-width: 120px; }
            
            .column-causa { min-width: 100px; }
            
            .leyenda-grid { flex-direction: column; gap: 10px; }
            
            .leyenda-item { width: 100%; }
        }

        /* ===== SCROLLBARS ===== */
        .table-wrapper::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 5px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: var(--gray-600);
            border-radius: 5px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-utensils"></i>
                    <h1>REPORTE DE COMEDOR</h1>
                </div>
                <div class="period-info">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>PERÍODO:</strong> 
                    <?php echo date('d/m/Y', strtotime($fechaInicio)); ?> - 
                    <?php echo date('d/m/Y', strtotime($fechaFin)); ?>
                </div>
            </div>
        </div>

        <!-- KPI CARDS -->
        <?php if ($stmt && count($rows) > 0): ?>
        <div class="kpi-section">
            <div class="kpi-grid">
                <!-- Card No Tomados -->
                <div class="kpi-card">
                    <div class="kpi-icon warning">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">No Tomados Reales</div>
                        <div class="kpi-value"><?php echo number_format($totalNoTomados); ?></div>
                        <div class="kpi-subvalue">$<?php echo number_format($montoTotalNoTomados, 2); ?></div>
                    </div>
                </div>

                <!-- Card Cancelaciones Aprobadas -->
                <div class="kpi-card">
                    <div class="kpi-icon danger">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Cancelaciones Aprobadas</div>
                        <div class="kpi-value"><?php echo number_format($totalCancelaciones); ?></div>
                        <div class="kpi-subvalue">$<?php echo number_format($montoTotalCancelaciones, 2); ?></div>
                    </div>
                </div>

                <!-- Card Total Consumos -->
                <div class="kpi-card">
                    <div class="kpi-icon success">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Empleados</div>
                        <div class="kpi-value"><?php echo number_format($totalEmpleados); ?></div>
                    </div>
                </div>

                <!-- Card Complementos -->
                <div class="kpi-card">
                    <div class="kpi-icon primary">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Complementos</div>
                        <div class="kpi-value"><?php echo number_format($totalComplementos); ?></div>
                        <div class="kpi-subvalue">$<?php echo number_format($montoTotalComplementos, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- LEYENDA -->
        <div class="leyenda-section">
            <div class="leyenda-grid">
                <div class="leyenda-item">
                    <div class="leyenda-color color-special-name"></div>
                    <span><strong>Nombre especial (amarillo intenso)</strong></span>
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color color-special-row"></div>
                    <span><strong>Registro completo especial (amarillo claro)</strong></span>
                </div>
                <div class="leyenda-item">
                    <i class="fas fa-info-circle text-primary"></i>
                    <span class="text-truncate"><strong>Nombres especiales:</strong> <?php echo count($nombresEspeciales); ?> registros</span>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="filters-section">
            <div class="filter-card">
                <div class="filter-title">
                    <i class="fas fa-sliders-h"></i>
                    CONFIGURAR REPORTE
                </div>
                <form method="GET" action="" class="filter-grid">
                    <div class="form-group">
                        <label for="fechaInicio">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" 
                               value="<?php echo htmlspecialchars($fechaInicio); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fechaFin">Fecha Fin</label>
                        <input type="date" class="form-control" id="fechaFin" name="fechaFin" 
                               value="<?php echo htmlspecialchars($fechaFin); ?>" required>
                    </div>
                    <div class="form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> GENERAR REPORTE
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($stmt && count($rows) > 0): ?>
            <!-- TABLA PRINCIPAL CON COLUMNAS FIJAS -->
            <div class="content-section">
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
                    <table id="tablaConsumos" class="data-table">
                        <thead>
                            <tr>
                                <?php
                                if (count($rows) > 0) {
                                    $firstRow = $rows[0];
                                    foreach ($firstRow as $col => $val) {
                                        if (!in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) {
                                            $displayName = $col;
                                            if ($col === 'Id_Empleado') $displayName = 'ID EMPLEADO';
                                            elseif ($col === 'Nombre') $displayName = 'NOMBRE';
                                            elseif ($col === 'TotalConsumos') $displayName = 'TOTAL';
                                            elseif ($col === 'MontoConsumos') $displayName = 'MONTO TOTAL';
                                            elseif ($col === 'MontoEntradasTotal') $displayName = 'MONTO ENTRADAS';
                                            elseif ($col === 'MontoFinalDescontar') $displayName = 'TOTAL A DESCONTAR';
                                            
                                            echo '<th>' . $displayName . '</th>';
                                        }
                                    }
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr class="<?php echo $row['Especial'] ? 'row-especial' : ''; ?>">
                                    <?php foreach ($row as $col => $val): ?>
                                        <?php if (in_array($col, ['Empleado', 'NombreEntradas', 'Especial'])) continue; ?>
                                        
                                        <?php
                                        $class = '';
                                        $displayVal = $val;
                                        
                                        if ($col === 'Id_Empleado' || $col === 'Nombre') {
                                            $class = $row['Especial'] && $col === 'Nombre' ? 'nombre-especial' : '';
                                        } elseif (strpos($col, 'Monto') === 0) {
                                            $class = 'monto-column';
                                            $displayVal = '$' . number_format($val, 2);
                                        } elseif ($row['Especial']) {
                                            $class = 'celda-especial';
                                        }
                                        ?>
                                        <td class="<?php echo $class; ?>">
                                            <?php echo htmlspecialchars($displayVal); ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- NO TOMADOS REALES -->
            <?php if (count($noTomadosData) > 0): ?>
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-times-circle text-warning"></i>
                        NO TOMADOS REALES
                        <span class="badge bg-warning ms-2">Total: <?php echo $totalNoTomados; ?> | $<?php echo number_format($montoTotalNoTomados, 2); ?></span>
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaNoTomados" class="data-table">
                        <thead>
                            <tr style="background:#e67e22;">
                                <th class="column-nombre">NOMBRE EMPLEADO</th>
                                <th class="column-fecha">FECHA</th>
                                <th>TIPO</th>
                                <th>ID</th>
                                <th>ESTADO</th>
                                <th>EN ENTRADAS</th>
                                <th>EN CANCELACIONES</th>
                                <th class="monto-column">COSTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noTomadosData as $noTomado): ?>
                                <tr class="<?php echo $noTomado['Especial'] ? 'row-especial' : ''; ?>">
                                    <td class="column-nombre <?php echo $noTomado['Especial'] ? 'nombre-especial' : ''; ?>">
                                        <?php echo htmlspecialchars($noTomado['Nombre_Empleado']); ?>
                                    </td>
                                    <td><?php echo $noTomado['Fecha_Consumo']->format('Y-m-d'); ?></td>
                                    <td><?php echo $noTomado['Tipo_Comida']; ?></td>
                                    <td><?php echo $noTomado['Id_Empleado']; ?></td>
                                    <td><?php echo $noTomado['Estado_Final']; ?></td>
                                    <td><?php echo $noTomado['En_Entradas'] ? 'Sí' : 'No'; ?></td>
                                    <td><?php echo $noTomado['En_Cancelaciones'] ? 'Sí' : 'No'; ?></td>
                                    <td class="monto-column">$<?php echo number_format($noTomado['Costo'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- COMPLEMENTOS -->
            <?php if (count($complementosData) > 0): ?>
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-coffee"></i>
                        COMPLEMENTOS CON COSTOS
                    </div>
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
                        <span class="tarifa-nombre">COMIDA LLEVAR:</span>
                        <span class="tarifa-valor">$<?php echo $costosComplementos['COMIDA PARA LLEVAR']; ?></span>
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaComplementos" class="data-table">
                        <thead>
                            <tr>
                                <th class="column-nombre">NOMBRE</th>
                                <th>CAFÉ/TÉ</th>
                                <th>TORTILLAS</th>
                                <th>AGUA</th>
                                <th>DESECHABLE</th>
                                <th>COMIDA LLEVAR</th>
                                <th>TOTAL</th>
                                <th class="costo-column">$ CAFÉ/TÉ</th>
                                <th class="costo-column">$ TORTILLAS</th>
                                <th class="costo-column">$ AGUA</th>
                                <th class="costo-column">$ DESECHABLE</th>
                                <th class="costo-column">$ COMIDA LLEVAR</th>
                                <th class="costo-column">TOTAL $</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complementosData as $complemento): ?>
                                <tr class="<?php echo $complemento['Especial'] ? 'row-especial' : ''; ?>">
                                    <td class="column-nombre <?php echo $complemento['Especial'] ? 'nombre-especial' : ''; ?>">
                                        <?php echo htmlspecialchars($complemento['Nombre']); ?>
                                    </td>
                                    <td><?php echo $complemento['CAFÉ O TÉ']; ?></td>
                                    <td><?php echo $complemento['TORTILLAS']; ?></td>
                                    <td><?php echo $complemento['AGUA']; ?></td>
                                    <td><?php echo $complemento['DESECHABLE']; ?></td>
                                    <td><?php echo $complemento['COMIDA PARA LLEVAR'] ?? 0; ?></td>
                                    <td><strong><?php echo $complemento['TOTAL']; ?></strong></td>
                                    <td class="costo-column">$<?php echo number_format($complemento['MONTO_CAFE_TE'], 2); ?></td>
                                    <td class="costo-column">$<?php echo number_format($complemento['MONTO_TORTILLAS'], 2); ?></td>
                                    <td class="costo-column">$<?php echo number_format($complemento['MONTO_AGUA'], 2); ?></td>
                                    <td class="costo-column">$<?php echo number_format($complemento['MONTO_DESECHABLE'], 2); ?></td>
                                    <td class="costo-column">$<?php echo number_format($complemento['MONTO_COMIDA_LLEVAR'], 2); ?></td>
                                    <td class="costo-column">$<?php echo number_format($complemento['MONTO_TOTAL'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- CANCELACIONES APROBADAS (con ESTATUS_APARTADO y usuario_aparta) -->
            <?php if (count($cancelacionesData) > 0): ?>
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-ban text-danger"></i>
                        CANCELACIONES APROBADAS
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaCancelaciones" class="data-table">
                        <thead>
                            <tr style="background:#cb4335;">
                                <th class="column-nombre">NOMBRE</th>
                                <th>TIPO</th>
                                <th class="column-fecha">FECHA</th>
                                <th class="column-causa">CAUSA</th>
                                <th class="column-descripcion">DESCRIPCIÓN</th>
                                <th class="column-estatus-apartado">ESTATUS APARTADO</th>
                                <th class="column-usuario-aparta">USUARIO APARTA</th>
                                <th>CANT</th>
                                <th>TARIFA</th>
                                <th class="monto-column">MONTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelacionesData as $cancelacion): ?>
                                <tr class="<?php echo $cancelacion['Especial'] ? 'row-especial' : ''; ?>">
                                    <td class="column-nombre <?php echo $cancelacion['Especial'] ? 'nombre-especial' : ''; ?>">
                                        <?php echo htmlspecialchars($cancelacion['Nombre']); ?>
                                    </td>
                                    <td><?php echo $cancelacion['Tipo_Consumo']; ?></td>
                                    <td><?php echo $cancelacion['FechaStr']; ?></td>
                                    <td class="column-causa"><?php echo htmlspecialchars($cancelacion['CAUSA'] ?? '-'); ?></td>
                                    <td class="column-descripcion"><?php echo htmlspecialchars($cancelacion['Descripcion'] ?? '-'); ?></td>
                                    <td class="column-estatus-apartado"><?php echo htmlspecialchars($cancelacion['ESTATUS_APARTADO'] ?? '-'); ?></td>
                                    <td class="column-usuario-aparta"><?php echo htmlspecialchars($cancelacion['usuario_aparta'] ?? '-'); ?></td>
                                    <td><strong><?php echo $cancelacion['Total']; ?></strong></td>
                                    <td>$<?php echo $cancelacion['MontoUnitario']; ?></td>
                                    <td class="monto-column">$<?php echo number_format($cancelacion['MontoTotal'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- CANCELACIONES RECHAZADAS (con ESTATUS_APARTADO y usuario_aparta) -->
            <?php if (count($cancelacionesRechazadasData) > 0): ?>
            <div class="content-section">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fas fa-ban text-secondary"></i>
                        CANCELACIONES RECHAZADAS
                    </div>
                </div>
                
                <div class="table-wrapper">
                    <table id="tablaCancelacionesRechazadas" class="data-table">
                        <thead>
                            <tr style="background:#7f8c8d;">
                                <th class="column-nombre">NOMBRE</th>
                                <th>TIPO</th>
                                <th class="column-fecha">FECHA</th>
                                <th class="column-causa">CAUSA</th>
                                <th class="column-descripcion">DESCRIPCIÓN</th>
                                <th class="column-estatus-apartado">ESTATUS APARTADO</th>
                                <th class="column-usuario-aparta">USUARIO APARTA</th>
                                <th>CANT</th>
                                <th>TARIFA</th>
                                <th class="monto-column">MONTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelacionesRechazadasData as $cancelacion): ?>
                                <tr class="<?php echo $cancelacion['Especial'] ? 'row-especial' : ''; ?>">
                                    <td class="column-nombre <?php echo $cancelacion['Especial'] ? 'nombre-especial' : ''; ?>">
                                        <?php echo htmlspecialchars($cancelacion['Nombre']); ?>
                                    </td>
                                    <td><?php echo $cancelacion['Tipo_Consumo']; ?></td>
                                    <td><?php echo $cancelacion['FechaStr']; ?></td>
                                    <td class="column-causa"><?php echo htmlspecialchars($cancelacion['CAUSA'] ?? '-'); ?></td>
                                    <td class="column-descripcion"><?php echo htmlspecialchars($cancelacion['Descripcion'] ?? '-'); ?></td>
                                    <td class="column-estatus-apartado"><?php echo htmlspecialchars($cancelacion['ESTATUS_APARTADO'] ?? '-'); ?></td>
                                    <td class="column-usuario-aparta"><?php echo htmlspecialchars($cancelacion['usuario_aparta'] ?? '-'); ?></td>
                                    <td><strong><?php echo $cancelacion['Total']; ?></strong></td>
                                    <td>$<?php echo $cancelacion['MontoUnitario']; ?></td>
                                    <td class="monto-column">$<?php echo number_format($cancelacion['MontoTotal'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($stmt): ?>
            <!-- SIN DATOS -->
            <div class="content-section">
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                    <h4 class="mb-3">NO HAY DATOS DISPONIBLES</h4>
                    <p class="text-muted">No se encontraron registros para el rango de fechas seleccionado.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- FOOTER -->
        <div class="footer">
            <p class="mb-2">
                <strong>SISTEMA DE REPORTES - COMEDOR CORPORATIVO</strong>
                <span class="ms-3">COMIDA PARA LLEVAR: $<?php echo $costosComplementos['COMIDA PARA LLEVAR']; ?></span>
            </p>
            <p class="mb-0">
                <i class="fas fa-clock"></i> Generado: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>

    <!-- ACCIONES FLOTANTES -->
    <div class="floating-actions">
        <a href="?fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>&exportar=excel" 
           class="btn-floating excel" title="Exportar a Excel">
            <i class="fas fa-file-excel"></i>
        </a>
        <button class="btn-floating" onclick="window.print()" title="Imprimir">
            <i class="fas fa-print"></i>
        </button>
        <button class="btn-floating" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Ir arriba">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <script>
        $(document).ready(function() {
            // Configuración común para DataTables
            const dtConfig = {
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                scrollX: true,
                scrollY: '500px',
                scrollCollapse: true,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            };

            // Inicializar DataTables para tabla principal
            if ($('#tablaConsumos').length) {
                $('#tablaConsumos').DataTable({
                    ...dtConfig,
                    order: [[0, 'asc']],
                    initComplete: function() {
                        this.api().columns.adjust();
                    }
                });
            }

            if ($('#tablaNoTomados').length) {
                $('#tablaNoTomados').DataTable({
                    ...dtConfig,
                    order: [[7, 'desc']],
                    pageLength: 15
                });
            }

            if ($('#tablaComplementos').length) {
                $('#tablaComplementos').DataTable({
                    ...dtConfig,
                    order: [[12, 'desc']],
                    pageLength: 15
                });
            }

            if ($('#tablaCancelaciones').length) {
                $('#tablaCancelaciones').DataTable({
                    ...dtConfig,
                    order: [[9, 'desc']],
                    pageLength: 15
                });
            }

            if ($('#tablaCancelacionesRechazadas').length) {
                $('#tablaCancelacionesRechazadas').DataTable({
                    ...dtConfig,
                    order: [[9, 'desc']],
                    pageLength: 15
                });
            }

            // Ajustar columnas al cambiar tamaño
            $(window).resize(function() {
                if ($.fn.dataTable.isDataTable('#tablaConsumos')) {
                    $('#tablaConsumos').DataTable().columns.adjust();
                }
            });
        });
    </script>

    <?php
    // Liberar recursos
    if (isset($stmt)) sqlsrv_free_stmt($stmt);
    if (isset($stmtCancelaciones)) sqlsrv_free_stmt($stmtCancelaciones);
    if (isset($stmtCancelacionesRechazadas)) sqlsrv_free_stmt($stmtCancelacionesRechazadas);
    if (isset($stmtComplementos)) sqlsrv_free_stmt($stmtComplementos);
    if (isset($stmtNoTomados)) sqlsrv_free_stmt($stmtNoTomados);
    sqlsrv_close($conn);
    ?>
</body>
</html>
<?php endif; ?>