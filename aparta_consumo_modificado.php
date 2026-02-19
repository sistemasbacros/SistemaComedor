<?php
/**
 * @file aparta_consumo_modificado.php
 * @brief Módulo para consultar y apartar consumos de comidas canceladas disponibles del día.
 *
 * @description
 * Permite a empleados autenticados (vía sesión del menú principal) reclamar/apartar
 * una comida de las cancelaciones aprobadas que no han sido asignadas en el día actual.
 * El módulo opera en dos modos:
 *
 *   Modo integrado (GET ?integrated=true):
 *     Verifica que el usuario tenga una sesión válida iniciada en el sistema principal
 *     ($_SESSION['authenticated_from_login']) y construye el contexto de sesión local
 *     ($_SESSION['usuario_apartar']) para el módulo.
 *
 *   Modo AJAX (GET ?action=...):
 *     - get_cancelaciones_disponibles: Devuelve JSON con las comidas disponibles del día
 *       o el apartado actual del usuario si ya reclamó uno.
 *     - apartar_comida: Ejecuta la reserva de un consumo cancelado para el usuario activo
 *       (UPDATE en tabla Cancelaciones), con validaciones de unicidad por usuario y día.
 *
 *   Modo HTML:
 *     Renderiza la interfaz de tarjetas de consumos disponibles con Bootstrap 5 y SweetAlert2.
 *     Las tarjetas muestran estado (DISPONIBLE / APARTADA / TU APARTADO) y permiten
 *     confirmar el apartado mediante un modal de confirmación.
 *
 * ADVERTENCIA: Este archivo contiene credenciales de base de datos hardcodeadas.
 * Debe migrarse para usar getComedorConnection() de config/database.php.
 *
 * @module Cancelaciones / Apartar Consumo
 * @access Requiere sesión activa del menú principal ($_SESSION['authenticated_from_login'] = true)
 *
 * @dependencies
 * - Librerías JS: Bootstrap 5.3.0, FontAwesome 6.4.0, SweetAlert2 11
 * - Archivos PHP: Ninguno (conexión directa hardcodeada)
 *
 * @database
 * - Tablas: Cancelaciones (BD Comedor)
 * - Operaciones:
 *     SELECT — Consulta cancelaciones del día con ESTATUS='APROBADO' y ESTATUS_APARTADO disponible
 *     SELECT — Verificación de si el usuario ya apartó un consumo hoy
 *     UPDATE — Asignación del consumo (ESTATUS_APARTADO='ASIGNADO', USUARIO_APARTA, FECHA_APARTADO)
 *
 * @session
 * - $_SESSION['authenticated_from_login'] : bool   - Indica que la sesión del menú principal está activa
 * - $_SESSION['username']                 : string - Usuario autenticado
 * - $_SESSION['user_name']                : string - Nombre completo del usuario
 * - $_SESSION['user_area']                : string - Área/departamento del usuario
 * - $_SESSION['usuario_apartar']          : array  - Contexto local del módulo con nombre, usuario, area y flags
 *
 * @inputs
 * - $_GET['integrated']        : string - 'true' para activar modo integrado con sesión del menú
 * - $_GET['action']            : string - Acción AJAX: 'get_cancelaciones_disponibles' | 'apartar_comida'
 * - $_POST['nombre_cancelacion'] : string - Nombre del empleado dueño de la cancelación a apartar
 * - $_POST['tipo_consumo']       : string - Tipo de consumo (Desayuno / Comida)
 * - $_POST['fecha_cancelacion']  : string - Fecha de la cancelación (YYYY-MM-DD o DD/MM/YYYY)
 *
 * @outputs
 * - JSON: { success: bool, cancelaciones: array, info: { ya_aparto: bool } } para GET cancelaciones
 * - JSON: { success: bool, message: string } para POST apartar
 * - HTML renderizado con tarjetas de consumos disponibles y modal de confirmación
 *
 * @security
 * - Validación de sesión activa antes de cualquier acción AJAX o renderizado
 * - Verificación de unicidad: un solo apartado por usuario por día (SELECT COUNT antes de UPDATE)
 * - Verificación de concurrencia: comprueba ESTATUS_APARTADO antes de ejecutar UPDATE
 * - htmlspecialchars() aplicado en salida de datos de sesión al HTML
 * - Credenciales de BD hardcodeadas (pendiente de migración a variables de entorno)
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 * @updated 2026-02-18
 */

