<?php
// =============================================
// DASHBOARD CHEF - SISTEMA COMPLETO CON COMPLEMENTOS Y REGISTROS
// =============================================

session_start();

// Configuración de conexión a la base de datos
$serverName = "DESAROLLO-BACRO\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "Comedor",
    "Uid" => "Larome03", 
    "PWD" => "Larome03",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// Función para obtener conexión
function getConnection() {
    global $serverName, $connectionOptions;
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
    }
    return $conn;
}

// =============================================
// MANEJO DE LOGIN Y SESIÓN
// =============================================

// Verificar login
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

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Determinar página activa
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
            
            // Crear un ID único para esta comida
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
            // Formatear fecha
            if (!empty($row['fecha'])) {
                if ($row['fecha'] instanceof DateTime) {
                    $row['fecha_formateada'] = $row['fecha']->format('d/m/Y');
                    $row['fecha_sql'] = $row['fecha']->format('Y-m-d');
                } else {
                    $row['fecha_formateada'] = $row['fecha'];
                    $row['fecha_sql'] = date('Y-m-d', strtotime(str_replace('/', '-', $row['fecha'])));
                }
            }
            
            // Formatear hora
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
            
            // Formatear fecha de atención si existe
            if (!empty($row['fecha_atendido']) && $row['fecha_atendido'] instanceof DateTime) {
                $row['fecha_atendido_formateada'] = $row['fecha_atendido']->format('H:i:s');
            }
            
            $row['estatus'] = !empty($row['atendido']) && $row['atendido'] == 1 ? 'ATENDIDO' : 'PENDIENTE';
            $row['nombre'] = $row['Nombre_Limpio'];
            
            // Crear un ID único para este complemento
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
    $fecha = trim($_POST['fecha']);  // Formato: dd/mm/yyyy
    $hora = trim($_POST['hora']);    // Formato: HH:MM
    
    // Asegurar formato de hora completo
    if (strlen($hora) == 5) {
        $hora .= ':00';  // Convierte "08:04" a "08:04:00"
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
    
    // $fecha debe estar en formato dd/mm/yyyy (ej: "12/01/2026")
    // $hora debe estar en formato HH:MM o HH:MM:SS
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
            AND ESTATUS='APROBADO'
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

// Mapeo de nombres completos
$nombresCompletos = [
    "ALEJANDRA CRUZ" => "NOMBRE: ALEJANDRA CRUZ N.E: 3 DEPARTAMENTO: DIRECCION AREA: DIRECCION ZONA: ZINACANTEPEC PUESTO: ANALISTA NSS: 0000000000 CEL: 24",
    "ALTA DIRECCION" => "NOMBRE: ALTA DIRECCION N.E: 4 DEPARTAMENTO: DIRECCION AREA: DIRECCION ZONA: ZINACANTEPEC PUESTO: DIRECCION NSS: 0000000000 CEL: 26",
    "CRUZ JOSE LUIS" => "NOMBRE: CRUZ JOSE LUIS N.E: 682 DEPARTAMENTO: OPERACIONES AREA: OPERACIONES ZONA: VALLE DE MEXICO Y TOLUCA PUESTO: TECNICO DE MANTENIMIENTO NSS: 42957406111",
    "CRUZ RODRIGUEZ ALEJANDRO" => "NOMBRE: CRUZ RODRIGUEZ ALEJANDRO N.E: 41 DEPARTAMENTO: CONTABILIDAD AREA: CONTABILIDAD ZONA: ZINACANTEPEC PUESTO: ANALISTA NSS: 39988108666",
    "JURIDICO" => "NOMBRE: JURIDICO N.E: 5 DEPARTAMENTO: PROYECTOS ESPECIALES AREA: JURIDICO ZONA: ZINACANTEPEC PUESTO: ABOGADOS NSS: 0000000000",
    "PALMA TREJO SANDY MARK" => "NOMBRE: PALMA TREJO SANDY MARK N.E: 1101 DEPARTAMENTO: DIRECCION AREA: DIRECCION ZONA: ZINACANTEPEC PUESTO: CHOFER NSS: 81927423350",
    "REYES QUIROZ HILDA" => "NOMBRE: REYES QUIROZ HILDA N.E: 24 DEPARTAMENTO: CONTABILIDAD Y FINANZAS AREA: CONTROL DE EGRESOS ZONA: TOLUCA VIA REMOTA PUESTO: ANALISTA NSS: 18886904574",
    "VIGILANCIA" => "NOMBRE: VIGILANCIA N.E: 105868 DEPARTAMENTO: SERVICIOS GENERALES AREA: VIGILANCIA ZONA: ZINACANTEPEC PUESTO: VIGILANTE NSS: 000000000000"
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
    
    // Validar formato de fecha (dd-mm-yyyy)
    if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $fecha_real)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha incorrecto. Use dd-mm-yyyy']);
        exit;
    }
    
    // Validar formato de hora (HH:MM:SS)
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
        $hora_real,     // TEXTO
        $fecha_real,    // TEXTO
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
            // Limpiar nombre
            $nombreLimpio = $row['Nombre'];
            if (strpos($nombreLimpio, 'NOMBRE:') !== false) {
                $nombreLimpio = preg_replace('/NOMBRE:\s*(.+?)\s+(N\.E:|DEPARTAMENTO:|NSS:|se encuentra registrado para|AREA:)/', '$1', $nombreLimpio);
                $nombreLimpio = trim($nombreLimpio);
            }
            $row['Nombre_Limpio'] = $nombreLimpio;
            
            // Formatear fecha de atención si existe
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
    }
}

