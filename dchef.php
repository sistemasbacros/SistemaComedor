<?php
// =============================================
// DASHBOARD CHEF - VERSIÓN FINAL COMPLETA Y FUNCIONAL
// CON BADGES DE ESTADO CORREGIDOS (DESAYUNO/COMIDA AGENDADO)
// =============================================

session_start();

// Configuración de conexión a la base de datos
require_once __DIR__ . '/config/database.php';

// Función para obtener conexión
function getConnection() {
    $conn = getComedorConnection();
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
    }
    return $conn;
}

// =============================================
// OBTENER PEDIDOS DE LA SEMANA ACTUAL
// =============================================

function getPedidosSemana() {
    $conn = getConnection();
    
    $sql = "
    WITH SemanaActual AS (
        SELECT *
        FROM PedidosComida
        WHERE Fecha = DATEADD(WEEK, DATEDIFF(WEEK, 0, GETDATE()), 0)
    ),
    Unpivoted AS (
        SELECT 
            Id_Empleado,
            Usuario,
            Fecha,
            Dia,
            Consumo
        FROM SemanaActual
        UNPIVOT (
            Consumo FOR Dia IN (Lunes, Martes, Miercoles, Jueves, Viernes)
        ) u
    )
    SELECT 
        u.Id_Empleado,
        u.Usuario,
        c.Nombre,
        CONVERT(DATE,
            DATEADD(DAY,
                CASE u.Dia
                    WHEN 'Lunes' THEN 0
                    WHEN 'Martes' THEN 1
                    WHEN 'Miercoles' THEN 2
                    WHEN 'Jueves' THEN 3
                    WHEN 'Viernes' THEN 4
                END,
                u.Fecha
            )
        ) AS FechaReal,
        u.Consumo
    FROM Unpivoted u
    LEFT JOIN ConPed c 
        ON u.Usuario = c.Usuario
    WHERE u.Consumo IS NOT NULL
    ORDER BY u.Usuario, FechaReal";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $pedidos = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $pedidos[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    sqlsrv_close($conn);
    return $pedidos;
}

// =============================================
// DEFINICIÓN DE HORARIOS Y BLOQUES
// =============================================

$horariosComedor = [
    'desayuno' => [
        'bloque1' => ['inicio' => '08:30', 'fin' => '08:50', 'nombre' => 'BLOQUE 1 - Desayuno'],
        'bloque2' => ['inicio' => '08:55', 'fin' => '09:15', 'nombre' => 'BLOQUE 2 - Desayuno'],
        'bloque3' => ['inicio' => '09:20', 'fin' => '09:40', 'nombre' => 'BLOQUE 3 - Desayuno'],
        'bloque4' => ['inicio' => '09:45', 'fin' => '10:05', 'nombre' => 'BLOQUE 4 - Desayuno']
    ],
    'comida' => [
        'bloque1' => ['inicio' => '13:00', 'fin' => '13:30', 'nombre' => 'BLOQUE 1 - Comida'],
        'bloque2' => ['inicio' => '13:35', 'fin' => '14:05', 'nombre' => 'BLOQUE 2 - Comida'],
        'bloque3' => ['inicio' => '14:10', 'fin' => '14:40', 'nombre' => 'BLOQUE 3 - Comida'],
        'bloque4' => ['inicio' => '14:45', 'fin' => '15:15', 'nombre' => 'BLOQUE 4 - Comida']
    ]
];