// =============================================
// APARTAR COMIDAS - VERSIÓN FINAL
// =============================================

session_start();

require_once __DIR__ . '/config/database.php';

function getConnection() {
    $conn = getComedorConnection();
    if ($conn === false) {
        die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
    }
    return $conn;
}

// =============================================
// VERIFICACIÓN DE SESIÓN
// =============================================

// Verificar si viene desde el menú (parámetro integrated)
$integrated_mode = isset($_GET['integrated']) && $_GET['integrated'] === 'true';

// Si viene del menú, verificar que esté autenticado en la sesión principal
if ($integrated_mode) {
    // Verificar que esté autenticado en la sesión principal
    if (!isset($_SESSION['authenticated_from_login']) || $_SESSION['authenticated_from_login'] !== true) {
        // Si no está autenticado, mostrar mensaje
        $no_session = true;
    } else {
        // Obtener datos del usuario desde la sesión principal
        $username = $_SESSION['username'] ?? 'usuario';
        $user_name = $_SESSION['user_name'] ?? 'Usuario';
        $user_area = $_SESSION['user_area'] ?? 'Sin área';
        
        // Configurar sesión específica para apartado
        $_SESSION['usuario_apartar'] = [
            'nombre' => $user_name,
            'usuario' => $username,
            'area' => $user_area,
            'logueado' => true,
            'integrated_mode' => true
        ];
    }
}

// =============================================
// FUNCIONES PARA APARTAR COMIDAS
// =============================================

/**
 * @brief Retorna en JSON las cancelaciones de comida disponibles para apartar en el día actual.
 *
 * Lógica de negocio:
 *   - Verifica que el usuario tenga sesión activa en $_SESSION['usuario_apartar'].
 *   - Comprueba si el usuario ya realizó un apartado hoy (ESTATUS_APARTADO = 'ASIGNADO').
 *   - Si ya apartó: devuelve solo su propio apartado del día.
 *   - Si no ha apartado: devuelve todas las cancelaciones del día con ESTATUS='APROBADO'
 *     y ESTATUS_APARTADO IS NULL o 'DISPONIBLE'.
 *   - Formatea las fechas (DateTime o string) y limpia el campo NOMBRE de prefijos textuales.
 *   - Responde con Content-Type: application/json y termina la ejecución.
 *
 * @return void Emite JSON directamente al cliente y llama a exit
 */