// =============================================
// HTML Y DISEÑO
// =============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Chef Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c5aa0;
            --primary-light: #4a7bc8;
            --primary-dark: #1a3d7a;
            --secondary: #FFC107;
            --secondary-light: #FFD54F;
            --secondary-dark: #FF8F00;
            --accent: #6c757d;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.1);
            --shadow-medium: 0 15px 50px rgba(44, 90, 160, 0.15);
            --shadow-dark: 0 20px 60px rgba(0, 0, 0, 0.1);
            --complement-color: #2196F3;
            --complement-light: #64B5F6;
            --complement-dark: #1976D2;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e3f2fd 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Login Page */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 20px;
        }
        
        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-dark);
            padding: 50px;
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.8s ease;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 32px;
            box-shadow: 0 10px 30px rgba(44, 90, 160, 0.4);
        }
        
        /* Navbar */
        .navbar-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--secondary);
            box-shadow: var(--shadow-medium);
            padding: 15px 0;
        }
        
        .navbar-brand-premium {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .navbar-brand-premium i {
            color: var(--secondary);
            margin-right: 10px;
        }
        
        /* Sidebar */
        .sidebar-container {
            position: fixed;
            top: 80px;
            left: 0;
            height: calc(100vh - 80px);
            width: 260px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-right: 1px solid var(--glass-border);
            box-shadow: var(--shadow-light);
            z-index: 100;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .user-info h5 {
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            margin-bottom: 5px;
        }
        
        .menu-item:hover {
            background: rgba(44, 90, 160, 0.08);
            color: var(--primary-dark);
            border-left-color: var(--secondary);
            padding-left: 30px;
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(44, 90, 160, 0.1), transparent);
            color: var(--primary-dark);
            border-left-color: var(--secondary);
            font-weight: 600;
        }
        
        .menu-item i {
            width: 24px;
            margin-right: 15px;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 992px) {
            .sidebar-container {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-container.show {
                transform: translateX(0);
            }
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-light);
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-dark);
        }
        
        .card-header-premium {
            background: transparent;
            border-bottom: 2px solid rgba(44, 90, 160, 0.1);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .card-title-premium {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
        }
        
        .card-title-premium i {
            color: var(--secondary);
            margin-right: 10px;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow-light);
            border-top: 5px solid var(--primary);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
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
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(44, 90, 160, 0.3);
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning) 0%, var(--secondary) 100%);
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
        }
        
        .stat-icon.complement {
            background: linear-gradient(135deg, var(--complement-color) 0%, var(--complement-light) 100%);
        }
        
        /* Buttons */
        .btn-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(44, 90, 160, 0.3);
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 90, 160, 0.4);
            color: white;
        }
        
        .btn-complement {
            background: linear-gradient(135deg, var(--complement-color) 0%, var(--complement-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }
        
        .btn-complement:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
            color: white;
        }
        
        /* Comidas Cards */
        .comida-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(44, 90, 160, 0.08);
            border-left: 5px solid var(--warning);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .comida-card:hover {
            box-shadow: 0 8px 25px rgba(44, 90, 160, 0.15);
            transform: translateY(-3px);
            border-color: var(--warning);
        }
        
        .comida-card.atendida {
            border-left-color: var(--success);
            background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white);
        }
        
        /* Complementos Cards */
        .complemento-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.08);
            border-left: 5px solid var(--complement-color);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .complemento-card:hover {
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.15);
            transform: translateY(-3px);
            border-color: var(--complement-color);
        }
        
        .complemento-card.atendida {
            border-left-color: var(--success);
            background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white);
        }
        
        /* Registros Cards */
        .registro-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(44, 90, 160, 0.08);
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .registro-card:hover {
            box-shadow: 0 8px 25px rgba(44, 90, 160, 0.15);
            transform: translateY(-3px);
            border-color: var(--primary);
        }
        
        .registro-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .complemento-badge {
            background: linear-gradient(135deg, var(--complement-color) 0%, var(--complement-light) 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        /* Toggle Sidebar Button */
        .toggle-sidebar {
            display: none;
            background: var(--secondary);
            border: none;
            border-radius: 10px;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            transition: all 0.3s ease;
        }
        
        .toggle-sidebar:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 992px) {
            .toggle-sidebar {
                display: flex;
            }
        }
        
        /* Badges */
        .badge-premium {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .badge-primary-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        /* Loading Spinner */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(44, 90, 160, 0.1);
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        /* Cancelaciones Cards */
        .cancelacion-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-light);
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .cancelacion-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .cancelacion-card.asignada {
            border-left-color: var(--success);
        }
        
        /* Area Badge */
        .area-badge {
            background: rgba(44, 90, 160, 0.1);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: rgba(44, 90, 160, 0.2);
            margin-bottom: 20px;
        }
        
        /* Info Row */
        .info-row {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        /* Complemento Icon */
        .complemento-icon {
            color: var(--complement-color);
        }
        
        /* Botones de filtro estilo AZUL para complementos */
        .btn-outline-complement {
            color: var(--complement-color);
            border-color: var(--complement-color);
        }
        
        .btn-outline-complement:hover {
            background-color: var(--complement-color);
            border-color: var(--complement-color);
            color: white;
        }
        
        .btn-outline-complement.active {
            background-color: var(--complement-color);
            border-color: var(--complement-color);
            color: white;
        }
        
        /* Botones de filtro estilo AZUL para registros */
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .btn-outline-primary.active {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .alert-complement {
            color: var(--complement-dark);
            background-color: rgba(33, 150, 243, 0.1);
            border-color: var(--complement-light);
        }
        
        /* Formulario Registro */
        .form-registro {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow-light);
            margin-bottom: 25px;
        }
        
        /* Date and Time Inputs */
        .datetime-input {
            position: relative;
        }
        
        .datetime-input input {
            padding-right: 40px;
        }
        
        .datetime-input i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            pointer-events: none;
        }
        
        /* Flatpickr Customization */
        .flatpickr-calendar {
            border-radius: 15px;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--glass-border);
        }
        
        .flatpickr-day.selected {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .flatpickr-day.today {
            border-color: var(--secondary);
        }
        
        .flatpickr-day.today:hover {
            background: var(--secondary-light);
            border-color: var(--secondary);
        }
        
        /* Time picker styling */
        input[type="time"] {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php if (!isset($_SESSION['usuario']) || !$_SESSION['usuario']['logueado']): ?>
        <!-- Login Page -->
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h2 class="fw-bold mb-2" style="color: var(--primary-dark);">Dashboard Chef</h2>
                    <p class="text-muted">Sistema Premium de Gestión de Comidas</p>
                </div>
                
                <?php if (isset($error_login)): ?>
                    <div class="alert alert-danger fade-in">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_login; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-user text-primary"></i>
                            </span>
                            <input type="text" name="usuario" class="form-control border-start-0" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-lock text-primary"></i>
                            </span>
                            <input type="password" name="contrasena" class="form-control border-start-0" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn-premium w-100 py-3 fw-bold">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Dashboard -->
        <!-- Navbar -->
        <nav class="navbar navbar-premium">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="toggle-sidebar me-3" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand-premium" href="?page=comidas">
                        <i class="fas fa-crown"></i>Dashboard Chef
                    </a>
                </div>
                
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="me-2">
                                <i class="fas fa-user-circle fa-2x"></i>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo $_SESSION['usuario']['nombre']; ?></div>
                                <div class="small opacity-75">Chef Principal</div>
                            </div>
                        </a>
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
                <div class="user-info text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                    </div>
                    <h5><?php echo $_SESSION['usuario']['nombre']; ?></h5>
                    <p><span class="badge bg-warning text-dark">Chef Principal</span></p>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <a href="?page=comidas" class="menu-item <?php echo $pagina_actual == 'comidas' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i>
                    <span>Comidas a Servir</span>
                </a>
                
                <a href="?page=complementos" class="menu-item <?php echo $pagina_actual == 'complementos' ? 'active' : ''; ?>">
                    <i class="fas fa-mug-hot"></i>
                    <span>Complementos</span>
                </a>
                
                <a href="?page=registros" class="menu-item <?php echo $pagina_actual == 'registros' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Registros de Comida</span>
                </a>
                
                <a href="?page=asignar" class="menu-item <?php echo $pagina_actual == 'asignar' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Asignar Comidas</span>
                </a>
                
                <a href="?page=configuracion" class="menu-item <?php echo $pagina_actual == 'configuracion' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </a>
                
                <div class="mt-4 pt-3 border-top">
                    <a href="?logout=1" class="menu-item text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <?php if ($pagina_actual == 'comidas'): ?>
                <!-- Módulo: Comidas a Servir -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium d-flex justify-content-between align-items-center">
                        <h3 class="card-title-premium">
                            <i class="fas fa-utensils"></i>Comidas a Servir - HOY
                        </h3>
                        <div>
                            <span class="badge badge-primary-premium me-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-premium btn-sm" onclick="cargarDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card fade-in">
                                <div class="stat-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h3 id="total-comidas" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Total Comidas Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card success fade-in">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="comidas-atendidas" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Atendidas Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card warning fade-in">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 id="comidas-pendientes" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Pendientes Ahora</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold d-flex align-items-center" style="color: var(--primary-dark);">
                                    <i class="fas fa-list me-3"></i>Lista de Comidas
                                    <span class="badge bg-warning ms-3" id="notification-count" style="display: none;">0</span>
                                </h4>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="filtrarComidas('todas')">
                                        <i class="fas fa-list me-1"></i> Todas
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="filtrarComidas('pendientes')">
                                        <i class="fas fa-clock me-1"></i> Pendientes
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="filtrarComidas('atendidas')">
                                        <i class="fas fa-check-circle me-1"></i> Atendidas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Comidas -->
                    <div id="vista-comidas" class="row">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando comidas...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de Confirmación -->
                <div class="modal fade" id="confirmModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar Atención
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <i class="fas fa-utensils fa-4x text-success mb-3"></i>
                                <h5 id="modal-empleado" class="fw-bold mb-3"></h5>
                                <p>¿Marcar esta comida como atendida?</p>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        La comida cambiará de "Pendiente" a "Atendida"
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </button>
                                <button type="button" class="btn btn-success" id="confirmAtender">
                                    <i class="fas fa-check me-2"></i>Sí, Atender
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'complementos'): ?>
                <!-- Módulo: Complementos -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium d-flex justify-content-between align-items-center">
                        <h3 class="card-title-premium">
                            <i class="fas fa-mug-hot complemento-icon"></i>Complementos - HOY
                        </h3>
                        <div>
                            <span class="badge badge-primary-premium me-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-complement btn-sm" onclick="cargarComplementosDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card complement fade-in">
                                <div class="stat-icon complement">
                                    <i class="fas fa-mug-hot"></i>
                                </div>
                                <h3 id="total-complementos" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Total Complementos</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card success fade-in">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="complementos-atendidos" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Atendidos Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card warning fade-in">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 id="complementos-pendientes" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Pendientes Ahora</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold d-flex align-items-center" style="color: var(--complement-dark);">
                                    <i class="fas fa-list me-3 complemento-icon"></i>Lista de Complementos
                                    <span class="badge bg-warning ms-3" id="notification-count-complementos" style="display: none;">0</span>
                                </h4>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-complement active" onclick="filtrarComplementos('todas')">
                                        <i class="fas fa-list me-1"></i> Todas
                                    </button>
                                    <button type="button" class="btn btn-outline-complement" onclick="filtrarComplementos('pendientes')">
                                        <i class="fas fa-clock me-1"></i> Pendientes
                                    </button>
                                    <button type="button" class="btn btn-outline-complement" onclick="filtrarComplementos('atendidas')">
                                        <i class="fas fa-check-circle me-1"></i> Atendidas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Complementos -->
                    <div id="vista-complementos" class="row">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando complementos...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de Confirmación Complementos -->
                <div class="modal fade" id="confirmModalComplemento" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-complement text-white" style="background: var(--complement-color);">
                                <h5 class="modal-title fw-bold">
                                    <i class="fas fa-check-circle me-2"></i>Confirmar Atención
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <i class="fas fa-mug-hot fa-4x complemento-icon mb-3"></i>
                                <h5 id="modal-empleado-complemento" class="fw-bold mb-3"></h5>
                                <div class="alert alert-complement mb-3" style="background: rgba(33, 150, 243, 0.1); border-color: var(--complement-color);">
                                    <i class="fas fa-mug-hot me-2 complemento-icon"></i>
                                    <span id="modal-complemento" class="fw-bold"></span>
                                </div>
                                <p>¿Marcar este complemento como atendido?</p>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        El complemento cambiará de "Pendiente" a "Atendido"
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </button>
                                <button type="button" class="btn btn-success" id="confirmAtenderComplemento">
                                    <i class="fas fa-check me-2"></i>Sí, Atender
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'registros'): ?>
                <!-- Módulo: Registros de Comida -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium d-flex justify-content-between align-items-center">
                        <h3 class="card-title-premium">
                            <i class="fas fa-clipboard-list"></i>Registros de Comida - HOY
                        </h3>
                        <div>
                            <span class="badge badge-primary-premium me-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-premium btn-sm" onclick="cargarRegistrosDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card fade-in">
                                <div class="stat-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h3 id="total-registros" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Total Registros Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card success fade-in">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="registros-atendidos" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Atendidos Hoy</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="stat-card warning fade-in">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 id="registros-pendientes" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Pendientes Ahora</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulario para crear registro -->
                    <div class="form-registro fade-in">
                        <h4 class="fw-bold mb-3">
                            <i class="fas fa-plus-circle me-2"></i>Nuevo Registro
                        </h4>
                        <form id="formCrearRegistro">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Nombre</label>
                                    <select name="nombre_corto" class="form-select" required>
                                        <option value="">Seleccionar empleado...</option>
                                        <?php foreach ($nombresCompletos as $nombre => $completo): ?>
                                            <option value="<?php echo htmlspecialchars($nombre); ?>"><?php echo htmlspecialchars($nombre); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3 datetime-input">
                                    <label class="form-label fw-bold">Fecha (dd-mm-yyyy)</label>
                                    <input type="text" name="fecha_real" class="form-control flatpickr-date" 
                                           id="fechaPicker" required 
                                           placeholder="Seleccionar fecha">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                
                                <div class="col-md-3 mb-3 datetime-input">
                                    <label class="form-label fw-bold">Hora (HH:MM:SS)</label>
                                    <input type="text" name="hora_real" class="form-control flatpickr-time" 
                                           id="horaPicker" required 
                                           placeholder="Seleccionar hora">
                                    <i class="fas fa-clock"></i>
                                </div>
                                
                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn-premium w-100">
                                        <i class="fas fa-save me-2"></i>Guardar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="fw-bold d-flex align-items-center" style="color: var(--primary-dark);">
                                    <i class="fas fa-list me-3"></i>Registros de Hoy
                                    <span class="badge bg-warning ms-3" id="notification-count-registros" style="display: none;">0</span>
                                </h4>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="filtrarRegistros('todas')">
                                        <i class="fas fa-list me-1"></i> Todas
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="filtrarRegistros('pendientes')">
                                        <i class="fas fa-clock me-1"></i> Pendientes
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="filtrarRegistros('atendidas')">
                                        <i class="fas fa-check-circle me-1"></i> Atendidas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Registros -->
                    <div id="vista-registros" class="row">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando registros...</p>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($pagina_actual == 'asignar'): ?>
                <!-- Módulo: Asignar Comidas -->
                <?php $empleados = getEmpleados(); ?>
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium d-flex justify-content-between align-items-center">
                        <h3 class="card-title-premium">
                            <i class="fas fa-tasks"></i>Asignar Comidas - Cancelaciones de HOY
                        </h3>
                        <div>
                            <span class="badge badge-primary-premium me-2">
                                <i class="fas fa-calendar-day me-1"></i><?php echo date('d-m-Y'); ?>
                            </span>
                            <button class="btn-premium btn-sm" onclick="cargarCancelaciones()">
                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card fade-in">
                                <div class="stat-icon">
                                    <i class="fas fa-list"></i>
                                </div>
                                <h3 id="total-cancelaciones" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Total Cancelaciones</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card success fade-in">
                                <div class="stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 id="cancelaciones-asignadas" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Asignadas</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card warning fade-in">
                                <div class="stat-icon warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3 id="cancelaciones-disponibles" class="fw-bold mb-2">0</h3>
                                <p class="text-muted mb-0">Disponibles</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card fade-in">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 id="total-empleados" class="fw-bold mb-2"><?php echo count($empleados); ?></h3>
                                <p class="text-muted mb-0">Empleados</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vista Tarjetas -->
                    <div id="vista-cancelaciones" class="row">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner mx-auto mb-3"></div>
                            <p class="text-muted">Cargando cancelaciones...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal para Asignar -->
                <div class="modal fade" id="asignarModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
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
                                <div id="asignar-info" class="alert alert-info">
                                    <!-- Información de la cancelación -->
                                </div>
                            </div>
                            <div class="modal-footer">
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
                <!-- Módulo: Configuración -->
                <div class="dashboard-card fade-in">
                    <div class="card-header-premium">
                        <h3 class="card-title-premium">
                            <i class="fas fa-cog"></i>Configuración del Sistema
                        </h3>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <i class="fas fa-user me-2"></i>Información del Usuario
                                    </h5>
                                    <div class="mt-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Nombre</label>
                                            <input type="text" class="form-control" value="<?php echo $_SESSION['usuario']['nombre']; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Rol</label>
                                            <input type="text" class="form-control" value="Chef Principal" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">
                                        <i class="fas fa-database me-2"></i>Conexión a Base de Datos
                                    </h5>
                                    <div class="mt-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Servidor</label>
                                            <input type="text" class="form-control" value="DESAROLLO-BACRO\SQLEXPRESS" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Base de Datos</label>
                                            <input type="text" class="form-control" value="Comedor" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Usuario BD</label>
                                            <input type="text" class="form-control" value="Larome03" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Información del Sistema:</strong> Dashboard Chef Premium v1.4 - Sistema Completo - Todos los derechos reservados
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botón flotante para actualizar -->
        <div class="position-fixed bottom-3 end-3">
            <button class="btn-premium rounded-circle p-3" onclick="cargarPaginaActual()" style="width: 60px; height: 60px;">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        <?php if (isset($_SESSION['usuario']) && $_SESSION['usuario']['logueado']): ?>
        
        // Variables globales
        let comidasData = [];
        let complementosData = [];
        let cancelacionesData = [];
        let registrosData = [];
        let empleadosData = <?php echo json_encode(getEmpleados()); ?>;
        let filtroActual = 'todas';
        let filtroComplementosActual = 'todas';
        let filtroRegistrosActual = 'todas';
        let currentComida = null;
        let currentComplemento = null;
        let currentCancelacion = null;
        
        // Mapeo de nombres para registros
        const nombresCompletos = <?php echo json_encode($nombresCompletos); ?>;
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar eventos
            document.getElementById('confirmAtender')?.addEventListener('click', ejecutarAtencion);
            document.getElementById('confirmAtenderComplemento')?.addEventListener('click', ejecutarAtencionComplemento);
            document.getElementById('confirmAsignar')?.addEventListener('click', ejecutarAsignacion);
            
            // Configurar formulario de registro
            document.getElementById('formCrearRegistro')?.addEventListener('submit', crearRegistroComida);
            
            // Inicializar datepicker y timepicker si existen
            if (typeof flatpickr !== 'undefined') {
                // Datepicker para fecha
                flatpickr('.flatpickr-date', {
                    dateFormat: 'd-m-Y',
                    locale: 'es',
                    defaultDate: 'today',
                    maxDate: 'today',
                    onChange: function(selectedDates, dateStr) {
                        console.log('Fecha seleccionada:', dateStr);
                    }
                });
                
                // Timepicker para hora
                flatpickr('.flatpickr-time', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i:S',
                    time_24hr: true,
                    defaultDate: '<?php echo date("H:i:s"); ?>',
                    onChange: function(selectedDates, dateStr) {
                        console.log('Hora seleccionada:', dateStr);
                    }
                });
            }
            
            // Cargar datos según la página
            if ('<?php echo $pagina_actual; ?>' === 'comidas') {
                cargarDashboard();
                setInterval(cargarDashboard, 30000);
            } else if ('<?php echo $pagina_actual; ?>' === 'complementos') {
                cargarComplementosDashboard();
                setInterval(cargarComplementosDashboard, 30000);
            } else if ('<?php echo $pagina_actual; ?>' === 'registros') {
                cargarRegistrosDashboard();
                setInterval(cargarRegistrosDashboard, 30000);
            } else if ('<?php echo $pagina_actual; ?>' === 'asignar') {
                cargarCancelaciones();
                setInterval(cargarCancelaciones, 30000);
            }
        });
        
        // Toggle sidebar en móviles
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Cargar página actual
        function cargarPaginaActual() {
            if ('<?php echo $pagina_actual; ?>' === 'comidas') {
                cargarDashboard();
            } else if ('<?php echo $pagina_actual; ?>' === 'complementos') {
                cargarComplementosDashboard();
            } else if ('<?php echo $pagina_actual; ?>' === 'registros') {
                cargarRegistrosDashboard();
            } else if ('<?php echo $pagina_actual; ?>' === 'asignar') {
                cargarCancelaciones();
            }
        }
        
        // ============ MÓDULO: COMIDAS A SERVIR ============
        
        // Cargar dashboard completo
        async function cargarDashboard() {
            try {
                await Promise.all([cargarEstadisticas(), cargarComidas()]);
                
                const boton = document.querySelector('.btn-premium i.fa-sync-alt');
                if (boton) {
                    boton.classList.add('fa-spin');
                    setTimeout(() => {
                        boton.classList.remove('fa-spin');
                    }, 1000);
                }
                
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al cargar datos');
            }
        }
        
        // Cargar estadísticas
        async function cargarEstadisticas() {
            try {
                const response = await fetch('?action=get_estadisticas');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.estadisticas;
                    document.getElementById('total-comidas').textContent = stats.total || 0;
                    document.getElementById('comidas-atendidas').textContent = stats.atendidos || 0;
                    document.getElementById('comidas-pendientes').textContent = stats.pendientes || 0;
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        }
        
        // Cargar comidas
        async function cargarComidas() {
            try {
                const response = await fetch('?action=get_comidas');
                const data = await response.json();
                
                if (data.success) {
                    comidasData = data.comidas;
                    actualizarVistaComidas();
                    
                    const pendientes = comidasData.filter(c => !c.Estatus || c.Estatus === 'PENDIENTE').length;
                    const badge = document.getElementById('notification-count');
                    if (badge) {
                        badge.textContent = pendientes;
                        badge.style.display = pendientes > 0 ? 'inline-block' : 'none';
                    }
                }
            } catch (error) {
                console.error('Error cargando comidas:', error);
                mostrarError('Error al cargar comidas');
            }
        }
        
        // Filtrar comidas
        function filtrarComidas(tipo) {
            filtroActual = tipo;
            
            // Actualizar botones activos
            document.querySelectorAll('.btn-outline-primary').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            actualizarVistaComidas();
        }
        
        // Actualizar vista de comidas
        function actualizarVistaComidas() {
            const container = document.getElementById('vista-comidas');
            if (!container) return;
            
            let comidasFiltradas = comidasData;
            
            if (filtroActual === 'pendientes') {
                comidasFiltradas = comidasData.filter(c => !c.Estatus || c.Estatus === 'PENDIENTE');
            } else if (filtroActual === 'atendidas') {
                comidasFiltradas = comidasData.filter(c => c.Estatus === 'ATENDIDO');
            }
            
            if (comidasFiltradas.length === 0) {
                let mensaje = '';
                if (filtroActual === 'todas') {
                    mensaje = 'No hay comidas registradas hoy';
                } else if (filtroActual === 'pendientes') {
                    mensaje = '¡Todo atendido! No hay comidas pendientes';
                } else {
                    mensaje = 'No hay comidas atendidas aún';
                }
                
                container.innerHTML = `
                    <div class="col-12 empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4 class="text-muted mt-3">${mensaje}</h4>
                        <button class="btn btn-primary mt-3" onclick="cargarDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = comidasFiltradas.map((comida, index) => {
                const nombre = comida.Nombre || 'Sin nombre';
                const horaCorta = comida.Hora_Corta || '--:--';
                const area = comida.Area || 'Sin área';
                const fechaHora = comida.Fecha_Hora || '--:--:--';
                const estatus = comida.Estatus_Actual;
                const esAtendida = estatus === 'ATENDIDO';
                const horaAtendido = comida.Fecha_Atendido_Formateada || '';
                
                return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="comida-card fade-in ${esAtendida ? 'atendida' : ''}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary">#${index + 1}</span>
                            </div>
                            <div>
                                <span class="badge ${esAtendida ? 'bg-success' : 'bg-warning'}">
                                    <i class="fas ${esAtendida ? 'fa-check-circle' : 'fa-clock'} me-1"></i>
                                    ${esAtendida ? 'ATENDIDA' : 'PENDIENTE'}
                                </span>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold mb-2" style="color: var(--primary-dark);">
                            <i class="fas fa-user me-2"></i>${nombre}
                        </h5>
                        
                        <div class="mb-3">
                            <span class="area-badge">
                                <i class="fas fa-building me-1"></i>${area}
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>Hora registro: ${horaCorta}
                                </small>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-utensils me-1"></i>Hora comida: ${fechaHora}
                                </small>
                            </div>
                            ${esAtendida && horaAtendido ? `
                            <div class="mt-1">
                                <small class="text-success">
                                    <i class="fas fa-user-check me-1"></i>Atendido: ${horaAtendido}
                                </small>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${!esAtendida ? `
                        <button class="btn-premium w-100" onclick="mostrarConfirmacionAtender('${comida.Id_Unico}')">
                            <i class="fas fa-check-circle me-2"></i>ATENDER COMIDA
                        </button>
                        ` : `
                        <button class="btn-premium w-100" disabled style="background: #6c757d;">
                            <i class="fas fa-check-circle me-2"></i>YA ATENDIDA
                        </button>
                        `}
                    </div>
                </div>
                `;
            }).join('');
        }
        
        // Mostrar confirmación para atender
        function mostrarConfirmacionAtender(idUnico) {
            // Buscar la comida por ID único en todos los datos
            currentComida = comidasData.find(c => c.Id_Unico === idUnico);
            
            if (!currentComida) {
                mostrarError('No se encontró la comida seleccionada');
                return;
            }
            
            document.getElementById('modal-empleado').textContent = currentComida.Nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        }
        
        // Ejecutar atención
        async function ejecutarAtencion() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            
            try {
                const formData = new FormData();
                formData.append('nombre', currentComida.Nombre);
                formData.append('hora_entrada', currentComida.Hora_Entrada_SQL);
                formData.append('fecha_hora', currentComida.Fecha_Hora);
                
                const response = await fetch('?action=marcar_atendido', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                modal.hide();
                
                if (data.success) {
                    mostrarAlerta('¡Éxito!', data.message, 'success');
                    await cargarDashboard();
                } else {
                    mostrarError('Error al atender comida: ' + data.message);
                }
            } catch (error) {
                modal.hide();
                console.error("Error:", error);
                mostrarError('Error al atender comida');
            }
        }
        
        // ============ MÓDULO: COMPLEMENTOS ============
        
        // Cargar dashboard de complementos
        async function cargarComplementosDashboard() {
            try {
                await Promise.all([cargarEstadisticasComplementos(), cargarComplementos()]);
                
                const boton = document.querySelector('.btn-complement i.fa-sync-alt');
                if (boton) {
                    boton.classList.add('fa-spin');
                    setTimeout(() => {
                        boton.classList.remove('fa-spin');
                    }, 1000);
                }
                
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al cargar datos de complementos');
            }
        }
        
        // Cargar estadísticas de complementos
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
                console.error('Error cargando estadísticas de complementos:', error);
            }
        }
        
        // Cargar complementos
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
                }
            } catch (error) {
                console.error('Error cargando complementos:', error);
                mostrarError('Error al cargar complementos');
            }
        }
        
        // Filtrar complementos
        function filtrarComplementos(tipo) {
            filtroComplementosActual = tipo;
            
            // Actualizar botones activos
            document.querySelectorAll('.btn-outline-complement').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            actualizarVistaComplementos();
        }
        
        // Actualizar vista de complementos
        function actualizarVistaComplementos() {
            const container = document.getElementById('vista-complementos');
            if (!container) return;
            
            let complementosFiltrados = complementosData;
            
            if (filtroComplementosActual === 'pendientes') {
                complementosFiltrados = complementosData.filter(c => c.estatus === 'PENDIENTE');
            } else if (filtroComplementosActual === 'atendidas') {
                complementosFiltrados = complementosData.filter(c => c.estatus === 'ATENDIDO');
            }
            
            if (complementosFiltrados.length === 0) {
                let mensaje = '';
                if (filtroComplementosActual === 'todas') {
                    mensaje = 'No hay complementos registrados hoy';
                } else if (filtroComplementosActual === 'pendientes') {
                    mensaje = '¡Todo atendido! No hay complementos pendientes';
                } else {
                    mensaje = 'No hay complementos atendidos aún';
                }
                
                container.innerHTML = `
                    <div class="col-12 empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4 class="text-muted mt-3">${mensaje}</h4>
                        <button class="btn btn-complement mt-3" onclick="cargarComplementosDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = complementosFiltrados.map((complemento, index) => {
                const nombre = complemento.nombre || 'Sin nombre';
                const complementoTipo = complemento.complemento || 'Sin especificar';
                const fecha = complemento.fecha_formateada || '--/--/----';
                const hora = complemento.hora_corta || '--:--';
                const estatus = complemento.estatus;
                const esAtendido = estatus === 'ATENDIDO';
                const horaAtendido = complemento.fecha_atendido_formateada || '';
                
                return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="complemento-card fade-in ${esAtendido ? 'atendida' : ''}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary">#${index + 1}</span>
                            </div>
                            <div>
                                <span class="badge ${esAtendido ? 'bg-success' : 'bg-warning'}">
                                    <i class="fas ${esAtendido ? 'fa-check-circle' : 'fa-clock'} me-1"></i>
                                    ${esAtendido ? 'ATENDIDO' : 'PENDIENTE'}
                                </span>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold mb-2" style="color: var(--primary-dark);">
                            <i class="fas fa-user me-2"></i>${nombre}
                        </h5>
                        
                        <div class="mb-3">
                            <span class="complemento-badge">
                                <i class="fas fa-mug-hot me-1"></i>${complementoTipo}
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>Fecha: ${fecha}
                                </small>
                            </div>
                            <div class="mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>Hora: ${hora}
                                </small>
                            </div>
                            ${esAtendido && horaAtendido ? `
                            <div class="mt-1">
                                <small class="text-success">
                                    <i class="fas fa-user-check me-1"></i>Atendido: ${horaAtendido}
                                </small>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${!esAtendido ? `
                        <button class="btn-complement w-100" onclick="mostrarConfirmacionAtenderComplemento('${complemento.Id_Unico}')">
                            <i class="fas fa-check-circle me-2"></i>ATENDER COMPLEMENTO
                        </button>
                        ` : `
                        <button class="btn-complement w-100" disabled style="background: #6c757d;">
                            <i class="fas fa-check-circle me-2"></i>YA ATENDIDO
                        </button>
                        `}
                    </div>
                </div>
                `;
            }).join('');
        }
        
        // Mostrar confirmación para atender complemento
        function mostrarConfirmacionAtenderComplemento(idUnico) {
            // Buscar el complemento por ID único en todos los datos
            currentComplemento = complementosData.find(c => c.Id_Unico === idUnico);
            
            if (!currentComplemento) {
                mostrarError('No se encontró el complemento seleccionado');
                return;
            }
            
            document.getElementById('modal-empleado-complemento').textContent = currentComplemento.nombre;
            document.getElementById('modal-complemento').textContent = currentComplemento.complemento;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmModalComplemento'));
            modal.show();
        }
        
        // Ejecutar atención de complemento
        async function ejecutarAtencionComplemento() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModalComplemento'));
            
            try {
                const formData = new FormData();
                formData.append('nombre', currentComplemento.nombre);
                formData.append('complemento', currentComplemento.complemento);
                formData.append('fecha', currentComplemento.fecha_formateada);
                formData.append('hora', currentComplemento.hora_completa);
                
                const response = await fetch('?action=marcar_complemento_atendido', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                modal.hide();
                
                if (data.success) {
                    mostrarAlerta('¡Éxito!', data.message, 'success');
                    await cargarComplementosDashboard();
                } else {
                    mostrarError('Error al atender complemento: ' + data.message);
                }
            } catch (error) {
                modal.hide();
                console.error("Error:", error);
                mostrarError('Error al atender complemento');
            }
        }
        
        // ============ MÓDULO: REGISTROS DE COMIDA ============
        
        // Cargar dashboard de registros
        async function cargarRegistrosDashboard() {
            try {
                await cargarRegistros();
                
                const boton = document.querySelector('.btn-premium i.fa-sync-alt');
                if (boton) {
                    boton.classList.add('fa-spin');
                    setTimeout(() => {
                        boton.classList.remove('fa-spin');
                    }, 1000);
                }
                
                // Actualizar estadísticas
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
                
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al cargar datos de registros');
            }
        }
        
        // Cargar registros
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
                mostrarError('Error al cargar registros');
            }
        }
        
        // Filtrar registros
        function filtrarRegistros(tipo) {
            filtroRegistrosActual = tipo;
            
            // Actualizar botones activos
            document.querySelectorAll('.btn-outline-primary').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            actualizarVistaRegistros();
        }
        
        // Actualizar vista de registros
        function actualizarVistaRegistros() {
            const container = document.getElementById('vista-registros');
            if (!container) return;
            
            let registrosFiltrados = registrosData;
            
            if (filtroRegistrosActual === 'pendientes') {
                registrosFiltrados = registrosData.filter(r => r.Estatus === 'PENDIENTE');
            } else if (filtroRegistrosActual === 'atendidas') {
                registrosFiltrados = registrosData.filter(r => r.Estatus === 'ATENDIDO');
            }
            
            if (registrosFiltrados.length === 0) {
                let mensaje = '';
                if (filtroRegistrosActual === 'todas') {
                    mensaje = 'No hay registros hoy';
                } else if (filtroRegistrosActual === 'pendientes') {
                    mensaje = '¡Todo atendido! No hay registros pendientes';
                } else {
                    mensaje = 'No hay registros atendidos aún';
                }
                
                container.innerHTML = `
                    <div class="col-12 empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4 class="text-muted mt-3">${mensaje}</h4>
                        <button class="btn btn-primary mt-3" onclick="cargarRegistrosDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = registrosFiltrados.map((registro, index) => {
                const nombre = registro.Nombre_Limpio || registro.Nombre || 'Sin nombre';
                const fechaRegistro = registro.Fecha_Registro || '--/--/----';
                const horaComida = registro.Hora_Comida || '--:--:--';
                const estatus = registro.Estatus || 'PENDIENTE';
                const esAtendido = estatus === 'ATENDIDO';
                const horaAtendido = registro.Fecha_Atendido_Formateada || '';
                const atendidoPor = registro.Usuario_Atiende || '';
                
                return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="registro-card fade-in ${esAtendido ? 'atendida' : ''}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary">#${index + 1}</span>
                                <span class="badge ${esAtendido ? 'bg-success' : 'bg-warning'} ms-1">
                                    ${esAtendido ? 'ATENDIDO' : 'PENDIENTE'}
                                </span>
                            </div>
                            <div>
                                <span class="registro-badge">
                                    <i class="fas fa-calendar-day me-1"></i>${fechaRegistro}
                                </span>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold mb-2" style="color: var(--primary-dark);">
                            <i class="fas fa-user me-2"></i>${nombre}
                        </h5>
                        
                        <div class="mb-3">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>Hora comida: ${horaComida}
                                </small>
                            </div>
                            ${esAtendido ? `
                            <div class="mt-1">
                                <small class="text-success">
                                    <i class="fas fa-user-check me-1"></i>Atendido: ${horaAtendido}
                                </small>
                            </div>
                            ${atendidoPor ? `
                            <div class="mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-user-tie me-1"></i>Por: ${atendidoPor}
                                </small>
                            </div>
                            ` : ''}
                            ` : ''}
                        </div>
                        
                        ${!esAtendido ? `
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Este registro se mostrará en "Comidas a Servir" para ser atendido
                        </div>
                        ` : `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Registro ya fue atendido en el módulo de comidas
                        </div>
                        `}
                    </div>
                </div>
                `;
            }).join('');
        }
        
        // Crear nuevo registro
        async function crearRegistroComida(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            
            // Validar que se haya seleccionado un nombre
            const nombre = formData.get('nombre_corto');
            if (!nombre) {
                mostrarError('Debe seleccionar un empleado');
                return;
            }
            
            // Validar formato de fecha (dd-mm-yyyy)
            const fecha = formData.get('fecha_real');
            if (!/^\d{2}-\d{2}-\d{4}$/.test(fecha)) {
                mostrarError('Formato de fecha incorrecto. Use dd-mm-yyyy');
                return;
            }
            
            // Validar formato de hora (HH:MM:SS)
            const hora = formData.get('hora_real');
            if (!/^\d{2}:\d{2}:\d{2}$/.test(hora)) {
                mostrarError('Formato de hora incorrecto. Use HH:MM:SS');
                return;
            }
            
            try {
                const response = await fetch('?action=crear_registro_comida', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Mostrar mensaje de éxito
                    mostrarAlerta('¡Éxito!', data.message, 'success');
                    
                    // Limpiar formulario
                    form.reset();
                    
                    // Restablecer valores por defecto
                    const fechaPicker = document.getElementById('fechaPicker');
                    const horaPicker = document.getElementById('horaPicker');
                    
                    if (fechaPicker && fechaPicker._flatpickr) {
                        fechaPicker._flatpickr.setDate('today');
                    }
                    
                    if (horaPicker && horaPicker._flatpickr) {
                        horaPicker._flatpickr.setDate('<?php echo date("H:i:s"); ?>');
                    }
                    
                    // Recargar registros
                    await cargarRegistrosDashboard();
                } else {
                    mostrarError('Error al crear registro: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error al crear registro');
            }
        }
        
        // ============ MÓDULO: ASIGNAR COMIDAS ============
        
        // Cargar cancelaciones (SOLO DE HOY)
        async function cargarCancelaciones() {
            try {
                const response = await fetch('?action=get_cancelaciones');
                const data = await response.json();
                
                if (data.success) {
                    cancelacionesData = data.cancelaciones;
                    actualizarVistaCancelaciones();
                    
                    // Actualizar estadísticas
                    const total = cancelacionesData.length;
                    const asignadas = cancelacionesData.filter(c => c.ESTATUS_APARTADO === 'ASIGNADO').length;
                    const disponibles = total - asignadas;
                    
                    document.getElementById('total-cancelaciones').textContent = total;
                    document.getElementById('cancelaciones-asignadas').textContent = asignadas;
                    document.getElementById('cancelaciones-disponibles').textContent = disponibles;
                    
                    const boton = document.querySelector('.btn-premium i.fa-sync-alt');
                    if (boton) {
                        boton.classList.add('fa-spin');
                        setTimeout(() => {
                            boton.classList.remove('fa-spin');
                        }, 1000);
                    }
                }
            } catch (error) {
                console.error('Error cargando cancelaciones:', error);
                mostrarError('Error al cargar cancelaciones');
            }
        }
        
        // Actualizar vista de cancelaciones
        function actualizarVistaCancelaciones() {
            const container = document.getElementById('vista-cancelaciones');
            if (!container) return;
            
            if (cancelacionesData.length === 0) {
                container.innerHTML = `
                    <div class="col-12 empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4 class="text-muted mt-3">No hay cancelaciones disponibles hoy</h4>
                        <p class="text-muted">No existen registros de cancelaciones aprobadas para el día de hoy</p>
                        <button class="btn btn-primary mt-3" onclick="cargarCancelaciones()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = cancelacionesData.map((cancelacion, index) => {
                const esAsignada = cancelacion.ESTATUS_APARTADO === 'ASIGNADO';
                const fechaMostrar = cancelacion.FECHA_FORMATEADA || 'N/A';
                
                return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="cancelacion-card fade-in ${esAsignada ? 'asignada' : ''}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary">#${index + 1}</span>
                                <span class="badge bg-info ms-1">${cancelacion.TIPO_CONSUMO || 'SIN TIPO'}</span>
                            </div>
                            <div>
                                <span class="badge ${esAsignada ? 'bg-success' : 'bg-warning'}">
                                    ${esAsignada ? 'ASIGNADA' : 'DISPONIBLE'}
                                </span>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold mb-2" style="color: var(--primary-dark);">
                            <i class="fas fa-user me-2"></i>${cancelacion.NOMBRE || 'Sin nombre'}
                        </h5>
                        
                        <div class="mb-3">
                            <div class="info-row">
                                <span class="info-label">Depto:</span> ${cancelacion.DEPARTAMENTO || 'N/A'}
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jefe:</span> ${cancelacion.JEFE || 'N/A'}
                            </div>
                            <div class="info-row">
                                <span class="info-label">Fecha:</span> ${fechaMostrar}
                            </div>
                            ${cancelacion.CAUSA ? `
                            <div class="info-row">
                                <span class="info-label">Causa:</span> ${cancelacion.CAUSA}
                            </div>
                            ` : ''}
                        </div>
                        
                        ${esAsignada ? `
                            <div class="alert alert-success py-2">
                                <i class="fas fa-user-check me-2"></i>
                                <strong>Asignado a:</strong> ${cancelacion.USUARIO_APARTA || 'N/A'}
                                ${cancelacion.FECHA_APARTADO_FORMATEADA ? `
                                <br><small class="text-muted">
                                    <i class="fas fa-calendar-check me-1"></i>${cancelacion.FECHA_APARTADO_FORMATEADA}
                                </small>
                                ` : ''}
                            </div>
                            <button class="btn btn-danger w-100" onclick="liberarCancelacion(${index})">
                                <i class="fas fa-unlock me-2"></i>Liberar Cancelación
                            </button>
                        ` : `
                            <button class="btn-premium w-100" onclick="mostrarModalAsignar(${index})">
                                <i class="fas fa-user-plus me-2"></i>Asignar Cancelación
                            </button>
                        `}
                    </div>
                </div>
                `;
            }).join('');
        }
        
        // Mostrar modal para asignar
        function mostrarModalAsignar(index) {
            currentCancelacion = cancelacionesData[index];
            
            // Limpiar select
            document.getElementById('select-empleado').value = '';
            
            // Mostrar información de la cancelación
            document.getElementById('asignar-info').innerHTML = `
                <strong>Cancelación:</strong> ${currentCancelacion.NOMBRE}<br>
                <strong>Tipo:</strong> ${currentCancelacion.TIPO_CONSUMO}<br>
                <strong>Fecha:</strong> ${currentCancelacion.FECHA_FORMATEADA}<br>
                <strong>Departamento:</strong> ${currentCancelacion.DEPARTAMENTO || 'N/A'}
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('asignarModal'));
            modal.show();
        }
        
        // Ejecutar asignación
        async function ejecutarAsignacion() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('asignarModal'));
            const select = document.getElementById('select-empleado');
            const idPersona = select.value;
            const nombrePersona = select.options[select.selectedIndex].text;
            
            if (!idPersona) {
                mostrarError('Debes seleccionar un empleado');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('nombre_cancelacion', currentCancelacion.NOMBRE);
                formData.append('tipo_consumo', currentCancelacion.TIPO_CONSUMO);
                formData.append('fecha_cancelacion', currentCancelacion.FECHA_SQL);
                formData.append('id_persona', idPersona);
                formData.append('nombre_persona', nombrePersona);
                
                const response = await fetch('?action=asignar_cancelacion', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                modal.hide();
                
                if (data.success) {
                    mostrarAlerta('¡Éxito!', data.message, 'success');
                    await cargarCancelaciones();
                } else {
                    mostrarError('Error al asignar: ' + data.message);
                }
            } catch (error) {
                modal.hide();
                console.error("Error:", error);
                mostrarError('Error al asignar cancelación');
            }
        }
        
        // Liberar cancelación
        async function liberarCancelacion(index) {
            const cancelacion = cancelacionesData[index];
            
            const confirmacion = await Swal.fire({
                title: '¿Liberar cancelación?',
                html: `
                    <div class="text-start">
                        <p><strong>Cancelación:</strong> ${cancelacion.NOMBRE}</p>
                        <p><strong>Tipo:</strong> ${cancelacion.TIPO_CONSUMO}</p>
                        <p><strong>Fecha:</strong> ${cancelacion.FECHA_FORMATEADA}</p>
                        <p><strong>Asignado a:</strong> ${cancelacion.USUARIO_APARTA || 'N/A'}</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, liberar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });
            
            if (!confirmacion.isConfirmed) return;
            
            try {
                const formData = new FormData();
                formData.append('nombre_cancelacion', cancelacion.NOMBRE);
                formData.append('tipo_consumo', cancelacion.TIPO_CONSUMO);
                formData.append('fecha_cancelacion', cancelacion.FECHA_SQL);
                
                const response = await fetch('?action=liberar_cancelacion', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarAlerta('¡Éxito!', data.message, 'success');
                    await cargarCancelaciones();
                } else {
                    mostrarError('Error al liberar: ' + data.message);
                }
            } catch (error) {
                console.error("Error:", error);
                mostrarError('Error al liberar cancelación');
            }
        }
        
        // ============ FUNCIONES GENERALES ============
        
        // Mostrar alerta
        function mostrarAlerta(titulo, mensaje, tipo = 'info') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: tipo === 'success' ? '#28a745' : 
                           tipo === 'error' ? '#dc3545' : 
                           tipo === 'warning' ? '#ffc107' : '#17a2b8',
                color: 'white'
            });
            
            Toast.fire({
                icon: tipo,
                title: mensaje
            });
        }
        
        // Mostrar error
        function mostrarError(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#dc3545'
            });
        }
        
        <?php endif; ?>
    </script>
</body>
</html>