// LISTA COMPLETA DE PERSONAL POR BLOQUE (MANTENIENDO TU LISTA ORIGINAL)
$personalBloque1 = [
    ['nombre' => 'RIVERA SOLANO MARCELINO', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ANTONIO SANCHEZ ARIEL', 'depto' => 'SISTEMAS'],
    ['nombre' => 'ARIZMENDI GOMEZ AIDA YAMILETH', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ARRIAGA GOMEZ ANA MARIA', 'depto' => 'FINANZAS'],
    ['nombre' => 'CARBAJAL VALENZUELA LORENZO ARMANDO', 'depto' => 'GESTION'],
    ['nombre' => 'CARMONA NAVA ROSA', 'depto' => 'FACTURACION'],
    ['nombre' => 'CORTES MORALES MARIA JOVITA', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'HERRERA HERNANDEZ MARIANA CITLALI', 'depto' => 'FINANZAS'],
    ['nombre' => 'LUNA CASTRO MIGUEL ANGEL', 'depto' => 'GESTION'],
    ['nombre' => 'NAVA GARCIA MONSERRAT', 'depto' => 'OPERACIONES'],
    ['nombre' => 'PEREZ MIRANDA ROJER MILTON', 'depto' => 'OPERACIONES'],
    ['nombre' => 'PIÑA ROMERO RUBEN JESUS', 'depto' => 'OPERACIONES'],
    ['nombre' => 'SANCHEZ MENDOZA IVAN', 'depto' => 'LICITACIONES'],
    ['nombre' => 'URIBE FERNANDEZ ADRIANA CECILIA', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'JORGE ALEJANDRO VELASCO PEREZ', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'XOLOCOTZI GONZALEZ ELIZABETH', 'depto' => 'SERVICIOS GENERALES'],
    ['nombre' => 'BENITEZ PADILLA LILIANA', 'depto' => 'ELEVADORES'],
    ['nombre' => 'DE NOVA GARDUÑO EDUARDO', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'SALINAS GONZALES YASMIN', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'DURAN MIRANDA ELISA SARAIH', 'depto' => 'OPERACIONES'],
    ['nombre' => 'RODRIGUEZ GARIBAY KAREN', 'depto' => 'OPERACIONES'],
    ['nombre' => 'MANCILLA VELAZQUEZ ELIZABETH JOHANA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'REYES REBOLLAR JESUS EMMANUEL', 'depto' => 'AUDITORIA'],
    ['nombre' => 'PAMELA LIZBETH GARCIA CRUZ', 'depto' => 'OBRA CIVIL'],
    ['nombre' => 'BORIS KELVIN RAMIREZ NEYRA', 'depto' => 'TI'],
    ['nombre' => 'JESUS ARMANDO GONZALEZ ARIAS', 'depto' => 'LICITACIONES'],
    ['nombre' => 'ANGEL DE JESUS CONTRERAS MORENO', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'VERONICA GARCIA ACEVEDO', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'PEREZ SANCHEZ ALAM NOE', 'depto' => 'ADMINISTRACION']
];

$personalBloque2 = [
    ['nombre' => 'AGUIRRE FLORES OFELIA', 'depto' => 'SERVICIOS GENERALES'],
    ['nombre' => 'AVILES AGUIRRE JAZMIN', 'depto' => 'FINANZAS'],
    ['nombre' => 'BECERRIL GARCIA LUIS MIGUEL', 'depto' => 'SISTEMAS'],
    ['nombre' => 'BERNAL JUAREZ MARISOL', 'depto' => 'OPERACIONES'],
    ['nombre' => 'CABALLERO LUNA ROSA ELENA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'CERNA HERNANDEZ BEATRIZ ALEJANDRA', 'depto' => 'LICITACIONES'],
    ['nombre' => 'CIENEGA JASSO MIRIAM', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'GALICIA DOMINGUEZ ADRIANA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'MAIRA RAMIREZ VELAZQUEZ', 'depto' => 'FINANZAS'],
    ['nombre' => 'REYES FONSECA NORMA ANGELICA', 'depto' => 'SERVICIOS GENERALES'],
    ['nombre' => 'VIOLETA URIA ESTRADA', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'CELAYA YAXI LUIS ENRIQUE', 'depto' => 'FINANZAS'],
    ['nombre' => 'FIRO CORTAZAR FERNANDO', 'depto' => 'OPERACIONES'],
    ['nombre' => 'VENTOLERO GUADARRAMA JAQUELINE', 'depto' => 'AUDITORIA'],
    ['nombre' => 'MEDINA MALDONADO ARMANDO KHIN', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'FABELA LOPEZ JUAN DANIEL', 'depto' => 'CONTABILIDAD'],
    ['nombre' => 'ADAME GARCIA JOSE PAUL', 'depto' => 'OPERACIONES'],
    ['nombre' => 'HERRERA CUALI HUGO ALEJANDRO', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'COLIN ESQUIVEL CESAR ALBERTO', 'depto' => 'AUDITORIA'],
    ['nombre' => 'SALGADO OROZCO SERGIO JAVIER', 'depto' => 'OPERACIONES'],
    ['nombre' => 'GARCIA CARDENAS DIANA ESTEFANIA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'GONZALEZ HERNANDEZ TANIA', 'depto' => 'CONTABILIDAD'],
    ['nombre' => 'GONZALEZ VELAZQUEZ MIGUEL ANTONIO', 'depto' => 'ADMINISTRACION']
];

$personalBloque3 = [
    ['nombre' => 'SAHIANA MELINA BAZAN', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'MEDINA DE JESUS JOSE LUIS', 'depto' => 'ALMACEN'],
    ['nombre' => 'CARLA JAQUELINE PRADO SANCHEZ', 'depto' => 'AUDITORIA'],
    ['nombre' => 'ESQUIVEL MARTINEZ LILIAN', 'depto' => 'ELEVADORES'],
    ['nombre' => 'GUTIERREZ ESQUIVEL EDGAR', 'depto' => 'GESTION'],
    ['nombre' => 'INIESTRA SANCHEZ BRENDA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'RAMIREZ AVILA VIRGINIA ABIGAIL', 'depto' => 'OPERACIONES'],
    ['nombre' => 'SANCHEZ ANTONIO ANA KERE', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ROMERO LOPEZ LUIS ANTONIO', 'depto' => 'SISTEMAS'],
    ['nombre' => 'LIZBETH YESENIA CRUZ PERALTA', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'CRUZ BLANCA HERNANDEZ FABIOLA', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'PEREZ MORENO JUAN', 'depto' => 'OPERACIONES'],
    ['nombre' => 'SAMUEL ESCALANTE GUADARRAMA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ACUÑA QUIROZ REBECA PAOLA', 'depto' => 'OBRA CIVIL'],
    ['nombre' => 'RIOS GARCIA MELISSA', 'depto' => 'CONTABILIDAD'],
    ['nombre' => 'JORGE VALENCIA GALVAN', 'depto' => 'OPERACIONES'],
    ['nombre' => 'SANCHEZ VELASCO CARLOS ARTURO', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ABUNDIO VALDES FRANCISCO', 'depto' => 'CONTABILIDAD'],
    ['nombre' => 'ROSALES RAMIREZ ALFREDO', 'depto' => 'SISTEMAS'],
    ['nombre' => 'CASAS TREJO JOSE ANTONIO', 'depto' => 'OPERACIONES'],
    ['nombre' => 'MONROY SANCHEZ JOSE CARLOS', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ALFREDO NAVARRO COLIN', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'GARCIA RIVERA LANDY YAMELY', 'depto' => 'OPERACIONES'],
    ['nombre' => 'DIMAS DIAZ SAUL ELIEL', 'depto' => 'OPERACIONES']
];

$personalBloque4 = [
    ['nombre' => 'CASTILLO COLIN MARIA DEL CARMEN', 'depto' => 'ALTA DIRECCION'],
    ['nombre' => 'DIONISIO ESPINOZA LUIS ALBERTO', 'depto' => 'LICITACIONES'],
    ['nombre' => 'ESTRADA BECERRIL BERENICE', 'depto' => 'OPERACIONES'],
    ['nombre' => 'GONZALEZ AVILES REBECA', 'depto' => 'LICITACIONES'],
    ['nombre' => 'JULIETA REBECA IGLESIAS MARTINEZ', 'depto' => 'AUDITORIA'],
    ['nombre' => 'SOTO DEL HOYO ISMAEL', 'depto' => 'DIRECCION'],
    ['nombre' => 'NAVA DE JESUS ROGELIO', 'depto' => 'OPERACIONES'],
    ['nombre' => 'NUÑEZ ROJAS DAVID URIEL', 'depto' => 'AUDITORIA'],
    ['nombre' => 'CASTILLO NIETO JESSICA', 'depto' => 'DIRECCION'],
    ['nombre' => 'BENITEZ REBOLLAR LIZBETH', 'depto' => 'ADMINISTRACION'],
    ['nombre' => 'PINEDA JUAREZ ANA VIANEY', 'depto' => 'OPERACIONES'],
    ['nombre' => 'SAAVEDRA PEREZ MARTIN SANTOS', 'depto' => 'OPERACIONES'],
    ['nombre' => 'LIZBETH VILLEGAS SOSA', 'depto' => 'TALENTO HUMANO'],
    ['nombre' => 'OROZCO CARMONA IVAN', 'depto' => 'OPERACIONES'],
    ['nombre' => 'ROBERTO ANTONIO BEST FERNANDEZ', 'depto' => 'OBRA CIVIL'],
    ['nombre' => 'LUIS SALVADOR MEDINA VARGAS', 'depto' => 'TI'],
    ['nombre' => 'JOSE FERNANDO OSORIO OJEDA', 'depto' => 'DIRECCION'],
    ['nombre' => 'MANUEL EDUARDO SALCIDO HUERTA', 'depto' => 'OPERACIONES'],
    ['nombre' => 'CARBAJAL MENDEZ SILVIA LETICIA', 'depto' => 'FINANZAS Y CONTABILIDAD'],
    ['nombre' => 'GERARDO ASTIVIA SANCHEZ', 'depto' => 'OPERACIONES'],
    ['nombre' => 'BRITO SAUCEDO FERNANDO ANTONIO', 'depto' => 'OPERACIONES'],
    ['nombre' => 'MARTINEZ APOLINAR ISRAEL', 'depto' => 'OPERACIONES'],
    ['nombre' => 'PALACIOS LOPEZ JUAN MANUEL', 'depto' => 'DIRECCION']
];

// =============================================
// PROCESAR PEDIDOS - SOLO HOY
// =============================================

// Obtener pedidos de la semana
$pedidosSemana = getPedidosSemana();

// Crear un mapa de pedidos por nombre - SOLO HOY
$mapaPedidos = [];
$hoy = date('Y-m-d'); // Fecha de HOY

foreach ($pedidosSemana as $pedido) {
    $nombreLimpio = trim(strtoupper($pedido['Nombre'] ?? $pedido['Usuario']));
    $fecha = $pedido['FechaReal'] instanceof DateTime ? $pedido['FechaReal']->format('Y-m-d') : $pedido['FechaReal'];
    $consumo = trim(strtoupper($pedido['Consumo']));
    
    // SOLO guardar pedidos de HOY
    if ($fecha === $hoy) {
        if (!isset($mapaPedidos[$nombreLimpio])) {
            $mapaPedidos[$nombreLimpio] = [];
        }
        
        $mapaPedidos[$nombreLimpio][] = [
            'fecha' => $fecha,
            'consumo' => $consumo
        ];
    }
}

// =============================================
// MANEJO DE LOGIN Y SESIÓN
// =============================================

if (isset($_POST['login'])) {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    
    if ($usuario === 'chef' && $contrasena === 'chef1234') {
        $_SESSION['usuario'] = [
            'nombre' => 'Chef',
            'rol' => 'chef',
            'logueado' => true
        ];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error_login = "Usuario o contraseña incorrectos";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$pagina_actual = $_GET['page'] ?? 'comidas';

// =============================================
// FUNCIONES PARA EL MÓDULO DE COMIDAS
// =============================================

function getComidas() {
    $conn = getConnection();
    
    $sql = "SELECT 
                Id_Empleado,
                LTRIM(RTRIM(
                    CASE 
                        WHEN Nombre LIKE 'NOMBRE:%N.E:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('N.E:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%DEPARTAMENTO:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('DEPARTAMENTO:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%NSS:%' AND Nombre NOT LIKE '%N.E:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('NSS:', Nombre) - 8
                            )
                        WHEN Nombre LIKE '% se encuentra registrado para%'
                            THEN LEFT(
                                Nombre,
                                CHARINDEX(' se encuentra registrado para', Nombre)
                            )
                        WHEN Nombre LIKE 'NOMBRE:%AREA:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('AREA:', Nombre) - 8
                            )
                        ELSE Nombre
                    END
                )) AS Nombre_Limpio,
                Nombre as Nombre_Original,
                Area,
                CONVERT(varchar, Hora_Entrada, 108) as Hora_Entrada_Hora,
                CONVERT(varchar, Hora_Entrada, 103) as Hora_Entrada_Fecha,
                CONVERT(varchar, Hora_Entrada, 23) as Hora_Entrada_YYYYMMDD,
                CONVERT(varchar, Fecha, 108) as Hora_Comida,
                CONVERT(varchar, Fecha_Atendido, 108) as Hora_Atendido,
                Estatus,
                Usuario_Atiende,
                Hora_Entrada,
                Fecha,
                Fecha_Atendido
            FROM Entradas 
            WHERE CONVERT(date, Hora_Entrada, 103) = CONVERT(date, GETDATE(), 103)
            ORDER BY Hora_Entrada ASC";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $comidas = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['Hora_Corta'] = !empty($row['Hora_Entrada_Hora']) ? substr($row['Hora_Entrada_Hora'], 0, 5) : '--:--';
            $row['Fecha_Completa'] = !empty($row['Hora_Entrada_Fecha']) ? $row['Hora_Entrada_Fecha'] : date('d/m/Y');
            $row['Hora_Entrada_SQL'] = !empty($row['Hora_Entrada_YYYYMMDD']) ? $row['Hora_Entrada_YYYYMMDD'] : date('Y-m-d');
            
            if (!empty($row['Hora_Comida'])) {
                $hora = trim($row['Hora_Comida']);
                if (strlen($hora) == 5) {
                    $hora .= ':00';
                }
                $row['Fecha_Hora'] = $hora;
            } else {
                $row['Fecha_Hora'] = '00:00:00';
            }
            
            if (!empty($row['Hora_Atendido'])) {
                $horaAtendido = trim($row['Hora_Atendido']);
                if (strlen($horaAtendido) == 5) {
                    $horaAtendido .= ':00';
                }
                $row['Fecha_Atendido_Formateada'] = $horaAtendido;
            }
            
            $row['Estatus_Actual'] = !empty($row['Estatus']) ? $row['Estatus'] : 'PENDIENTE';
            $row['Nombre'] = $row['Nombre_Limpio'];
            $row['Nombre_Original'] = $row['Nombre_Original'];
            $row['Id_Unico'] = md5($row['Nombre'] . $row['Hora_Entrada_SQL'] . $row['Fecha_Hora']);
            
            $comidas[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'comidas' => $comidas]);
    exit;
}

function getEstadisticas() {
    $conn = getConnection();
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Estatus = 'ATENDIDO' THEN 1 ELSE 0 END) as atendidos,
                SUM(CASE WHEN Estatus IS NULL OR Estatus = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes
            FROM Entradas 
            WHERE CONVERT(date, Hora_Entrada, 103) = CONVERT(date, GETDATE(), 103)";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $estadisticas = ['total' => 0, 'atendidos' => 0, 'pendientes' => 0];
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $estadisticas = [
            'total' => intval($row['total']),
            'atendidos' => intval($row['atendidos']),
            'pendientes' => intval($row['pendientes'])
        ];
    }
    
    if ($stmt) sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'estadisticas' => $estadisticas]);
    exit;
}

function marcarAtendido() {
    if (!isset($_POST['nombre']) || !isset($_POST['hora_entrada']) || !isset($_POST['fecha_hora'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $conn = getConnection();
    
    $nombre_limpio = trim($_POST['nombre']);
    $hora_entrada = trim($_POST['hora_entrada']);
    $fecha_hora = trim($_POST['fecha_hora']);
    
    if (strlen($fecha_hora) == 5) {
        $fecha_hora .= ':00';
    }
    
    $sql = "UPDATE Entradas 
            SET Estatus = 'ATENDIDO',
                Fecha_Atendido = GETDATE(),
                Usuario_Atiende = 'CHEF'
            WHERE   CONVERT(date, Hora_Entrada, 103) = CONVERT(date, ?, 103)
            AND CONVERT(time, Fecha, 108) = CONVERT(time, ?, 108)
            AND (
                LTRIM(RTRIM(
                    CASE 
                        WHEN Nombre LIKE 'NOMBRE:%N.E:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('N.E:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%DEPARTAMENTO:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('DEPARTAMENTO:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%NSS:%' AND Nombre NOT LIKE '%N.E:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('NSS:', Nombre) - 8
                            )
                        WHEN Nombre LIKE '% se encuentra registrado para%'
                            THEN LEFT(
                                Nombre,
                                CHARINDEX(' se encuentra registrado para', Nombre)
                            )
                        WHEN Nombre LIKE 'NOMBRE:%AREA:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('AREA:', Nombre) - 8
                            )
                        ELSE Nombre
                    END
                )) = ?
            )
            AND (Estatus IS NULL OR Estatus = 'PENDIENTE')";
    
    $params = array($hora_entrada, $fecha_hora, $nombre_limpio);
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $error_message = 'Error en la consulta SQL';
        if ($errors) {
            $error_message .= ': ' . $errors[0]['message'];
        }
        
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error_message]);
        sqlsrv_close($conn);
        exit;
    }
    
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    if ($stmt) sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    if ($rowsAffected > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Comida atendida correctamente', 'rows' => $rowsAffected]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se encontró un registro pendiente con esos datos exactos']);
    }
    exit;
}

// =============================================
// FUNCIONES PARA EL MÓDULO DE COMPLEMENTOS
// =============================================

function getComplementos() {
    $conn = getConnection();
    
    $sql = "SELECT 
                LTRIM(RTRIM(
                    CASE 
                        WHEN Nombre LIKE 'NOMBRE:%N.E:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('N.E:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%DEPARTAMENTO:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('DEPARTAMENTO:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%NSS:%' AND Nombre NOT LIKE '%N.E:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('NSS:', Nombre) - 8
                            )
                        WHEN Nombre LIKE '% se encuentra registrado para%'
                            THEN LEFT(
                                Nombre,
                                CHARINDEX(' se encuentra registrado para', Nombre)
                            )
                        WHEN Nombre LIKE 'NOMBRE:%AREA:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('AREA:', Nombre) - 8
                            )
                        ELSE Nombre
                    END
                )) AS Nombre_Limpio,
                Nombre as Nombre_Original,
                complemento,
                fecha,
                hora,
                atendido,
                usuario_atendido,
                fecha_atendido
            FROM complementos 
            WHERE CONVERT(date, fecha, 103) = CONVERT(date, GETDATE(), 103)
            ORDER BY fecha ASC, hora ASC";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $complementos = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!empty($row['fecha'])) {
                if ($row['fecha'] instanceof DateTime) {
                    $row['fecha_formateada'] = $row['fecha']->format('d/m/Y');
                    $row['fecha_sql'] = $row['fecha']->format('Y-m-d');
                } else {
                    $row['fecha_formateada'] = $row['fecha'];
                    $row['fecha_sql'] = date('Y-m-d', strtotime(str_replace('/', '-', $row['fecha'])));
                }
            }
            
            if (!empty($row['hora'])) {
                $hora = trim($row['hora']);
                if (strlen($hora) == 5) {
                    $hora .= ':00';
                }
                $row['hora_completa'] = $hora;
                $row['hora_corta'] = substr($hora, 0, 5);
            } else {
                $row['hora_completa'] = '00:00:00';
                $row['hora_corta'] = '--:--';
            }
            
            if (!empty($row['fecha_atendido']) && $row['fecha_atendido'] instanceof DateTime) {
                $row['fecha_atendido_formateada'] = $row['fecha_atendido']->format('H:i:s');
            }
            
            $row['estatus'] = !empty($row['atendido']) && $row['atendido'] == 1 ? 'ATENDIDO' : 'PENDIENTE';
            $row['nombre'] = $row['Nombre_Limpio'];
            $row['Id_Unico'] = md5($row['nombre'] . $row['fecha_sql'] . $row['hora_completa'] . $row['complemento']);
            
            $complementos[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'complementos' => $complementos]);
    exit;
}

function getEstadisticasComplementos() {
    $conn = getConnection();
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN atendido = 1 THEN 1 ELSE 0 END) as atendidos,
                SUM(CASE WHEN atendido IS NULL OR atendido = 0 THEN 1 ELSE 0 END) as pendientes
            FROM complementos 
            WHERE CONVERT(date, fecha, 103) = CONVERT(date, GETDATE(), 103)";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $estadisticas = ['total' => 0, 'atendidos' => 0, 'pendientes' => 0];
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $estadisticas = [
            'total' => intval($row['total']),
            'atendidos' => intval($row['atendidos']),
            'pendientes' => intval($row['pendientes'])
        ];
    }
    
    if ($stmt) sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'estadisticas' => $estadisticas]);
    exit;
}