function getCancelacionesDisponibles() {
    // Verificar sesión
    if (!isset($_SESSION['usuario_apartar']) || !$_SESSION['usuario_apartar']['logueado']) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    // Datos del usuario
    $nombreUsuario = $_SESSION['usuario_apartar']['nombre'];
    $areaUsuario = $_SESSION['usuario_apartar']['area'] ?? '';
    $usuarioAparea = $nombreUsuario . ' - ' . $areaUsuario;
    
    $conn = getConnection();
    
    /* =========================================================
     * CONSULTA: Verificar si el usuario ya realizó un apartado hoy
     * Tablas: Cancelaciones
     * Retorna: Conteo de filas con USUARIO_APARTA = usuario actual,
     *          fecha de hoy y ESTATUS_APARTADO = 'ASIGNADO'.
     * =========================================================
     */
    $sqlVerificarUsuario = "SELECT COUNT(*) as ya_aparto
                           FROM Cancelaciones
                           WHERE USUARIO_APARTA = ?
                           AND CONVERT(DATE, FECHA) = CONVERT(DATE, GETDATE())
                           AND ESTATUS_APARTADO = 'ASIGNADO'";
    
    $stmtVerificar = sqlsrv_query($conn, $sqlVerificarUsuario, array($usuarioAparea));
    $ya_aparto = false;
    
    if ($stmtVerificar) {
        if ($row = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC)) {
            $ya_aparto = intval($row['ya_aparto']) > 0;
        }
        sqlsrv_free_stmt($stmtVerificar);
    }
    
    // Si ya apartó, mostrar solo su apartado
    if ($ya_aparto) {
        /* =========================================================
         * CONSULTA: Apartado del día del usuario actual
         * Tablas: Cancelaciones
         * Retorna: Registro(s) que el usuario ya apartó hoy (ASIGNADO).
         * =========================================================
         */
        $sql = "SELECT
                    NOMBRE,
                    DEPARTAMENTO,
                    JEFE,
                    TIPO_CONSUMO,
                    FECHA,
                    CAUSA,
                    ESTATUS,
                    ESTATUS_APARTADO,
                    USUARIO_APARTA,
                    FECHA_APARTADO,
                    DESCRIPCION
                FROM Cancelaciones
                WHERE USUARIO_APARTA = ?
                AND CONVERT(DATE, FECHA) = CONVERT(DATE, GETDATE())
                AND ESTATUS_APARTADO = 'ASIGNADO'
                ORDER BY FECHA_APARTADO DESC";
        
        $stmt = sqlsrv_query($conn, $sql, array($usuarioAparea));
    } else {
        /* =========================================================
         * CONSULTA: Cancelaciones disponibles del día para apartar
         * Tablas: Cancelaciones
         * Retorna: Todas las cancelaciones aprobadas de hoy que no han sido
         *          asignadas aún (ESTATUS_APARTADO IS NULL o 'DISPONIBLE').
         * =========================================================
         */
        $sql = "SELECT
                    NOMBRE,
                    DEPARTAMENTO,
                    JEFE,
                    TIPO_CONSUMO,
                    FECHA,
                    CAUSA,
                    ESTATUS,
                    ESTATUS_APARTADO,
                    USUARIO_APARTA,
                    DESCRIPCION
                FROM Cancelaciones
                WHERE CONVERT(DATE, FECHA) = CONVERT(DATE, GETDATE())
                AND ESTATUS = 'APROBADO'
                AND (ESTATUS_APARTADO IS NULL OR ESTATUS_APARTADO = 'DISPONIBLE')
                ORDER BY FECHA ASC, NOMBRE ASC";
        
        $stmt = sqlsrv_query($conn, $sql);
    }
    
    $cancelaciones = [];
    $info = ['ya_aparto' => $ya_aparto];
    
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Formatear fecha
            if ($row['FECHA'] instanceof DateTime) {
                $row['FECHA_FORMATEADA'] = $row['FECHA']->format('Y-m-d');
                $row['FECHA_LARGA'] = $row['FECHA']->format('d/m/Y');
                $row['FECHA_SQL'] = $row['FECHA']->format('Y-m-d');
            } else {
                $fechaStr = $row['FECHA'];
                if (is_string($fechaStr)) {
                    $row['FECHA_FORMATEADA'] = date('Y-m-d', strtotime($fechaStr));
                    $row['FECHA_LARGA'] = date('d/m/Y', strtotime($fechaStr));
                    $row['FECHA_SQL'] = date('Y-m-d', strtotime($fechaStr));
                }
            }
            
            // Formatear fecha de apartado si existe
            if (isset($row['FECHA_APARTADO']) && $row['FECHA_APARTADO']) {
                if ($row['FECHA_APARTADO'] instanceof DateTime) {
                    $row['FECHA_APARTADO_FORMATEADA'] = $row['FECHA_APARTADO']->format('d/m/Y H:i:s');
                    $row['HORA_SOLA'] = $row['FECHA_APARTADO']->format('H:i');
                } else {
                    $row['FECHA_APARTADO_FORMATEADA'] = date('d/m/Y H:i:s', strtotime($row['FECHA_APARTADO']));
                    $row['HORA_SOLA'] = date('H:i', strtotime($row['FECHA_APARTADO']));
                }
            }
            
            // Limpiar nombre
            $nombreLimpio = $row['NOMBRE'];
            if (strpos($nombreLimpio, 'NOMBRE:') !== false) {
                $nombreLimpio = preg_replace('/NOMBRE:\s*(.+?)\s+(N\.E:|DEPARTAMENTO:|NSS:|se encuentra registrado para|AREA:)/', '$1', $nombreLimpio);
                $nombreLimpio = trim($nombreLimpio);
            }
            $row['NOMBRE_LIMPIO'] = $nombreLimpio;
            $row['NOMBRE_ORIGINAL'] = $row['NOMBRE'];
            
            $cancelaciones[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    } else {
        error_log("Error en getCancelacionesDisponibles: " . print_r(sqlsrv_errors(), true));
    }
    
    sqlsrv_close($conn);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'cancelaciones' => $cancelaciones,
        'info' => $info
    ]);
    exit;
}