function marcarComplementoAtendido() {
    if (!isset($_POST['nombre']) || !isset($_POST['complemento']) || !isset($_POST['fecha']) || !isset($_POST['hora'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $conn = getConnection();
    
    $nombre_limpio = trim($_POST['nombre']);
    $complemento = trim($_POST['complemento']);
    $fecha = trim($_POST['fecha']);
    $hora = trim($_POST['hora']);
    
    if (strlen($hora) == 5) {
        $hora .= ':00';
    }
    
    $sql = "UPDATE complementos 
            SET atendido = 1,
                usuario_atendido = 'CHEF',
                fecha_atendido = GETDATE()
            WHERE CONVERT(date, fecha, 103) = CONVERT(date, ?, 103)
            AND LEFT(CONVERT(varchar, hora, 108), 5) = LEFT(CONVERT(varchar, ?, 108), 5)
            AND complemento = ?
            AND (
                LTRIM(RTRIM(
                    CASE 
                        WHEN Nombre LIKE 'NOMBRE:%N.E:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('N.E:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%DEPARTAMENTO:%' 
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('DEPARTAMENTO:', Nombre) - 8
                            )
                        WHEN Nombre LIKE 'NOMBRE:%NSS:%' AND Nombre NOT LIKE '%N.E:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('NSS:', Nombre) - 8
                            )
                        WHEN Nombre LIKE '% se encuentra registrado para%'
                            THEN LEFT(
                                Nombre,
                                CHARINDEX(' se encuentra registrado para', Nombre)
                            )
                        WHEN Nombre LIKE 'NOMBRE:%AREA:%'
                            THEN SUBSTRING(
                                Nombre, 
                                8,
                                CHARINDEX('AREA:', Nombre) - 8
                            )
                        ELSE Nombre
                    END
                )) = ?
            )
            AND (atendido IS NULL OR atendido = 0)";
    
    $params = array($fecha, $hora, $complemento, $nombre_limpio);
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $error_message = 'Error en la consulta SQL';
        if ($errors) {
            $error_message .= ': ' . $errors[0]['message'];
        }
        
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error_message]);
        sqlsrv_close($conn);
        exit;
    }
    
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    if ($stmt) sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    if ($rowsAffected > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Complemento atendido correctamente', 'rows' => $rowsAffected]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se encontró un complemento pendiente con esos datos exactos']);
    }
    exit;
}

// =============================================
// FUNCIONES PARA EL MÓDULO DE ASIGNACIÓN
// =============================================

function getEmpleados() {
    $conn = getConnection();
    
    $sql = "SELECT Id_Empleado, Nombre, Area FROM ConPed ORDER BY Nombre";
    $stmt = sqlsrv_query($conn, $sql);
    
    $empleados = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $empleados[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    sqlsrv_close($conn);
    return $empleados;
}

function getCancelaciones() {
    $conn = getConnection();
    
    $sql = "SELECT 
                NOMBRE,
                DEPARTAMENTO,
                JEFE,
                TIPO_CONSUMO,
                FECHA,
                CAUSA,
                ESTATUS,
                FECHA_CAPTURA,
                APARTADO_POR,
                FECHA_APARTADO,
                ESTATUS_APARTADO,
                USUARIO_APARTA,
                ESTATUS_SOLICITUD
            FROM Cancelaciones 
            WHERE CONVERT(DATE, FECHA) = CONVERT(DATE, GETDATE())
            ORDER BY FECHA DESC, NOMBRE ASC";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    $cancelaciones = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['FECHA'] instanceof DateTime) {
                $row['FECHA_FORMATEADA'] = $row['FECHA']->format('d/m/Y');
                $row['FECHA_SQL'] = $row['FECHA']->format('Y-m-d');
            } else {
                $row['FECHA_FORMATEADA'] = $row['FECHA'];
                $row['FECHA_SQL'] = date('Y-m-d', strtotime(str_replace('/', '-', $row['FECHA'])));
            }
            
            if ($row['FECHA_APARTADO'] instanceof DateTime) {
                $row['FECHA_APARTADO_FORMATEADA'] = $row['FECHA_APARTADO']->format('d/m/Y H:i');
            }
            
            $cancelaciones[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cancelaciones' => $cancelaciones]);
    exit;
}

function asignarCancelacion() {
    if (!isset($_SESSION['usuario']) || !$_SESSION['usuario']['logueado']) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $conn = getConnection();
    
    $nombreCancelacion = $_POST['nombre_cancelacion'] ?? '';
    $tipoConsumo = $_POST['tipo_consumo'] ?? '';
    $fechaCancelacion = $_POST['fecha_cancelacion'] ?? '';
    $idPersonaAsignar = $_POST['id_persona'] ?? 0;
    $nombrePersona = $_POST['nombre_persona'] ?? '';
    
    if (empty($nombreCancelacion) || empty($tipoConsumo) || empty($fechaCancelacion) || $idPersonaAsignar <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $fechaSQL = date('Y-m-d', strtotime(str_replace('/', '-', $fechaCancelacion)));
    
    $sql = "UPDATE Cancelaciones 
            SET APARTADO_POR = ?, 
                USUARIO_APARTA = ?,
                ESTATUS_APARTADO = 'ASIGNADO',
                FECHA_APARTADO = GETDATE(),
                ESTATUS_SOLICITUD = 'APROBADA',
                APARTADO_APROBADO_POR = ?
            WHERE NOMBRE = ? 
            AND TIPO_CONSUMO = ? 
            AND CONVERT(DATE, FECHA) = CONVERT(DATE, ?)";
    
    $params = array(
        $idPersonaAsignar, 
        $nombrePersona,
        $_SESSION['usuario']['nombre'],
        $nombreCancelacion, 
        $tipoConsumo, 
        $fechaSQL
    );
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error al asignar']);
        exit;
    }
    
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    if ($rowsAffected > 0) {
        echo json_encode(['success' => true, 'message' => 'Cancelación asignada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la cancelación o ya está asignada']);
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    exit;
}

function liberarCancelacion() {
    if (!isset($_SESSION['usuario']) || !$_SESSION['usuario']['logueado']) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $conn = getConnection();
    
    $nombreCancelacion = $_POST['nombre_cancelacion'] ?? '';
    $tipoConsumo = $_POST['tipo_consumo'] ?? '';
    $fechaCancelacion = $_POST['fecha_cancelacion'] ?? '';
    
    if (empty($nombreCancelacion) || empty($tipoConsumo) || empty($fechaCancelacion)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $fechaSQL = date('Y-m-d', strtotime(str_replace('/', '-', $fechaCancelacion)));
    
    $sql = "UPDATE Cancelaciones 
            SET APARTADO_POR = NULL, 
                USUARIO_APARTA = NULL,
                ESTATUS_APARTADO = NULL,
                FECHA_APARTADO = NULL,
                ESTATUS_SOLICITUD = NULL,
                MOTIVO_SOLICITUD = NULL,
                FECHA_SOLICITUD = NULL,
                MOTIVO_RECHAZO = NULL,
                RECHAZADO_POR = NULL,
                FECHA_RECHAZO = NULL,
                APARTADO_APROBADO_POR = NULL
            WHERE NOMBRE = ? 
            AND TIPO_CONSUMO = ? 
            AND CONVERT(DATE, FECHA) = CONVERT(DATE, ?)";
    
    $params = array($nombreCancelacion, $tipoConsumo, $fechaSQL);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error al liberar']);
        exit;
    }
    
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    if ($rowsAffected > 0) {
        echo json_encode(['success' => true, 'message' => 'Cancelación liberada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la cancelación']);
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    exit;
}

// =============================================
// FUNCIONES PARA EL MÓDULO DE REGISTROS DE COMIDA
// =============================================

$nombresCompletos = [
    "ALEJANDRA CRUZ" => "NOMBRE: ALEJANDRA CRUZ N.E: 3 DEPARTAMENTO: DIRECCION AREA: DIRECCION ZONA: ZINACANTEPEC PUESTO: ANALISTA NSS: 0000000000 CEL: 24",
    "ALTA DIRECCION" => "NOMBRE: ALTA DIRECCION N.E: 4 DEPARTAMENTO: DIRECCION AREA: DIRECCION ZONA: ZINACANTEPEC PUESTO: DIRECCION NSS: 0000000000 CEL: 26",
    "CRUZ JOSE LUIS" => "NOMBRE: CRUZ JOSE LUIS N.E: 682 DEPARTAMENTO: OPERACIONES AREA: OPERACIONES ZONA: VALLE DE MEXICO Y TOLUCA PUESTO: TECNICO DE MANTENIMIENTO NSS: 42957406111",
    "CRUZ RODRIGUEZ ALEJANDRO" => "NOMBRE: CRUZ RODRIGUEZ ALEJANDRO N.E: 41 DEPARTAMENTO: CONTABILIDAD AREA: CONTABILIDAD ZONA: ZINACANTEPEC PUESTO: ANALISTA NSS: 39988108666",
    "JURIDICO" => "NOMBRE: JURIDICO N.E: 5 DEPARTAMENTO: PROYECTOS ESPECIALES AREA: JURIDICO ZONA: ZINACANTEPEC PUESTO: ABOGADOS NSS: 0000000000",
    "PALMA TREJO SANDY MARK" => "NOMBRE: PALMA TREJO SANDY MARK N.E: 1101 DEPARTAMENTO: DIRECCION AREA: DIRECCION ZONA: ZINACANTEPEC PUESTO: CHOFER NSS: 81927423350",
    "REYES QUIROZ HILDA" => "NOMBRE: REYES QUIROZ HILDA N.E: 24 DEPARTAMENTO: CONTABILIDAD Y FINANZAS AREA: CONTROL DE EGRESOS ZONA: TOLUCA VIA REMOTA PUESTO: ANALISTA NSS: 18886904574",
    "VIGILANCIA" => "NOMBRE: VIGILANCIA N.E: 105868 DEPARTAMENTO: SERVICIOS GENERALES AREA: VIGILANCIA ZONA: ZINACANTEPEC PUESTO: VIGILANTE NSS: 000000000000",
    "COCINA" => "NOMBRE: COCINA N.E: 105869 DEPARTAMENTO: SERVICIOS GENERALES AREA: VIGILANCIA ZONA: ZINACANTEPEC PUESTO: VIGILANTE NSS: 000000000000"
];

function crearRegistroComida() {
    global $nombresCompletos;
    
    if (!isset($_POST['nombre_corto']) || !isset($_POST['fecha_real']) || !isset($_POST['hora_real'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $conn = getConnection();
    
    $nombre_corto = trim($_POST['nombre_corto']);
    $hora_real = trim($_POST['hora_real']);
    $fecha_real = trim($_POST['fecha_real']);
    
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $fecha_real)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha incorrecto. Use dd-mm-yyyy']);
        exit;
    }
    
    if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora_real)) {
        echo json_encode(['success' => false, 'message' => 'Formato de hora incorrecto. Use HH:MM:SS']);
        exit;
    }
    
    $nombre_completo = $nombresCompletos[$nombre_corto] ?? $nombre_corto;
    
    $sql = "INSERT INTO entradas
            (Id_Empleado, Nombre, Area, Fecha, Hora_Entrada, Estatus)
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $params = [
        0,
        $nombre_completo,
        '',
        $hora_real,
        $fecha_real,
        'PENDIENTE'
    ];
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $error = sqlsrv_errors();
        echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $error[0]['message']]);
    } else {
        echo json_encode(['success' => true, 'message' => '✅ Registro guardado exitosamente']);
    }
    
    sqlsrv_close($conn);
    exit;
}

function getRegistrosHoy() {
    $conn = getConnection();
    
    $hoy = date('d-m-Y');
    $sql = "SELECT 
                Id,
                Nombre,
                Fecha as Hora_Comida,
                Hora_Entrada as Fecha_Registro,
                Estatus,
                Usuario_Atiende,
                Fecha_Atendido
            FROM entradas 
            WHERE Hora_Entrada = ?
            ORDER BY Fecha DESC";
    
    $stmt = sqlsrv_query($conn, $sql, [$hoy]);
    
    $registros = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $nombreLimpio = $row['Nombre'];
            if (strpos($nombreLimpio, 'NOMBRE:') !== false) {
                $nombreLimpio = preg_replace('/NOMBRE:\s*(.+?)\s+(N\.E:|DEPARTAMENTO:|NSS:|se encuentra registrado para|AREA:)/', '$1', $nombreLimpio);
                $nombreLimpio = trim($nombreLimpio);
            }
            $row['Nombre_Limpio'] = $nombreLimpio;
            
            if (!empty($row['Fecha_Atendido']) && $row['Fecha_Atendido'] instanceof DateTime) {
                $row['Fecha_Atendido_Formateada'] = $row['Fecha_Atendido']->format('H:i:s');
            }
            
            $registros[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'registros' => $registros, 'hoy' => $hoy]);
    exit;
}

// =============================================
// FUNCIÓN PARA OBTENER BLOQUE ACTUAL (AJAX)
// =============================================

function getBloqueActualJSON() {
    global $horariosComedor;
    $bloqueActual = getBloqueActual($horariosComedor);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'bloque' => $bloqueActual]);
    exit;
}

// Función para obtener el bloque actual
function getBloqueActual($horariosComedor) {
    $horaActual = date('H:i');
    $tipoComida = date('H') < 12 ? 'desayuno' : 'comida';
    
    foreach ($horariosComedor[$tipoComida] as $bloque => $horario) {
        if ($horaActual >= $horario['inicio'] && $horaActual <= $horario['fin']) {
            return [
                'bloque' => $bloque,
                'tipo' => $tipoComida,
                'nombre' => $horario['nombre'],
                'inicio' => $horario['inicio'],
                'fin' => $horario['fin'],
                'activo' => true
            ];
        }
    }
    
    $siguienteBloque = null;
    foreach ($horariosComedor[$tipoComida] as $bloque => $horario) {
        if ($horaActual < $horario['inicio']) {
            $siguienteBloque = [
                'bloque' => $bloque,
                'tipo' => $tipoComida,
                'nombre' => $horario['nombre'],
                'inicio' => $horario['inicio'],
                'fin' => $horario['fin'],
                'activo' => false,
                'mensaje' => 'Próximo: ' . $horario['nombre']
            ];
            break;
        }
    }
    
    if ($siguienteBloque) {
        return $siguienteBloque;
    }
    
    return [
        'activo' => false,
        'mensaje' => '⏰ No hay turno de comida en este momento',
        'tipo' => $tipoComida
    ];
}

// =============================================
// MANEJO DE ACCIONES AJAX
// =============================================

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_comidas':
            getComidas();
            break;
        case 'get_estadisticas':
            getEstadisticas();
            break;
        case 'marcar_atendido':
            marcarAtendido();
            break;
        case 'get_complementos':
            getComplementos();
            break;
        case 'get_estadisticas_complementos':
            getEstadisticasComplementos();
            break;
        case 'marcar_complemento_atendido':
            marcarComplementoAtendido();
            break;
        case 'get_cancelaciones':
            getCancelaciones();
            break;
        case 'asignar_cancelacion':
            asignarCancelacion();
            break;
        case 'liberar_cancelacion':
            liberarCancelacion();
            break;
        case 'crear_registro_comida':
            crearRegistroComida();
            break;
        case 'get_registros_hoy':
            getRegistrosHoy();
            break;
        case 'get_bloque_actual':
            getBloqueActualJSON();
            break;
    }
}

// =============================================
// OBTENER DATOS PARA RENDERIZADO INICIAL
// =============================================