/**
 * @brief Procesa la solicitud de apartado de un consumo cancelado por el usuario activo.
 *
 * Recibe vía POST los datos de la cancelación a apartar. Realiza las siguientes validaciones
 * antes de ejecutar el UPDATE:
 *   1. Verifica que el usuario tenga sesión activa en $_SESSION['usuario_apartar'].
 *   2. Valida que los campos nombre_cancelacion, tipo_consumo y fecha_cancelacion no estén vacíos.
 *   3. Comprueba que el usuario no haya realizado ya un apartado hoy (unicidad diaria).
 *   4. Verifica que la cancelación específica aún tenga ESTATUS_APARTADO != 'ASIGNADO'
 *      (control de concurrencia).
 *   5. Ejecuta UPDATE para marcar la cancelación como ASIGNADO con USUARIO_APARTA y FECHA_APARTADO.
 *
 * @return void Emite JSON directamente al cliente y llama a exit
 */
function apartarComida() {
    // Verificar sesión directamente desde la sesión iniciada
    if (!isset($_SESSION['usuario_apartar']) || !$_SESSION['usuario_apartar']['logueado']) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $conn = getConnection();
    
    $nombreCancelacion = $_POST['nombre_cancelacion'] ?? '';
    $tipoConsumo = $_POST['tipo_consumo'] ?? '';
    $fechaCancelacion = $_POST['fecha_cancelacion'] ?? '';
    
    // Datos del usuario de la sesión
    $usuario = $_SESSION['usuario_apartar']['usuario'];
    $nombreUsuario = $_SESSION['usuario_apartar']['nombre'];
    $areaUsuario = $_SESSION['usuario_apartar']['area'] ?? '';
    
    if (empty($nombreCancelacion) || empty($tipoConsumo) || empty($fechaCancelacion)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    // Formatear USUARIO_APARTA como se muestra en los datos: "NOMBRE - AREA"
    $usuarioAparea = $nombreUsuario . ' - ' . $areaUsuario;
    
    // Asegurar formato de fecha correcto
    $fechaSQL = date('Y-m-d', strtotime(str_replace('/', '-', $fechaCancelacion)));
    
    /* =========================================================
     * CONSULTA: Verificar unicidad del apartado diario del usuario
     * Tablas: Cancelaciones
     * Opera en: apartarComida()
     * Retorna: Conteo; si > 0, el usuario ya apartó hoy → rechazar solicitud.
     * =========================================================
     */
    $sqlVerificarUsuario = "SELECT COUNT(*) as ya_aparto
                           FROM Cancelaciones
                           WHERE USUARIO_APARTA = ?
                           AND CONVERT(DATE, FECHA) = CONVERT(DATE, GETDATE())
                           AND ESTATUS_APARTADO = 'ASIGNADO'";
    
    $stmtVerificarUsuario = sqlsrv_query($conn, $sqlVerificarUsuario, array($usuarioAparea));
    
    if ($stmtVerificarUsuario && $row = sqlsrv_fetch_array($stmtVerificarUsuario, SQLSRV_FETCH_ASSOC)) {
        if (intval($row['ya_aparto']) > 0) {
            echo json_encode(['success' => false, 'message' => '⚠️ Ya has apartado un consumo para hoy. Solo se permite un apartado por día.']);
            sqlsrv_free_stmt($stmtVerificarUsuario);
            sqlsrv_close($conn);
            exit;
        }
    }
    sqlsrv_free_stmt($stmtVerificarUsuario);
    
    /* =========================================================
     * CONSULTA: Verificar disponibilidad de la cancelación específica (control de concurrencia)
     * Tablas: Cancelaciones
     * Retorna: ESTATUS_APARTADO y USUARIO_APARTA del registro solicitado.
     *          Si ESTATUS_APARTADO = 'ASIGNADO', la comida ya fue tomada por otro usuario.
     * =========================================================
     */
    $sqlVerificar = "SELECT ESTATUS_APARTADO, USUARIO_APARTA FROM Cancelaciones
                     WHERE NOMBRE = ?
                     AND TIPO_CONSUMO = ?
                     AND CONVERT(DATE, FECHA) = ?";
    
    $paramsVerificar = array($nombreCancelacion, $tipoConsumo, $fechaSQL);
    $stmtVerificar = sqlsrv_query($conn, $sqlVerificar, $paramsVerificar);
    
    if ($stmtVerificar && $row = sqlsrv_fetch_array($stmtVerificar, SQLSRV_FETCH_ASSOC)) {
        if ($row['ESTATUS_APARTADO'] === 'ASIGNADO') {
            $usuarioAparto = $row['USUARIO_APARTA'] ?: 'otro usuario';
            echo json_encode(['success' => false, 'message' => 'Esta comida ya fue apartada por: ' . $usuarioAparto]);
            sqlsrv_free_stmt($stmtVerificar);
            sqlsrv_close($conn);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la comida']);
        if ($stmtVerificar) sqlsrv_free_stmt($stmtVerificar);
        sqlsrv_close($conn);
        exit;
    }
    
    sqlsrv_free_stmt($stmtVerificar);
    
    /* =========================================================
     * ACTUALIZACIÓN: Asignar la cancelación al usuario que la aparta
     * Tablas: Cancelaciones
     * Operación: UPDATE — Marca ESTATUS_APARTADO = 'ASIGNADO', registra USUARIO_APARTA,
     *            FECHA_APARTADO = GETDATE() y ESTATUS_SOLICITUD = 'APROBADA'.
     * Condición: Coincidencia exacta por NOMBRE, TIPO_CONSUMO y fecha (CONVERT(DATE, FECHA)).
     * =========================================================
     */
    $sql = "UPDATE Cancelaciones
            SET ESTATUS_APARTADO = 'ASIGNADO',
                USUARIO_APARTA = ?,
                FECHA_APARTADO = GETDATE(),
                ESTATUS_SOLICITUD = 'APROBADA'
            WHERE NOMBRE = ?
            AND TIPO_CONSUMO = ?
            AND CONVERT(DATE, FECHA) = ?";
    
    $params = array($usuarioAparea, $nombreCancelacion, $tipoConsumo, $fechaSQL);
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $errorMsg = isset($errors[0]['message']) ? $errors[0]['message'] : 'Error desconocido';
        error_log("ERROR SQL al apartar: " . print_r($errors, true));
        echo json_encode(['success' => false, 'message' => 'Error al apartar: ' . $errorMsg]);
        sqlsrv_close($conn);
        exit;
    }
    
    $rowsAffected = sqlsrv_rows_affected($stmt);
    
    if ($rowsAffected > 0) {
        echo json_encode(['success' => true, 'message' => '✅ Comida apartada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el registro']);
    }
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    exit;
}

/* =========================================================
 * DISPATCHER DE ACCIONES AJAX
 * Determina si la solicitud es AJAX mediante el parámetro GET 'action'
 * y despacha a la función PHP correspondiente.
 * Acciones soportadas:
 *   - get_cancelaciones_disponibles → getCancelacionesDisponibles()
 *   - apartar_comida                → apartarComida()
 * =========================================================
 */
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_cancelaciones_disponibles':
            getCancelacionesDisponibles();
            break;
        case 'apartar_comida':
            apartarComida();
            break;
    }
}