$bloqueActual = getBloqueActual($horariosComedor);
$empleados = getEmpleados();
$turnoActual = date('H') < 12 ? 'DESAYUNO' : 'COMIDA';
$colorTurno = date('H') < 12 ? '#FFC107' : '#2196F3';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
    <title>Dashboard Chef Premium - Versión Actualizada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* ============ VARIABLES Y RESET MEJORADOS ============ */
        :root {
            --primary: #2c5aa0;
            --primary-light: #4a7bc8;
            --primary-dark: #1a3d7a;
            --secondary: #FFC107;
            --secondary-light: #FFD54F;
            --secondary-dark: #FF8F00;
            --accent: #6c757d;
            --success: #28a745;
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --glass-bg: rgba(255, 255, 255, 0.98);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 5px 20px rgba(44, 90, 160, 0.12);
            --shadow-lg: 0 15px 40px rgba(44, 90, 160, 0.2);
            --shadow-hover: 0 20px 50px rgba(44, 90, 160, 0.25);
            --complement-color: #2196F3;
            --complement-light: #e3f2fd;
            --desayuno-color: #FFC107;
            --comida-color: #2196F3;
            --bloque1-color: #4CAF50;
            --bloque2-color: #FF9800;
            --bloque3-color: #9C27B0;
            --bloque4-color: #F44336;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --border-radius-lg: 24px;
        }
        
        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(145deg, #f0f5ff 0%, #e6f0fa 100%);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* ============ LOGIN MEJORADO ============ */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 16px;
        }
        
        .login-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            padding: clamp(30px, 6vw, 50px);
            width: 100%;
            max-width: 480px;
            animation: slideUpFade 0.6s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 38px;
            box-shadow: 0 15px 30px rgba(44, 90, 160, 0.4);
            border: 3px solid var(--secondary);
        }
        
        /* ============ NAVBAR PREMIUM ============ */
        .navbar-premium {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--secondary);
            box-shadow: var(--shadow-lg);
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .navbar-brand-premium {
            font-weight: 700;
            font-size: 1.4rem;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }
        
        .navbar-brand-premium i {
            color: var(--secondary);
            font-size: 1.8rem;
        }
        
        .user-dropdown {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 6px 16px 6px 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
            cursor: pointer;
        }
        
        .user-dropdown:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* ============ SIDEBAR MEJORADO ============ */
        .sidebar-container {
            position: fixed;
            top: 76px;
            left: 0;
            height: calc(100vh - 76px);
            width: 280px;
            background: white;
            border-right: 1px solid rgba(44, 90, 160, 0.1);
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.03);
            z-index: 1020;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            scrollbar-width: thin;
        }
        
        .sidebar-container::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .sidebar-container::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 2px solid rgba(44, 90, 160, 0.1);
            background: linear-gradient(to right, rgba(44, 90, 160, 0.02), transparent);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 40px;
            border: 3px solid var(--secondary);
            box-shadow: 0 10px 25px rgba(44, 90, 160, 0.3);
        }
        
        .user-info h5 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 24px;
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
            margin: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
            gap: 16px;
        }
        
        .menu-item:hover {
            background: rgba(44, 90, 160, 0.06);
            color: var(--primary-dark);
            border-left-color: var(--secondary);
            transform: translateX(5px);
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(44, 90, 160, 0.1), transparent);
            color: var(--primary-dark);
            border-left-color: var(--secondary);
            font-weight: 600;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .menu-item i {
            width: 24px;
            font-size: 1.2rem;
            text-align: center;
        }
        
        /* ============ MAIN CONTENT ============ */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 76px);
        }
        
        /* ============ RESPONSIVE BREAKPOINTS MEJORADOS ============ */
        @media (max-width: 1200px) {
            .main-content {
                padding: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar-container {
                transform: translateX(-100%);
                box-shadow: 10px 0 40px rgba(0,0,0,0.1);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar-container.show {
                transform: translateX(0);
            }
        }
        
        @media (max-width: 768px) {
            .navbar-brand-premium span {
                display: none;
            }
            .main-content {
                padding: 16px;
            }
        }
        
        /* ============ TOGGLE BUTTON ============ */
        .toggle-sidebar {
            display: none;
            background: var(--secondary);
            border: none;
            border-radius: 14px;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 1.3rem;
            box-shadow: 0 6px 18px rgba(255, 193, 7, 0.3);
            transition: var(--transition);
            margin-right: 12px;
        }
        
        .toggle-sidebar:hover {
            transform: scale(1.05);
            background: var(--secondary-dark);
            color: white;
        }
        
        @media (max-width: 992px) {
            .toggle-sidebar {
                display: flex;
            }
        }
        
        /* ============ CARDS MEJORADAS ============ */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            padding: 28px;
            margin-bottom: 30px;
            transition: var(--transition);
            border: 1px solid rgba(44, 90, 160, 0.08);
            backdrop-filter: blur(10px);
        }
        
        .dashboard-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(44, 90, 160, 0.15);
        }
        
        .card-header-premium {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid rgba(44, 90, 160, 0.08);
            padding-bottom: 20px;
            margin-bottom: 24px;
            gap: 15px;
        }
        
        .card-title-premium {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .card-title-premium i {
            color: var(--secondary);
            font-size: 1.8rem;
        }
        
        /* ============ STAT CARDS MEJORADAS ============ */
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border-top: 5px solid var(--primary);
            transition: var(--transition);
            height: 100%;
            border: 1px solid rgba(44, 90, 160, 0.08);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }
        
        .stat-card.warning {
            border-top-color: var(--warning);
        }
        
        .stat-card.success {
            border-top-color: var(--success);
        }
        
        .stat-card.complement {
            border-top-color: var(--complement-color);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 18px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 18px rgba(44, 90, 160, 0.25);
        }
        
        .stat-icon.warning {
            background: linear-gradient(145deg, var(--warning), var(--secondary-dark));
        }
        
        .stat-icon.success {
            background: linear-gradient(145deg, var(--success), #1e7e34);
        }
        
        .stat-icon.complement {
            background: linear-gradient(145deg, var(--complement-color), #1976D2);
        }
        
        /* ============ BUTTONS MEJORADOS ============ */
        .btn-premium, .btn-complement {
            border: none;
            border-radius: 14px;
            padding: 12px 24px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(44, 90, 160, 0.2);
            letter-spacing: 0.3px;
        }
        
        .btn-premium {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44, 90, 160, 0.35);
            color: white;
            background: linear-gradient(145deg, var(--primary-dark), var(--primary));
        }
        
        .btn-complement {
            background: linear-gradient(145deg, var(--complement-color), #1976D2);
            color: white;
        }
        
        .btn-complement:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.35);
            background: linear-gradient(145deg, #1976D2, var(--complement-color));
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* ============ LIST CARDS MEJORADAS ============ */
        .comida-card, .complemento-card, .registro-card, .cancelacion-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(44, 90, 160, 0.05);
            border-left: 6px solid var(--warning);
            transition: var(--transition);
            border: 1px solid #edf2f7;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .comida-card:hover, .complemento-card:hover, .registro-card:hover, .cancelacion-card:hover {
            box-shadow: 0 10px 30px rgba(44, 90, 160, 0.12);
            transform: translateY(-4px);
            border-color: var(--warning);
        }
        
        .comida-card.atendida, .complemento-card.atendida, .registro-card.atendida {
            border-left-color: var(--success);
            background: linear-gradient(145deg, white, #f8fff9);
        }
        
        .cancelacion-card.asignada {
            border-left-color: var(--success);
        }
        
        /* ============ GRID PERSONAL MEJORADO ============ */
        .personal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 20px;
            max-height: 600px;
            overflow-y: auto;
            padding: 4px 4px 8px 0;
            scrollbar-width: thin;
        }
        
        @media (max-width: 640px) {
            .personal-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
        }
        
        .personal-item {
            background: white;
            border: 1px solid #edf2f7;
            border-radius: 16px;
            padding: 16px 10px;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.02);
            min-height: 160px;
        }
        
        .personal-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(44,90,160,0.12);
            border-color: var(--primary-light);
        }
        
        .personal-numero {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.75rem;
            margin-bottom: 8px;
            background: rgba(44,90,160,0.08);
            padding: 4px 12px;
            border-radius: 40px;
            width: fit-content;
        }
        
        .personal-nombre {
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 0.85rem;
            line-height: 1.4;
            color: var(--primary-dark);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            width: 100%;
        }
        
        .personal-depto {
            font-size: 0.7rem;
            background: rgba(44,90,160,0.06);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 40px;
            margin: 8px 0;
            font-weight: 500;
            max-width: 100%;
        }
        
        /* ============ BADGES MEJORADOS - CORREGIDOS PARA MOSTRAR AGENDADO ============ */
        .badge-atendido, .badge-pendiente, .badge-sinregistro, .badge-consumo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 40px;
            font-size: 0.7rem;
            white-space: nowrap;
        }
        
        .badge-atendido {
            background: var(--success-light);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .badge-pendiente {
            background: var(--warning-light);
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .badge-sinregistro {
            background: #e9ecef;
            color: #495057;
            border: 1px solid #ced4da;
        }
        
        .badge-consumo {
            background: rgba(255, 193, 7, 0.15);
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        /* Estilo específico para COMIDA AGENDADO */
        .badge-consumo-comida {
            background: rgba(33, 150, 243, 0.15);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* ============ ESTADOS LEYENDA ============ */
        .estados-leyenda {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background: white;
            padding: 16px 20px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            border: 1px solid #edf2f7;
        }
        
        /* ============ SPLIT LAYOUT ============ */
        .split-layout {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 25px;
            margin-top: 20px;
        }
        
        @media (max-width: 1200px) {
            .split-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
        
        .comidas-section, .horarios-section {
            min-width: 0;
        }
        
        .comidas-section {
            max-height: 800px;
            overflow-y: auto;
            padding-right: 8px;
            scrollbar-width: thin;
        }
        
        /* ============ HORARIOS BANNER ============ */
        .horarios-banner {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            color: white;
            box-shadow: var(--shadow-md);
        }
        
        .digital-clock {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 3px;
            background: rgba(0,0,0,0.2);
            padding: 8px 20px;
            border-radius: 16px;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .bloque-activo {
            background: rgba(255,193,7,0.2);
            border: 2px solid var(--secondary);
            border-radius: 16px;
            padding: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255,193,7,0.5); }
            70% { box-shadow: 0 0 0 12px rgba(255,193,7,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,193,7,0); }
        }
        
        /* ============ TABS BLOQUES ============ */
        .bloque-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .bloque-tab {
            flex: 1;
            min-width: 100px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 14px;
            padding: 12px 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .bloque-tab:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .bloque-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(44,90,160,0.3);
        }
        
        /* ============ FORMULARIO REGISTRO ============ */
        .form-registro {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            border: 1px solid #edf2f7;
        }
        
        .datetime-input {
            position: relative;
        }
        
        .datetime-input input {
            padding-right: 45px;
            border-radius: 14px;
            border: 1px solid #dee2e6;
            height: 48px;
        }
        
        .datetime-input i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            pointer-events: none;
            font-size: 1.1rem;
        }
        
        /* ============ LOADING SPINNER ============ */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(44, 90, 160, 0.1);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ============ ANIMACIONES ============ */
        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: slideUpFade 0.5s ease;
        }
        
        /* ============ EMPTY STATE ============ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            border: 2px dashed #dee2e6;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: rgba(44, 90, 160, 0.2);
            margin-bottom: 20px;
        }
        
        /* ============ TURNO INDICATOR ============ */
        .turno-indicator {
            display: inline-flex;
            align-items: center;
            padding: 6px 18px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(5px);
        }
        
        .turno-desayuno {
            background: var(--desayuno-color);
            color: black;
        }
        
        .turno-comida {
            background: var(--comida-color);
            color: white;
        }
        
        /* ============ FLOATING BUTTON ============ */
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            box-shadow: 0 10px 30px rgba(44, 90, 160, 0.4);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .floating-btn:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 15px 40px rgba(44, 90, 160, 0.5);
        }
        
        @media (max-width: 768px) {
            .floating-btn {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
        
        /* ============ UTILIDADES ============ */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .hover-lift {
            transition: var(--transition);
        }
        
        .hover-lift:hover {
            transform: translateY(-3px);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['usuario']) || !$_SESSION['usuario']['logueado']): ?>
        <!-- Login Page Mejorado -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h2 class="fw-bold mb-2" style="color: var(--primary-dark);">¡Bienvenido Chef!</h2>
                    <p class="text-muted">Sistema Premium de Gestión de Comidas</p>
                </div>
                
                <?php if (isset($error_login)): ?>
                    <div class="alert alert-danger fade-in d-flex align-items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error_login; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-4">
                                <i class="fas fa-user text-primary"></i>
                            </span>
                            <input type="text" name="usuario" class="form-control border-start-0 rounded-end-4 py-3" 
                                   placeholder="Ingresa tu usuario" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-4">
                                <i class="fas fa-lock text-primary"></i>
                            </span>
                            <input type="password" name="contrasena" class="form-control border-start-0 rounded-end-4 py-3" 
                                   placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn-premium w-100 py-3 fw-bold">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Dashboard Mejorado -->
        <nav class="navbar navbar-premium">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="toggle-sidebar me-3" onclick="toggleSidebar()" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand-premium" href="?page=comidas">
                        <i class="fas fa-crown"></i>
                        <span>Dashboard Chef</span>
                    </a>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <div class="user-dropdown d-flex align-items-center text-white" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-2x me-2"></i>
                            <div class="d-none d-sm-block">
                                <div class="fw-bold"><?php echo $_SESSION['usuario']['nombre']; ?></div>
                                <small class="opacity-75">Chef Principal</small>
                            </div>
                            <i class="fas fa-chevron-down ms-2 small"></i>
                        </div>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="?page=configuracion">
                                <i class="fas fa-cog me-2"></i>Configuración
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="?logout=1">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Sidebar -->
        <div class="sidebar-container" id="sidebar">
            <div class="sidebar-header">
                <div class="user-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="user-info text-center">
                    <h5><?php echo $_SESSION['usuario']['nombre']; ?></h5>
                    <span class="badge bg-warning text-dark px-3 py-2">
                        <i class="fas fa-crown me-1"></i>Chef Principal
                    </span>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <a href="?page=comidas" class="menu-item <?php echo $pagina_actual == 'comidas' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i>
                    <span>Comidas a Servir</span>
                    <span class="badge bg-warning ms-auto" id="sidebar-comidas-count" style="display: none;">0</span>
                </a>
                
                <a href="?page=complementos" class="menu-item <?php echo $pagina_actual == 'complementos' ? 'active' : ''; ?>">
                    <i class="fas fa-mug-hot"></i>
                    <span>Complementos</span>
                    <span class="badge bg-warning ms-auto" id="sidebar-complementos-count" style="display: none;">0</span>
                </a>
                
                <a href="?page=registros" class="menu-item <?php echo $pagina_actual == 'registros' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Registros de Comida</span>
                    <span class="badge bg-warning ms-auto" id="sidebar-registros-count" style="display: none;">0</span>
                </a>
                
                <a href="?page=asignar" class="menu-item <?php echo $pagina_actual == 'asignar' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Asignar Comidas</span>
                </a>
                
                <a href="?page=configuracion" class="menu-item <?php echo $pagina_actual == 'configuracion' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
                
                <hr class="my-3 mx-3">
                
                <a href="?logout=1" class="menu-item text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <?php if ($pagina_actual == 'comidas'): ?>
                <!-- MÓDULO: COMIDAS A SERVIR MEJORADO - CON BADGES DE ESTADO CORREGIDOS -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium">
                        <h3 class="card-title-premium">
                            <i class="fas fa-utensils"></i>
                            <span>Comidas a Servir - HOY</span>
                            <span class="turno-indicator <?php echo date('H') < 12 ? 'turno-desayuno' : 'turno-comida'; ?>">
                                <i class="fas <?php echo date('H') < 12 ? 'fa-sun' : 'fa-utensils'; ?>"></i>
                                <?php echo $turnoActual; ?>
                            </span>
                        </h3>
                        <div class="d-flex gap-2">
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-premium btn-sm" onclick="cargarDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="split-layout">
                        <!-- SECCIÓN COMIDAS -->
                        <div class="comidas-section">
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <h4 id="comidas-pendientes" class="fw-bold mb-1">0</h4>
                                        <p class="text-muted mb-0 small">Pendientes</p>
                                        <small id="pendientes-horario" class="text-warning small"></small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card success">
                                        <div class="stat-icon success">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <h4 id="comidas-atendidas" class="fw-bold mb-1">0</h4>
                                        <p class="text-muted mb-0 small">Atendidas</p>
                                        <small id="atendidas-porcentaje" class="text-success small"></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">
                                    <i class="fas fa-list me-2"></i>Lista de Comidas
                                    <span class="badge bg-warning ms-2" id="notification-count" style="display: none;">0</span>
                                </h6>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="filtrarComidas('todas')">Todas</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="filtrarComidas('pendientes')">Pendientes</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="filtrarComidas('atendidas')">Atendidas</button>
                                </div>
                            </div>
                            
                            <div id="vista-comidas" style="max-height: 550px; overflow-y: auto; padding-right: 5px;">
                                <div class="text-center py-5">
                                    <div class="loading-spinner mx-auto mb-3"></div>
                                    <p class="text-muted">Cargando comidas...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SECCIÓN HORARIOS Y PERSONAL -->
                        <div class="horarios-section">
                            <div class="horarios-banner">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <i class="fas fa-clock fa-2x"></i>
                                    <div>
                                        <span class="text-uppercase small opacity-75">Hora actual</span>
                                        <div class="digital-clock" id="relojDigital"><?php echo date('H:i:s'); ?></div>
                                    </div>
                                </div>
                                
                                <div id="contenedorBloqueActual">
                                    <?php if ($bloqueActual['activo']): ?>
                                        <div class="bloque-activo">
                                            <span class="badge bg-warning text-dark mb-2">
                                                <i class="fas fa-bell me-1"></i>ACTIVO AHORA
                                            </span>
                                            <h5 class="text-white mb-1"><?php echo $bloqueActual['nombre']; ?></h5>
                                            <p class="text-white-50 mb-0 small">
                                                <i class="fas fa-clock me-1"></i><?php echo $bloqueActual['inicio']; ?> - <?php echo $bloqueActual['fin']; ?>
                                            </p>
                                        </div>
                                    <?php elseif (isset($bloqueActual['mensaje']) && strpos($bloqueActual['mensaje'], 'Próximo') !== false): ?>
                                        <div class="bg-white-10 p-4 rounded" style="background: rgba(255,255,255,0.1);">
                                            <span class="badge bg-secondary mb-2">
                                                <i class="fas fa-hourglass-half me-1"></i>PRÓXIMO
                                            </span>
                                            <h5 class="text-white mb-1"><?php echo $bloqueActual['mensaje']; ?></h5>
                                            <p class="text-white-50 mb-0 small">
                                                <i class="fas fa-clock me-1"></i><?php echo $bloqueActual['inicio'] ?? '--:--'; ?> - <?php echo $bloqueActual['fin'] ?? '--:--'; ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-white-10 p-4 rounded" style="background: rgba(255,255,255,0.1);">
                                            <span class="badge bg-secondary mb-2">
                                                <i class="fas fa-moon me-1"></i>DESCANSO
                                            </span>
                                            <h5 class="text-white mb-0"><?php echo $bloqueActual['mensaje'] ?? 'No hay turno'; ?></h5>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="bloque-tabs">
                                <div class="bloque-tab active" onclick="mostrarBloque('bloque1')" id="tab-bloque1">
                                    <i class="fas fa-circle" style="color: #4CAF50;"></i> BLOQUE 1
                                    <span class="badge bg-primary ms-1">28</span>
                                </div>
                                <div class="bloque-tab" onclick="mostrarBloque('bloque2')" id="tab-bloque2">
                                    <i class="fas fa-circle" style="color: #FF9800;"></i> BLOQUE 2
                                    <span class="badge bg-primary ms-1">21</span>
                                </div>
                                <div class="bloque-tab" onclick="mostrarBloque('bloque3')" id="tab-bloque3">
                                    <i class="fas fa-circle" style="color: #9C27B0;"></i> BLOQUE 3
                                    <span class="badge bg-primary ms-1">22</span>
                                </div>
                                <div class="bloque-tab" onclick="mostrarBloque('bloque4')" id="tab-bloque4">
                                    <i class="fas fa-circle" style="color: #F44336;"></i> BLOQUE 4
                                    <span class="badge bg-primary ms-1">28</span>
                                </div>
                            </div>
                            
                            <div class="estados-leyenda">
                                <span><i class="fas fa-check-circle text-success me-1"></i> ATENDIDO</span>
                                <span><i class="fas fa-clock text-warning me-1"></i> EN FILA</span>
                                <span><i class="fas <?php echo date('H') < 12 ? 'fa-sun' : 'fa-utensils'; ?> me-1" style="color: <?php echo $colorTurno; ?>;"></i> <?php echo $turnoActual; ?> AGENDADO</span>
                                <span><i class="fas fa-hourglass text-secondary me-1"></i> SIN REGISTRO</span>
                            </div>
                            
                            <div id="contenedor-personal">
                                <!-- BLOQUE 1 -->
                                <div id="personal-bloque1">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0">
                                            <i class="fas fa-users me-2"></i>BLOQUE 1 - Personal
                                        </h6>
                                        <span class="badge bg-primary">28</span>
                                    </div>
                                    <div class="personal-grid" id="grid-bloque1"></div>
                                </div>
                                
                                <!-- BLOQUE 2 -->
                                <div id="personal-bloque2" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0">
                                            <i class="fas fa-users me-2"></i>BLOQUE 2 - Personal
                                        </h6>
                                        <span class="badge bg-primary">21</span>
                                    </div>
                                    <div class="personal-grid" id="grid-bloque2"></div>
                                </div>
                                
                                <!-- BLOQUE 3 -->
                                <div id="personal-bloque3" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0">
                                            <i class="fas fa-users me-2"></i>BLOQUE 3 - Personal
                                        </h6>
                                        <span class="badge bg-primary">22</span>
                                    </div>
                                    <div class="personal-grid" id="grid-bloque3"></div>
                                </div>
                                
                                <!-- BLOQUE 4 -->
                                <div id="personal-bloque4" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0">
                                            <i class="fas fa-users me-2"></i>BLOQUE 4 - Personal
                                        </h6>
                                        <span class="badge bg-primary">28</span>
                                    </div>
                                    <div class="personal-grid" id="grid-bloque4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Confirmación Mejorado -->
                <div class="modal fade" id="confirmModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-success text-white border-0">
                                <h6 class="modal-title fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar Atención
                                </h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-4">
                                <i class="fas fa-utensils fa-4x text-success mb-3"></i>
                                <h5 id="modal-empleado" class="fw-bold mb-2"></h5>
                                <p class="text-muted mb-0">¿Marcar esta comida como atendida?</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-success" id="confirmAtender">Sí, Atender</button>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'complementos'): ?>
                <!-- MÓDULO: COMPLEMENTOS MEJORADO -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium">
                        <h3 class="card-title-premium">
                            <i class="fas fa-mug-hot"></i>
                            <span>Complementos - HOY</span>
                        </h3>
                        <div class="d-flex gap-2">
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-complement btn-sm" onclick="cargarComplementosDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card complement">
                                <div class="stat-icon complement">
                                    <i class="fas fa-mug-hot"></i>
                                </div>
                                <h3 id="total-complementos" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Total Complementos</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card success">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="complementos-atendidos" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Atendidos Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card warning">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 id="complementos-pendientes" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Pendientes Ahora</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold d-flex align-items-center">
                            <i class="fas fa-list me-3 text-primary"></i>Lista de Complementos
                            <span class="badge bg-warning ms-3" id="notification-count-complementos" style="display: none;">0</span>
                        </h4>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" onclick="filtrarComplementos('todas')">Todas</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filtrarComplementos('pendientes')">Pendientes</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filtrarComplementos('atendidas')">Atendidas</button>
                        </div>
                    </div>
                    
                    <div id="vista-complementos" class="row g-4">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando complementos...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Confirmación Complementos Mejorado -->
                <div class="modal fade" id="confirmModalComplemento" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header" style="background: var(--complement-color); color: white;" class="border-0">
                                <h5 class="modal-title fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar Atención
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-4">
                                <i class="fas fa-mug-hot fa-4x mb-3" style="color: var(--complement-color);"></i>
                                <h5 id="modal-empleado-complemento" class="fw-bold mb-3"></h5>
                                <div class="alert alert-info border-0">
                                    <i class="fas fa-mug-hot me-2"></i>
                                    <span id="modal-complemento" class="fw-bold"></span>
                                </div>
                                <p class="text-muted">¿Marcar este complemento como atendido?</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-success" id="confirmAtenderComplemento">Sí, Atender</button>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'registros'): ?>
                <!-- MÓDULO: REGISTROS DE COMIDA MEJORADO -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium">
                        <h3 class="card-title-premium">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Registros de Comida - HOY</span>
                        </h3>
                        <div class="d-flex gap-2">
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-premium btn-sm" onclick="cargarRegistrosDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h3 id="total-registros" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Total Registros Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card success">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="registros-atendidos" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Atendidos Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card warning">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 id="registros-pendientes" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Pendientes Ahora</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-registro">
                        <h4 class="fw-bold mb-4">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Nuevo Registro
                        </h4>
                        <form id="formCrearRegistro">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Nombre</label>
                                    <select name="nombre_corto" class="form-select" required>
                                        <option value="">Seleccionar empleado...</option>
                                        <?php foreach ($nombresCompletos as $nombre => $completo): ?>
                                            <option value="<?php echo htmlspecialchars($nombre); ?>"><?php echo htmlspecialchars($nombre); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Fecha (dd-mm-yyyy)</label>
                                    <div class="datetime-input">
                                        <input type="text" name="fecha_real" class="form-control flatpickr-date" 
                                               id="fechaPicker" required placeholder="dd-mm-yyyy">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Hora (HH:MM:SS)</label>
                                    <div class="datetime-input">
                                        <input type="text" name="hora_real" class="form-control flatpickr-time" 
                                               id="horaPicker" required placeholder="HH:MM:SS">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn-premium w-100">
                                        <i class="fas fa-save me-2"></i>Guardar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold d-flex align-items-center">
                            <i class="fas fa-list me-3 text-primary"></i>Registros de Hoy
                            <span class="badge bg-warning ms-3" id="notification-count-registros" style="display: none;">0</span>
                        </h4>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary active" onclick="filtrarRegistros('todas')">Todas</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filtrarRegistros('pendientes')">Pendientes</button>
                            <button type="button" class="btn btn-outline-primary" onclick="filtrarRegistros('atendidas')">Atendidas</button>
                        </div>
                    </div>
                    
                    <div id="vista-registros" class="row g-4">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando registros...</p>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'asignar'): ?>
                <!-- MÓDULO: ASIGNAR COMIDAS MEJORADO -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium">
                        <h3 class="card-title-premium">
                            <i class="fas fa-tasks"></i>
                            <span>Cancelaciones Asignadas - HOY</span>
                        </h3>
                        <div class="d-flex gap-2">
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-premium btn-sm" onclick="cargarCancelaciones()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-list"></i>
                                </div>
                                <h3 id="total-cancelaciones" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Cancelaciones Asignadas</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card success">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="cancelaciones-aprobadas" class="fw-bold mb-1">0</h3>
                                <p class="text-muted mb-0">Aprobadas</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card" style="border-top-color: #6c757d;">
                                <div class="stat-icon" style="background: linear-gradient(145deg, #6c757d, #495057);">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3 class="fw-bold mb-1"><?php echo date('d/m/Y'); ?></h3>
                                <p class="text-muted mb-0">Fecha de Hoy</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="vista-cancelaciones" class="row g-4">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando cancelaciones asignadas...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal para Asignar (Mantenido por compatibilidad) -->
                <div class="modal fade" id="asignarModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-primary text-white border-0">
                                <h5 class="modal-title fw-bold">
                                    <i class="fas fa-user-plus me-2"></i>Asignar Cancelación
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Empleado</label>
                                    <select class="form-select" id="select-empleado">
                                        <option value="">Seleccionar empleado...</option>
                                        <?php foreach ($empleados as $empleado): ?>
                                            <option value="<?php echo $empleado['Id_Empleado']; ?>">
                                                <?php echo htmlspecialchars($empleado['Nombre'] . ' - ' . $empleado['Area']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="asignar-info" class="alert alert-info"></div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </button>
                                <button type="button" class="btn btn-primary" id="confirmAsignar">
                                    <i class="fas fa-user-plus me-2"></i>Asignar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'configuracion'): ?>
                <!-- MÓDULO: CONFIGURACIÓN MEJORADO -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium">
                        <h3 class="card-title-premium">
                            <i class="fas fa-cog"></i>
                            <span>Configuración del Sistema</span>
                        </h3>
                    </div>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold mb-4">
                                        <i class="fas fa-user me-2"></i>Información del Usuario
                                    </h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Nombre</label>
                                        <input type="text" class="form-control" value="<?php echo $_SESSION['usuario']['nombre']; ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Rol</label>
                                        <input type="text" class="form-control" value="Chef Principal" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Último acceso</label>
                                        <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i:s'); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold mb-4">
                                        <i class="fas fa-database me-2"></i>Conexión a Base de Datos
                                    </h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Servidor</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(getenv('DB_COMEDOR_SERVER')); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Base de Datos</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(getenv('DB_COMEDOR_DATABASE')); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small">Usuario BD</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(getenv('DB_COMEDOR_USERNAME')); ?>" readonly>
                                    </div>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>Estado: <strong>Conectado</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info border-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Dashboard Chef Premium - VERSIÓN ACTUALIZADA</strong> | 
                                <span class="badge" style="background: <?php echo $colorTurno; ?>; color: black;">
                                    <i class="fas <?php echo date('H') < 12 ? 'fa-sun' : 'fa-utensils'; ?> me-1"></i>
                                    MODO <?php echo $turnoActual; ?>
                                </span>
                                | Badges de estado corregidos - AHORA MUESTRA "AGENDADO"
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botón flotante actualizar -->
        <button class="floating-btn" onclick="cargarPaginaActual()">
            <i class="fas fa-sync-alt"></i>
        </button>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        <?php if (isset($_SESSION['usuario']) && $_SESSION['usuario']['logueado']): ?>
        
        // ============ VARIABLES GLOBALES ============
        let comidasData = [];
        let complementosData = [];
        let cancelacionesData = [];
        let registrosData = [];
        let empleadosData = <?php echo json_encode($empleados); ?>;
        let filtroActual = 'todas';
        let filtroComplementosActual = 'todas';
        let filtroRegistrosActual = 'todas';
        let currentComida = null;
        let currentComplemento = null;
        let bloqueActualData = <?php echo json_encode($bloqueActual); ?>;
        const nombresCompletos = <?php echo json_encode($nombresCompletos); ?>;
        
        // Datos de personal por bloque
        const personalBloque1 = <?php echo json_encode($personalBloque1); ?>;
        const personalBloque2 = <?php echo json_encode($personalBloque2); ?>;
        const personalBloque3 = <?php echo json_encode($personalBloque3); ?>;
        const personalBloque4 = <?php echo json_encode($personalBloque4); ?>;
        const mapaPedidos = <?php echo json_encode($mapaPedidos); ?>;
        
        // ============ INICIALIZACIÓN ============
        document.addEventListener('DOMContentLoaded', function() {
            // Eventos comidas
            document.getElementById('confirmAtender')?.addEventListener('click', ejecutarAtencion);
            
            // Eventos complementos
            document.getElementById('confirmAtenderComplemento')?.addEventListener('click', ejecutarAtencionComplemento);
            
            // Eventos registros
            document.getElementById('formCrearRegistro')?.addEventListener('submit', crearRegistroComida);
            
            // Inicializar Flatpickr
            if (typeof flatpickr !== 'undefined') {
                flatpickr('.flatpickr-date', {
                    dateFormat: 'd-m-Y',
                    locale: 'es',
                    defaultDate: 'today',
                    maxDate: 'today'
                });
                
                flatpickr('.flatpickr-time', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i:S',
                    time_24hr: true,
                    defaultDate: '<?php echo date("H:i:s"); ?>'
                });
            }
            
            // Iniciar según página
            if ('<?php echo $pagina_actual; ?>' === 'comidas') {
                iniciarReloj();
                setInterval(actualizarBloqueActual, 60000);
                cargarDashboard();
                setInterval(cargarDashboard, 30000);
            }
            
            if ('<?php echo $pagina_actual; ?>' === 'complementos') {
                cargarComplementosDashboard();
                setInterval(cargarComplementosDashboard, 30000);
            }
            
            if ('<?php echo $pagina_actual; ?>' === 'registros') {
                cargarRegistrosDashboard();
                setInterval(cargarRegistrosDashboard, 30000);
            }
            
            if ('<?php echo $pagina_actual; ?>' === 'asignar') {
                cargarCancelaciones();
                setInterval(cargarCancelaciones, 30000);
            }
        });
        
        // ============ FUNCIONES COMUNES ============
        function toggleSidebar() {
            document.getElementById('sidebar')?.classList.toggle('show');
        }
        
        function cargarPaginaActual() {
            if ('<?php echo $pagina_actual; ?>' === 'comidas') cargarDashboard();
            else if ('<?php echo $pagina_actual; ?>' === 'complementos') cargarComplementosDashboard();
            else if ('<?php echo $pagina_actual; ?>' === 'registros') cargarRegistrosDashboard();
            else if ('<?php echo $pagina_actual; ?>' === 'asignar') cargarCancelaciones();
        }
        
        function mostrarError(mensaje) {
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: mensaje, 
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Entendido'
            });
        }
        
        function mostrarAlerta(titulo, mensaje, tipo = 'success') {
            Swal.fire({ 
                icon: tipo, 
                title: titulo, 
                text: mensaje, 
                timer: 2000, 
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }
        
        // ============ FUNCIONES MÓDULO COMIDAS ============
        function iniciarReloj() {
            function actualizarReloj() {
                const reloj = document.getElementById('relojDigital');
                if (reloj) {
                    reloj.textContent = new Date().toLocaleTimeString('es-MX', { hour12: false });
                }
            }
            actualizarReloj();
            setInterval(actualizarReloj, 1000);
        }
        
        function mostrarBloque(bloque) {
            document.getElementById('personal-bloque1').style.display = 'none';
            document.getElementById('personal-bloque2').style.display = 'none';
            document.getElementById('personal-bloque3').style.display = 'none';
            document.getElementById('personal-bloque4').style.display = 'none';
            document.getElementById('personal-' + bloque).style.display = 'block';
            
            document.querySelectorAll('.bloque-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById('tab-' + bloque).classList.add('active');
        }
        
        async function actualizarBloqueActual() {
            try {
                const response = await fetch('?action=get_bloque_actual');
                const data = await response.json();
                if (data.success) {
                    bloqueActualData = data.bloque;
                    actualizarVistaBloque();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function actualizarVistaBloque() {
            const contenedor = document.getElementById('contenedorBloqueActual');
            if (!contenedor) return;
            
            let html = '';
            if (bloqueActualData.activo) {
                html = `
                    <div class="bloque-activo">
                        <span class="badge bg-warning text-dark mb-2"><i class="fas fa-bell me-1"></i>ACTIVO AHORA</span>
                        <h5 class="text-white mb-1">${bloqueActualData.nombre}</h5>
                        <p class="text-white-50 mb-0 small"><i class="fas fa-clock me-1"></i>${bloqueActualData.inicio} - ${bloqueActualData.fin}</p>
                    </div>
                `;
            } else if (bloqueActualData.mensaje?.includes('Próximo')) {
                html = `
                    <div class="bg-white-10 p-4 rounded" style="background: rgba(255,255,255,0.1);">
                        <span class="badge bg-secondary mb-2"><i class="fas fa-hourglass-half me-1"></i>PRÓXIMO</span>
                        <h5 class="text-white mb-1">${bloqueActualData.mensaje}</h5>
                        <p class="text-white-50 mb-0 small"><i class="fas fa-clock me-1"></i>${bloqueActualData.inicio || '--:--'} - ${bloqueActualData.fin || '--:--'}</p>
                    </div>
                `;
            } else {
                html = `
                    <div class="bg-white-10 p-4 rounded" style="background: rgba(255,255,255,0.1);">
                        <span class="badge bg-secondary mb-2"><i class="fas fa-moon me-1"></i>DESCANSO</span>
                        <h5 class="text-white mb-0">${bloqueActualData.mensaje || 'No hay turno'}</h5>
                    </div>
                `;
            }
            contenedor.innerHTML = html;
        }
        
        async function cargarDashboard() {
            try {
                await Promise.all([cargarEstadisticas(), cargarComidas()]);
                const boton = document.querySelector('.btn-premium i.fa-sync-alt');
                if (boton) {
                    boton.classList.add('fa-spin');
                    setTimeout(() => boton.classList.remove('fa-spin'), 1000);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al cargar datos');
            }
        }
        
        async function cargarEstadisticas() {
            try {
                const response = await fetch('?action=get_estadisticas');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.estadisticas;
                    const pendientes = stats.pendientes || 0;
                    const atendidos = stats.atendidos || 0;
                    const total = stats.total || 0;
                    const porcentaje = total > 0 ? Math.round((atendidos / total) * 100) : 0;
                    
                    document.getElementById('comidas-pendientes').textContent = pendientes;
                    document.getElementById('comidas-atendidas').textContent = atendidos;
                    
                    const badge = document.getElementById('notification-count');
                    if (badge) {
                        badge.textContent = pendientes;
                        badge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                    }
                    
                    const sidebarBadge = document.getElementById('sidebar-comidas-count');
                    if (sidebarBadge) {
                        sidebarBadge.textContent = pendientes;
                        sidebarBadge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                    }
                    
                    const pendientesHorario = document.getElementById('pendientes-horario');
                    if (pendientesHorario) {
                        pendientesHorario.innerHTML = bloqueActualData?.activo 
                            ? `<i class="fas fa-bell me-1"></i>Bloque: ${pendientes}`
                            : `<i class="fas fa-hourglass me-1"></i>Fuera: ${pendientes}`;
                    }
                    
                    const porcentajeElem = document.getElementById('atendidas-porcentaje');
                    if (porcentajeElem) {
                        porcentajeElem.innerHTML = `<i class="fas fa-chart-line me-1"></i>${porcentaje}%`;
                    }
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        }
        
        async function cargarComidas() {
            try {
                const response = await fetch('?action=get_comidas');
                const data = await response.json();
                
                if (data.success) {
                    comidasData = data.comidas;
                    actualizarVistaComidas();
                    actualizarPersonalConEstados();
                }
            } catch (error) {
                console.error('Error cargando comidas:', error);
                mostrarError('Error al cargar comidas');
            }
        }
        
        // ============ FUNCIÓN PRINCIPAL CORREGIDA - AHORA MUESTRA "AGENDADO" ============
        function actualizarPersonalConEstados() {
            const horaActual = new Date().getHours();
            const esManana = horaActual < 12;
            const turnoActual = esManana ? 'DESAYUNO' : 'COMIDA';
            
            function renderPersonalItem(persona, index) {
                const nombre = persona.nombre;
                const depto = persona.depto;
                const numero = String(index + 1).padStart(2, '0');
                const nombreNormalizado = nombre.trim().toUpperCase();
                
                // Verificar si tiene el turno actual agendado en mapaPedidos
                let tieneTurno = false;
                if (mapaPedidos[nombreNormalizado]) {
                    for (const pedido of mapaPedidos[nombreNormalizado]) {
                        if (pedido.consumo === turnoActual) {
                            tieneTurno = true;
                            break;
                        }
                    }
                }
                
                // Verificar si ya fue atendido o está en fila
                let yaAtendido = false;
                let enFila = false;
                
                for (const comida of comidasData) {
                    // Comparación flexible de nombres
                    if (comida.Nombre) {
                        const nombreComida = comida.Nombre.toUpperCase().trim();
                        if (nombreComida.includes(nombreNormalizado) || nombreNormalizado.includes(nombreComida)) {
                            if (comida.Estatus === 'ATENDIDO') {
                                yaAtendido = true;
                                break;
                            } else if (!comida.Estatus || comida.Estatus === 'PENDIENTE') {
                                enFila = true;
                                // No break aquí porque podría haber otro registro atendido
                            }
                        }
                    }
                }
                
                // Construir badge según estado (orden de prioridad)
                let badgeHtml = '';
                if (yaAtendido) {
                    badgeHtml = '<span class="badge-atendido"><i class="fas fa-check-circle me-1"></i>ATENDIDO</span>';
                } else if (enFila) {
                    badgeHtml = '<span class="badge-pendiente"><i class="fas fa-clock me-1"></i>EN FILA</span>';
                } else if (tieneTurno) {
                    // Color dinámico según el turno
                    const bgColor = esManana ? 'rgba(255, 193, 7, 0.15)' : 'rgba(33, 150, 243, 0.15)';
                    const textColor = esManana ? '#856404' : '#0c5460';
                    const borderColor = esManana ? '#ffeeba' : '#bee5eb';
                    const iconoTurno = esManana ? 'fa-sun' : 'fa-utensils';
                    
                    badgeHtml = `<span class="badge-consumo" style="background: ${bgColor}; color: ${textColor}; border: 1px solid ${borderColor};">
                        <i class="fas ${iconoTurno} me-1"></i>${turnoActual} AGENDADO
                    </span>`;
                } else {
                    badgeHtml = '<span class="badge-sinregistro"><i class="fas fa-hourglass me-1"></i>SIN REGISTRO</span>';
                }
                
                return `
                    <div class="personal-item">
                        <span class="personal-numero">#${numero}</span>
                        <span class="personal-nombre" title="${nombre}">${nombre}</span>
                        <span class="personal-depto" title="${depto}">${depto}</span>
                        ${badgeHtml}
                    </div>
                `;
            }
            
            // Renderizar cada bloque
            const grid1 = document.getElementById('grid-bloque1');
            if (grid1) {
                grid1.innerHTML = personalBloque1.map((p, i) => renderPersonalItem(p, i)).join('');
            }
            
            const grid2 = document.getElementById('grid-bloque2');
            if (grid2) {
                grid2.innerHTML = personalBloque2.map((p, i) => renderPersonalItem(p, i)).join('');
            }
            
            const grid3 = document.getElementById('grid-bloque3');
            if (grid3) {
                grid3.innerHTML = personalBloque3.map((p, i) => renderPersonalItem(p, i)).join('');
            }
            
            const grid4 = document.getElementById('grid-bloque4');
            if (grid4) {
                grid4.innerHTML = personalBloque4.map((p, i) => renderPersonalItem(p, i)).join('');
            }
        }
        
        function filtrarComidas(tipo) {
            filtroActual = tipo;
            document.querySelectorAll('.btn-outline-primary').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            actualizarVistaComidas();
        }
        
        function actualizarVistaComidas() {
            const container = document.getElementById('vista-comidas');
            if (!container) return;
            
            let filtradas = comidasData;
            if (filtroActual === 'pendientes') filtradas = comidasData.filter(c => !c.Estatus || c.Estatus === 'PENDIENTE');
            if (filtroActual === 'atendidas') filtradas = comidasData.filter(c => c.Estatus === 'ATENDIDO');
            
            if (filtradas.length === 0) {
                let mensaje = filtroActual === 'todas' ? 'No hay comidas registradas hoy' : 
                             filtroActual === 'pendientes' ? '¡Todo atendido! No hay comidas pendientes' : 
                             'No hay comidas atendidas aún';
                
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <p class="text-muted mt-2">${mensaje}</p>
                        <button class="btn btn-primary btn-sm mt-2" onclick="cargarDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filtradas.map((c, i) => `
                <div class="comida-card ${c.Estatus === 'ATENDIDO' ? 'atendida' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-primary">#${i + 1}</span>
                        <span class="badge ${c.Estatus === 'ATENDIDO' ? 'bg-success' : 'bg-warning'}">
                            ${c.Estatus === 'ATENDIDO' ? 'ATENDIDA' : 'PENDIENTE'}
                        </span>
                    </div>
                    <h6 class="fw-bold mb-2">${c.Nombre?.substring(0, 35) || 'Sin nombre'}${c.Nombre?.length > 35 ? '...' : ''}</h6>
                    <div class="small text-muted mb-3">
                        <span class="area-badge">${c.Area || 'Sin área'}</span>
                        <span class="ms-2"><i class="fas fa-clock me-1"></i>${c.Hora_Corta || '--:--'}</span>
                    </div>
                    ${c.Estatus !== 'ATENDIDO' ? 
                        `<button class="btn-premium w-100" onclick="mostrarConfirmacionAtender('${c.Id_Unico}')">
                            <i class="fas fa-check-circle me-1"></i>ATENDER
                        </button>` : 
                        `<button class="btn btn-secondary w-100" disabled>ATENDIDA</button>`
                    }
                </div>
            `).join('');
        }
        
        function mostrarConfirmacionAtender(id) {
            currentComida = comidasData.find(c => c.Id_Unico === id);
            if (currentComida) {
                document.getElementById('modal-empleado').textContent = currentComida.Nombre?.substring(0, 40) || 'Empleado';
                new bootstrap.Modal(document.getElementById('confirmModal')).show();
            }
        }
        
        async function ejecutarAtencion() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            try {
                const formData = new FormData();
                formData.append('nombre', currentComida.Nombre);
                formData.append('hora_entrada', currentComida.Hora_Entrada_SQL);
                formData.append('fecha_hora', currentComida.Fecha_Hora);
                
                const response = await fetch('?action=marcar_atendido', { method: 'POST', body: formData });
                const data = await response.json();
                
                modal.hide();
                
                if (data.success) {
                    mostrarAlerta('Éxito', data.message, 'success');
                    await cargarDashboard();
                    await actualizarBloqueActual();
                } else {
                    mostrarError('Error al atender comida: ' + data.message);
                }
            } catch (error) {
                modal.hide();
                mostrarError('Error al atender comida');
            }
        }
        
        // ============ FUNCIONES MÓDULO COMPLEMENTOS ============
        async function cargarComplementosDashboard() {
            try {
                await Promise.all([cargarEstadisticasComplementos(), cargarComplementos()]);
                const boton = document.querySelector('.btn-complement i.fa-sync-alt');
                if (boton) {
                    boton.classList.add('fa-spin');
                    setTimeout(() => boton.classList.remove('fa-spin'), 1000);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al cargar complementos');
            }
        }
        
        async function cargarEstadisticasComplementos() {
            try {
                const response = await fetch('?action=get_estadisticas_complementos');
                const data = await response.json();
                if (data.success) {
                    const stats = data.estadisticas;
                    document.getElementById('total-complementos').textContent = stats.total || 0;
                    document.getElementById('complementos-atendidos').textContent = stats.atendidos || 0;
                    document.getElementById('complementos-pendientes').textContent = stats.pendientes || 0;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function cargarComplementos() {
            try {
                const response = await fetch('?action=get_complementos');
                const data = await response.json();
                
                if (data.success) {
                    complementosData = data.complementos;
                    actualizarVistaComplementos();
                    
                    const pendientes = complementosData.filter(c => c.estatus === 'PENDIENTE').length;
                    const badge = document.getElementById('notification-count-complementos');
                    if (badge) {
                        badge.textContent = pendientes;
                        badge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                    }
                    
                    const sidebarBadge = document.getElementById('sidebar-complementos-count');
                    if (sidebarBadge) {
                        sidebarBadge.textContent = pendientes;
                        sidebarBadge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                    }
                }
            } catch (error) {
                console.error('Error cargando complementos:', error);
                mostrarError('Error al cargar complementos');
            }
        }
        
        function filtrarComplementos(tipo) {
            filtroComplementosActual = tipo;
            document.querySelectorAll('.btn-outline-primary').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            actualizarVistaComplementos();
        }
        
        function actualizarVistaComplementos() {
            const container = document.getElementById('vista-complementos');
            if (!container) return;
            
            let filtradas = complementosData;
            if (filtroComplementosActual === 'pendientes') filtradas = complementosData.filter(c => c.estatus === 'PENDIENTE');
            if (filtroComplementosActual === 'atendidas') filtradas = complementosData.filter(c => c.estatus === 'ATENDIDO');
            
            if (filtradas.length === 0) {
                let mensaje = filtroComplementosActual === 'todas' ? 'No hay complementos registrados hoy' :
                             filtroComplementosActual === 'pendientes' ? '¡Todo atendido! No hay complementos pendientes' :
                             'No hay complementos atendidos aún';
                
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-mug-hot"></i>
                            <h4 class="text-muted mt-3">${mensaje}</h4>
                            <button class="btn btn-primary mt-3" onclick="cargarComplementosDashboard()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            filtradas.forEach((comp, index) => {
                const esAtendido = comp.estatus === 'ATENDIDO';
                html += `<div class="col-md-6 col-lg-4">
                    <div class="complemento-card fade-in ${esAtendido ? 'atendida' : ''}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary">#${index + 1}</span>
                            <span class="badge ${esAtendido ? 'bg-success' : 'bg-warning'}">
                                <i class="fas ${esAtendido ? 'fa-check-circle' : 'fa-clock'} me-1"></i>${comp.estatus}
                            </span>
                        </div>
                        <h5 class="fw-bold mb-2">${comp.nombre || 'Sin nombre'}</h5>
                        <div class="mb-3">
                            <span class="badge bg-info text-white">
                                <i class="fas fa-mug-hot me-1"></i>${comp.complemento || 'Sin especificar'}
                            </span>
                        </div>
                        <div class="mb-3 small">
                            <div><i class="fas fa-calendar me-1 text-muted"></i>Fecha: ${comp.fecha_formateada || '--/--/----'}</div>
                            <div><i class="fas fa-clock me-1 text-muted"></i>Hora: ${comp.hora_corta || '--:--'}</div>
                            ${esAtendido && comp.fecha_atendido_formateada ? 
                                `<div class="text-success"><i class="fas fa-user-check me-1"></i>Atendido: ${comp.fecha_atendido_formateada}</div>` : ''}
                        </div>
                        ${!esAtendido ? 
                            `<button class="btn-complement w-100" onclick="mostrarConfirmacionAtenderComplemento('${comp.Id_Unico}')">
                                <i class="fas fa-check-circle me-2"></i>ATENDER
                            </button>` : 
                            `<button class="btn btn-secondary w-100" disabled>YA ATENDIDO</button>`
                        }
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }
        
        function mostrarConfirmacionAtenderComplemento(id) {
            currentComplemento = complementosData.find(c => c.Id_Unico === id);
            if (currentComplemento) {
                document.getElementById('modal-empleado-complemento').textContent = currentComplemento.nombre;
                document.getElementById('modal-complemento').textContent = currentComplemento.complemento;
                new bootstrap.Modal(document.getElementById('confirmModalComplemento')).show();
            }
        }
        
        async function ejecutarAtencionComplemento() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModalComplemento'));
            try {
                const formData = new FormData();
                formData.append('nombre', currentComplemento.nombre);
                formData.append('complemento', currentComplemento.complemento);
                formData.append('fecha', currentComplemento.fecha_formateada);
                formData.append('hora', currentComplemento.hora_completa);
                
                const response = await fetch('?action=marcar_complemento_atendido', { method: 'POST', body: formData });
                const data = await response.json();
                
                modal.hide();
                
                if (data.success) {
                    mostrarAlerta('Éxito', data.message, 'success');
                    await cargarComplementosDashboard();
                } else {
                    mostrarError('Error al atender complemento: ' + data.message);
                }
            } catch (error) {
                modal.hide();
                mostrarError('Error al atender complemento');
            }
        }
        
        // ============ FUNCIONES MÓDULO REGISTROS ============
        async function cargarRegistrosDashboard() {
            try {
                await cargarRegistros();
                const boton = document.querySelector('.btn-premium i.fa-sync-alt');
                if (boton) {
                    boton.classList.add('fa-spin');
                    setTimeout(() => boton.classList.remove('fa-spin'), 1000);
                }
                
                const total = registrosData.length;
                const atendidos = registrosData.filter(r => r.Estatus === 'ATENDIDO').length;
                const pendientes = total - atendidos;
                
                document.getElementById('total-registros').textContent = total || 0;
                document.getElementById('registros-atendidos').textContent = atendidos || 0;
                document.getElementById('registros-pendientes').textContent = pendientes || 0;
                
                const badge = document.getElementById('notification-count-registros');
                if (badge) {
                    badge.textContent = pendientes;
                    badge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                }
                
                const sidebarBadge = document.getElementById('sidebar-registros-count');
                if (sidebarBadge) {
                    sidebarBadge.textContent = pendientes;
                    sidebarBadge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al cargar registros');
            }
        }
        
        async function cargarRegistros() {
            try {
                const response = await fetch('?action=get_registros_hoy');
                const data = await response.json();
                if (data.success) {
                    registrosData = data.registros;
                    actualizarVistaRegistros();
                }
            } catch (error) {
                console.error('Error cargando registros:', error);
            }
        }
        
        function filtrarRegistros(tipo) {
            filtroRegistrosActual = tipo;
            document.querySelectorAll('.btn-outline-primary').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            actualizarVistaRegistros();
        }
        
        function actualizarVistaRegistros() {
            const container = document.getElementById('vista-registros');
            if (!container) return;
            
            let filtradas = registrosData;
            if (filtroRegistrosActual === 'pendientes') filtradas = registrosData.filter(r => r.Estatus === 'PENDIENTE');
            if (filtroRegistrosActual === 'atendidas') filtradas = registrosData.filter(r => r.Estatus === 'ATENDIDO');
            
            if (filtradas.length === 0) {
                let mensaje = filtroRegistrosActual === 'todas' ? 'No hay registros hoy' :
                             filtroRegistrosActual === 'pendientes' ? '¡Todo atendido! No hay registros pendientes' :
                             'No hay registros atendidos aún';
                
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h4 class="text-muted mt-3">${mensaje}</h4>
                            <button class="btn btn-primary mt-3" onclick="cargarRegistrosDashboard()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            filtradas.forEach((reg, index) => {
                const esAtendido = reg.Estatus === 'ATENDIDO';
                html += `<div class="col-md-6 col-lg-4">
                    <div class="registro-card fade-in ${esAtendido ? 'atendida' : ''}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary">#${index + 1}</span>
                            <span class="badge ${esAtendido ? 'bg-success' : 'bg-warning'}">${esAtendido ? 'ATENDIDO' : 'PENDIENTE'}</span>
                        </div>
                        <h5 class="fw-bold mb-2">${reg.Nombre_Limpio || reg.Nombre || 'Sin nombre'}</h5>
                        <div class="mb-3 small">
                            <div><i class="fas fa-clock me-1 text-muted"></i>Hora comida: ${reg.Hora_Comida || '--:--:--'}</div>
                            ${esAtendido ? 
                                `<div class="text-success"><i class="fas fa-user-check me-1"></i>Atendido: ${reg.Fecha_Atendido_Formateada || ''}</div>
                                ${reg.Usuario_Atiende ? `<div class="text-muted"><i class="fas fa-user-tie me-1"></i>Por: ${reg.Usuario_Atiende}</div>` : ''}` : ''}
                        </div>
                        ${!esAtendido ? 
                            `<div class="alert alert-warning py-2"><i class="fas fa-info-circle me-2"></i>Pendiente en "Comidas a Servir"</div>` : 
                            `<div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i>Atendido</div>`
                        }
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }
        
        async function crearRegistroComida(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            if (!formData.get('nombre_corto')) {
                mostrarError('Debe seleccionar un empleado');
                return;
            }
            
            const fecha = formData.get('fecha_real');
            if (!/^\d{2}-\d{2}-\d{4}$/.test(fecha)) {
                mostrarError('Formato de fecha incorrecto. Use dd-mm-yyyy');
                return;
            }
            
            const hora = formData.get('hora_real');
            if (!/^\d{2}:\d{2}:\d{2}$/.test(hora)) {
                mostrarError('Formato de hora incorrecto. Use HH:MM:SS');
                return;
            }
            
            try {
                const response = await fetch('?action=crear_registro_comida', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    mostrarAlerta('Éxito', data.message, 'success');
                    form.reset();
                    
                    const fechaPicker = document.getElementById('fechaPicker');
                    const horaPicker = document.getElementById('horaPicker');
                    if (fechaPicker?._flatpickr) fechaPicker._flatpickr.setDate('today');
                    if (horaPicker?._flatpickr) horaPicker._flatpickr.setDate('<?php echo date("H:i:s"); ?>');
                    
                    await cargarRegistrosDashboard();
                } else {
                    mostrarError('Error al crear registro: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al crear registro');
            }
        }
        
        // ============ FUNCIONES MÓDULO ASIGNAR ============
        async function cargarCancelaciones() {
            try {
                const response = await fetch('?action=get_cancelaciones');
                const data = await response.json();
                
                if (data.success) {
                    cancelacionesData = data.cancelaciones;
                    actualizarVistaCancelaciones();
                    
                    const total = cancelacionesData.length;
                    document.getElementById('total-cancelaciones').textContent = total;
                    document.getElementById('cancelaciones-aprobadas').textContent = total;
                    
                    const boton = document.querySelector('.btn-premium i.fa-sync-alt');
                    if (boton) {
                        boton.classList.add('fa-spin');
                        setTimeout(() => boton.classList.remove('fa-spin'), 1000);
                    }
                }
            } catch (error) {
                console.error('Error cargando cancelaciones:', error);
                mostrarError('Error al cargar cancelaciones');
            }
        }
        
        function actualizarVistaCancelaciones() {
            const container = document.getElementById('vista-cancelaciones');
            if (!container) return;
            
            if (cancelacionesData.length === 0) {
                container.innerHTML = '<div class="col-12"><div class="empty-state"><i class="fas fa-inbox"></i><h4 class="text-muted mt-3">No hay cancelaciones asignadas hoy</h4></div></div>';
                return;
            }
            
            let html = '';
            cancelacionesData.forEach((can, index) => {
                html += `<div class="col-md-6 col-lg-4">
                    <div class="cancelacion-card fade-in asignada">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary">#${index + 1}</span>
                            <span class="badge bg-info ms-1">${can.TIPO_CONSUMO || 'SIN TIPO'}</span>
                            <span class="badge bg-success">ASIGNADA</span>
                        </div>
                        <h5 class="fw-bold mb-2">${can.NOMBRE || 'Sin nombre'}</h5>
                        <div class="mb-3 small">
                            <div><span class="fw-bold">Depto:</span> ${can.DEPARTAMENTO || 'N/A'}</div>
                            <div><span class="fw-bold">Jefe:</span> ${can.JEFE || 'N/A'}</div>
                            <div><span class="fw-bold">Fecha:</span> ${can.FECHA_FORMATEADA || 'N/A'}</div>
                            ${can.CAUSA ? `<div><span class="fw-bold">Causa:</span> ${can.CAUSA}</div>` : ''}
                        </div>
                        <div class="alert alert-success py-2">
                            <i class="fas fa-user-check me-2"></i><strong>Asignado a:</strong> ${can.USUARIO_APARTA || 'No asignado'}<br>
                            <small class="text-muted"><i class="fas fa-calendar-check me-1"></i>${can.FECHA_APARTADO_FORMATEADA || 'N/A'}</small>
                        </div>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }
        
        <?php endif; ?>
    </script>
</body>
</html>