// =============================================
// HTML
// =============================================

// Verificar si estamos en modo integrado y tenemos sesión
$is_logged_in = isset($_SESSION['usuario_apartar']) && $_SESSION['usuario_apartar']['logueado'];
$is_integrated_mode = $is_logged_in && isset($_SESSION['usuario_apartar']['integrated_mode']) && $_SESSION['usuario_apartar']['integrated_mode'];

// Si no está logueado en modo integrado, mostrar mensaje de error
if ($integrated_mode && !$is_logged_in) {
    $no_session = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumos Disponibles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #2c5aa0 0%, #1a3d7a 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .food-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #dee2e6;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .food-card.disponible {
            border-top: 4px solid #28a745;
        }
        
        .food-card.apartada {
            border-top: 4px solid #ffc107;
        }
        
        .food-card.mi-apartado {
            border-top: 4px solid #007bff;
            background: #f0f8ff;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .person-info h5 {
            margin: 0;
            color: #343a40;
            font-size: 16px;
            line-height: 1.3;
        }
        
        .badge-estado {
            padding: 5px 8px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        
        .badge-disponible {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-apartada {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-mi-apartado {
            background: #cce5ff;
            color: #004085;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 80px;
        }
        
        .info-value {
            color: #6c757d;
            flex: 1;
        }
        
        .descripcion {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 13px;
            color: #6c757d;
            border-left: 3px solid #6c757d;
        }
        
        .btn-apartar {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 600;
            width: 100%;
            margin-top: auto;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-apartar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-apartar:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-mi-apartado {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 600;
            width: 100%;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: default;
        }
        
        .btn-refresh {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error-message {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        
        .error-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .user-area {
            font-size: 12px;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 10px;
        }
        
        .info-mensaje {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
            
            .person-info h5 {
                font-size: 15px;
            }
            
            .badge-estado {
                max-width: 100px;
                font-size: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding: 10px;
            }
            
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($no_session)): ?>
        <div class="error-message">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Sesión no encontrada</h3>
            <p>Por favor, accede al sistema desde el menú principal.</p>
        </div>
    <?php elseif ($is_logged_in): ?>
        <div class="container-fluid">
            <!-- Header -->
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4><i class="fas fa-hand-holding-heart me-2"></i>Consumos Disponibles</h4>
                        <p class="mb-0">Sistema para apartar comidas canceladas</p>
                    </div>
                    <div>
                        <button class="btn-refresh" onclick="cargarComidas()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex align-items-center flex-wrap">
                        <i class="fas fa-user me-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['usuario_apartar']['nombre']); ?></span>
                        <?php if (!empty($_SESSION['usuario_apartar']['area'])): ?>
                            <span class="user-area">
                                <i class="fas fa-building me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['usuario_apartar']['area']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <small class="text-white-50"><?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
            
            <!-- Mensaje informativo -->
            <div id="mensaje-container"></div>
            
            <!-- Lista de Comidas -->
            <div class="card-grid" id="comidas-container">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Cargando comidas disponibles...</p>
                </div>
            </div>
        </div>
        
        <!-- Modal de Confirmación -->
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-hand-holding-heart me-2"></i>Confirmar Apartado
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-utensils fa-3x text-success"></i>
                        </div>
                        <h5 id="modal-empleado" class="text-center mb-3"></h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="modal-info"></span>
                        </div>
                        <p class="text-center">¿Deseas apartar esta comida?</p>
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Solo puedes apartar una comida por día.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-success" id="confirmApartar">
                            <i class="fas fa-hand-holding-heart me-2"></i>Sí, Apartar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if ($is_logged_in): ?>
        
        let comidasData = [];
        let currentComida = null;
        let yaAparto = false;
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            cargarComidas();
            
            // Configurar event listeners
            document.getElementById('confirmApartar')?.addEventListener('click', ejecutarApartado);
        });
        
        // Cargar comidas
        async function cargarComidas() {
            try {
                const response = await fetch('?action=get_cancelaciones_disponibles');
                const data = await response.json();
                
                if (data.success) {
                    comidasData = data.cancelaciones;
                    yaAparto = data.info?.ya_aparto || false;
                    actualizarVistaComidas();
                } else {
                    mostrarError(data.message || 'Error al cargar comidas');
                }
            } catch (error) {
                console.error('Error cargando comidas:', error);
                mostrarError('Error al cargar comidas disponibles para hoy');
            }
        }
        
        // Actualizar vista de comidas
        function actualizarVistaComidas() {
            const container = document.getElementById('comidas-container');
            const mensajeContainer = document.getElementById('mensaje-container');
            
            if (!container) return;
            
            // Actualizar mensaje informativo
            if (yaAparto) {
                mensajeContainer.innerHTML = `
                    <div class="info-mensaje">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        <strong>Ya has apartado un consumo para hoy.</strong> 
                        <span class="text-muted">Solo se permite un apartado por día.</span>
                    </div>
                `;
            } else {
                mensajeContainer.innerHTML = `
                    <div class="info-mensaje">
                        <i class="fas fa-utensils me-2 text-success"></i>
                        <strong>Puedes apartar un consumo disponible.</strong> 
                        <span class="text-muted">Solo se permite un apartado por día.</span>
                    </div>
                `;
            }
            
            if (comidasData.length === 0) {
                if (yaAparto) {
                    container.innerHTML = `
                        <div class="error-message" style="grid-column: 1 / -1;">
                            <div class="error-icon">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <h3>Ya tienes un consumo apartado</h3>
                            <p>Has apartado un consumo para hoy. Vuelve mañana para apartar otro.</p>
                            <button class="btn btn-primary mt-3" onclick="cargarComidas()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="error-message" style="grid-column: 1 / -1;">
                            <div class="error-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3>No hay comidas disponibles para hoy</h3>
                            <p>Todas las cancelaciones aprobadas ya han sido apartadas</p>
                            <button class="btn btn-primary mt-3" onclick="cargarComidas()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                        </div>
                    `;
                }
                return;
            }
            
            container.innerHTML = comidasData.map((comida, index) => {
                const nombre = comida.NOMBRE_LIMPIO || comida.NOMBRE || 'Sin nombre';
                const nombreOriginal = comida.NOMBRE_ORIGINAL || comida.NOMBRE || '';
                const depto = comida.DEPARTAMENTO || 'Sin departamento';
                const jefe = comida.JEFE || 'Sin jefe';
                const causa = comida.CAUSA || 'No especificada';
                const tipoConsumo = comida.TIPO_CONSUMO || 'Sin tipo';
                const fechaLarga = comida.FECHA_LARGA || 'N/A';
                const descripcion = comida.DESCRIPCION || '';
                const fechaApartado = comida.FECHA_APARTADO_FORMATEADA || '';
                const horaApartado = comida.HORA_SOLA || '';
                
                // Verificar si está apartada
                const estaApartada = comida.ESTATUS_APARTADO === 'ASIGNADO';
                const usuarioApareta = comida.USUARIO_APARTA || '';
                
                // Determinar si es mi apartado
                const esMiApartado = yaAparto;
                
                // Estilos según el estado
                let cardClase = 'disponible';
                let badgeClase = 'badge-disponible';
                let badgeTexto = 'DISPONIBLE';
                
                if (estaApartada) {
                    if (esMiApartado) {
                        cardClase = 'mi-apartado';
                        badgeClase = 'badge-mi-apartado';
                        badgeTexto = 'TU APARTADO';
                    } else {
                        cardClase = 'apartada';
                        badgeClase = 'badge-apartada';
                        badgeTexto = 'APARTADA';
                    }
                }
                
                return `
                <div class="food-card ${cardClase}">
                    <div class="card-header">
                        <div class="person-info">
                            <h5>${nombre}</h5>
                            <small class="text-muted">${depto}</small>
                        </div>
                        <div>
                            <span class="badge-estado ${badgeClase}" title="${estaApartada ? 'Apartada por: ' + usuarioApareta : 'Disponible'}">
                                ${badgeTexto}
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Jefe:</span>
                        <span class="info-value">${jefe}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Causa:</span>
                        <span class="info-value">${causa}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tipo:</span>
                        <span class="info-value">${tipoConsumo}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha:</span>
                        <span class="info-value">${fechaLarga}</span>
                    </div>
                    
                    ${esMiApartado && fechaApartado ? `
                    <div class="info-row">
                        <span class="info-label">Apartado:</span>
                        <span class="info-value">${horaApartado}</span>
                    </div>
                    ` : ''}
                    
                    ${descripcion ? `
                    <div class="descripcion">
                        <strong>Descripción:</strong><br>
                        ${descripcion}
                    </div>
                    ` : ''}
                    
                    ${!estaApartada && !yaAparto ? `
                    <button class="btn-apartar" onclick="mostrarConfirmacionApartar(${index})">
                        <i class="fas fa-hand-holding-heart me-2"></i>APARTAR ESTA COMIDA
                    </button>
                    ` : esMiApartado ? `
                    <button class="btn-mi-apartado">
                        <i class="fas fa-check-circle me-2"></i>MI APARTADO
                    </button>
                    ` : `
                    <button class="btn-apartar" disabled>
                        <i class="fas fa-lock me-2"></i>YA APARTADA
                    </button>
                    `}
                </div>
                `;
            }).join('');
        }
        
        // Mostrar confirmación para apartar
        function mostrarConfirmacionApartar(index) {
            // Verificar que no haya ya apartado
            if (yaAparto) {
                mostrarError('Ya has apartado un consumo para hoy. Solo se permite un apartado por día.');
                return;
            }
            
            currentComida = comidasData[index];
            
            const nombreCompleto = currentComida.NOMBRE_LIMPIO || currentComida.NOMBRE;
            const nombreCorto = nombreCompleto.length > 30 ? nombreCompleto.substring(0, 30) + '...' : nombreCompleto;
            
            document.getElementById('modal-empleado').textContent = nombreCorto;
            document.getElementById('modal-info').innerHTML = `
                <strong>Depto:</strong> ${currentComida.DEPARTAMENTO || 'N/A'}<br>
                <strong>Causa:</strong> ${currentComida.CAUSA || 'No especificada'}<br>
                <strong>Tipo:</strong> ${currentComida.TIPO_CONSUMO || 'N/A'}
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        }
        
        // Ejecutar apartado
        async function ejecutarApartado() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            
            if (!currentComida) {
                mostrarError('No hay comida seleccionada');
                modal.hide();
                return;
            }
            
            // Verificar nuevamente que no haya ya apartado
            if (yaAparto) {
                modal.hide();
                mostrarError('Ya has apartado un consumo para hoy. Solo se permite un apartado por día.');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('nombre_cancelacion', currentComida.NOMBRE_ORIGINAL || currentComida.NOMBRE);
                formData.append('tipo_consumo', currentComida.TIPO_CONSUMO);
                formData.append('fecha_cancelacion', currentComida.FECHA_SQL || currentComida.FECHA_FORMATEADA);
                
                const response = await fetch('?action=apartar_comida', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                modal.hide();
                
                if (data.success) {
                    mostrarAlerta('¡Éxito!', data.message, 'success');
                    await cargarComidas();
                } else {
                    mostrarError('Error al apartar: ' + data.message);
                }
            } catch (error) {
                modal.hide();
                console.error("Error:", error);
                mostrarError('Error al apartar comida');
            }
        }
        
        // Mostrar error
        function mostrarError(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#dc3545',
                background: 'white',
                color: '#212529'
            });
        }
        
        // Mostrar alerta
        function mostrarAlerta(titulo, mensaje, tipo = 'info') {
            Swal.fire({
                icon: tipo,
                title: titulo,
                text: mensaje,
                confirmButtonColor: tipo === 'success' ? '#28a745' : 
                                   tipo === 'error' ? '#dc3545' : 
                                   '#17a2b8',
                timer: 3000
            });
        }
        
        <?php endif; ?>
    </script>
</body>
</html>