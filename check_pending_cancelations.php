<?php
/**
 * @file check_pending_cancelations.php
 * @brief Endpoint AJAX para consultar cancelaciones pendientes del día actual.
 *
 * @description
 * Endpoint de solo lectura que retorna en formato JSON el número y detalle
 * de las cancelaciones de comedor que aún no han sido aprobadas en la fecha actual.
 * Es consumido por el frontend (fetch/$.ajax) para mostrar badges de notificación
 * en el menú principal del sistema, alertando a DIRECCIÓN sobre solicitudes pendientes.
 *
 * Flujo de ejecución:
 * 1. Verifica autenticación via $_SESSION['authenticated_from_login'].
 * 2. Conecta a SQL Server (BD Comedor).
 * 3. Consulta tabla Cancelaciones con filtro de fecha actual y ESTATUS != 'APROBADO'.
 * 4. Construye array de respuesta con conteo y detalle.
 * 5. Retorna JSON codificado.
 *
 * @module Módulo de Cancelaciones
 * @access Usuarios autenticados con $_SESSION['authenticated_from_login'] = true
 *
 * @api {POST} /check_pending_cancelations.php Consultar cancelaciones pendientes
 * @apiName CheckPendingCancelations
 * @apiGroup Cancelaciones
 *
 * @apiHeader {String} Cookie Sesión PHP con authenticated_from_login=true
 *
 * @apiSuccess {Boolean} success              true si la consulta fue exitosa
 * @apiSuccess {Number}  pending_count        Número de cancelaciones pendientes hoy
 * @apiSuccess {Array}   details              Lista de cancelaciones pendientes
 * @apiSuccess {String}  details.usuario      Nombre del solicitante
 * @apiSuccess {String}  details.tipo_consumo Desayuno | Comida
 * @apiSuccess {String}  details.estatus      Estado actual (PENDIENTE | REVISION | etc.)
 * @apiSuccess {String}  details.fecha        Fecha en formato DD/MM/YYYY
 * @apiSuccess {String}  details.hora         Hora en formato HH:MM:SS
 *
 * @apiError {Boolean} success false
 * @apiError {String}  error   Mensaje descriptivo del error
 *
 * @dependencies
 * - PHP: session_start(), sqlsrv_connect(), sqlsrv_query(), json_encode()
 *
 * @database
 * - Servidor: DESAROLLO-BACRO\\SQLEXPRESS (hardcoded — pendiente migración a .env)
 * - Base de datos: Comedor
 * - Tabla: cancelaciones
 * - Operación: SELECT con filtro de fecha actual y ESTATUS != 'APROBADO'
 * - Ordenado: por HORA DESC (más recientes primero)
 *
 * @session
 * - $_SESSION['authenticated_from_login'] : bool - debe ser true (requerido)
 *
 * @inputs
 * - Ninguno (no requiere parámetros de formulario, solo sesión activa)
 *
 * @outputs
 * - JSON: { success: bool, pending_count: int, details: array }
 * - JSON de error: { success: false, error: string }
 *
 * @security
 * - Verifica sesión antes de cualquier operación de BD
 * - Responde con JSON de error si no hay autenticación (no redirige)
 * - Consulta parametrizada con prepared statement para la fecha
 *
 * @uses getComedorConnection() Conexión centralizada desde config/database.php.
 *
 * @author Equipo Tecnología BacroCorp
 * @version 1.0
 * @since 2024
 * @updated 2026-02-18
 */
// check_pending_cancelations.php

session_start();

// ---------------------------------------------------------------------------
// SECCIÓN: Verificación de autenticación
// Solo usuarios con sesión válida (authenticated_from_login === true) pueden
// ejecutar este endpoint. Se retorna JSON de error y se detiene la ejecución.
// ---------------------------------------------------------------------------
// Verificar autenticación
if (!isset($_SESSION['authenticated_from_login']) || $_SESSION['authenticated_from_login'] !== true) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/config/database.php';

// ---------------------------------------------------------------------------
// SECCIÓN: Estructura de respuesta JSON por defecto
// Inicializada con valores de fallo para garantizar una respuesta válida
// incluso si la conexión o la consulta fallan antes de asignar datos reales.
// ---------------------------------------------------------------------------
$response = ['success' => false, 'pending_count' => 0, 'details' => []];

try {
    // -----------------------------------------------------------------------
    // SECCIÓN: Conexión al servidor SQL
    // -----------------------------------------------------------------------
    $conn = getComedorConnection();

    if ($conn !== false) {
        // -------------------------------------------------------------------
        // SECCIÓN: Preparación del parámetro de fecha para la consulta
        // Se usa la fecha del servidor (zona horaria America/Mexico_City
        // configurada en php/custom.ini) para filtrar registros del día actual.
        // -------------------------------------------------------------------
        $fecha_actual = date('Y-m-d');

        /* =========================================================
         * CONSULTA: Cancelaciones pendientes del día actual
         * =========================================================
         * Tabla: cancelaciones
         * Filtros:
         *   - Fecha = Fecha actual del servidor (date('Y-m-d'))
         *   - ESTATUS != 'APROBADO' (incluye: PENDIENTE, EN REVISIÓN, etc.)
         * Orden: HORA DESC (cancelaciones más recientes primero)
         * Formato de fecha: CONVERT(varchar, FECHA, 103) → DD/MM/YYYY
         *                   CONVERT(VARCHAR, HORA, 108)  → HH:MM:SS
         */
        // Consulta para obtener cancelaciones pendientes del día actual
        $sql = "SELECT
                    nombre as usuario,
                    tipo_consumo,
                    ESTATUS as estatus,
                    convert(varchar, FECHA, 103) as fecha,
                    CONVERT(VARCHAR, HORA, 108) as hora
                FROM cancelaciones
                WHERE convert(date, FECHA, 102) = ?
                AND ESTATUS != 'APROBADO'
                ORDER BY HORA DESC";

        $params = array($fecha_actual);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt !== false) {
            // ---------------------------------------------------------------
            // SECCIÓN: Iteración de resultados y construcción del payload
            // Cada fila se normaliza con valores por defecto ('N/A' / 'PENDIENTE')
            // para campos NULL, garantizando un JSON consistente en el cliente.
            // ---------------------------------------------------------------
            $pending_count = 0;
            $details = [];

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $pending_count++;
                $details[] = [
                    'usuario' => $row['usuario'] ?? 'N/A',
                    'tipo_consumo' => $row['tipo_consumo'] ?? 'N/A',
                    'estatus' => $row['estatus'] ?? 'PENDIENTE',
                    'fecha' => $row['fecha'] ?? date('d/m/Y'),
                    'hora' => $row['hora'] ?? date('H:i:s')
                ];
            }

            // ---------------------------------------------------------------
            // SECCIÓN: Construcción de respuesta exitosa
            // ---------------------------------------------------------------
            $response['success'] = true;
            $response['pending_count'] = $pending_count;
            $response['details'] = $details;

        } else {
            $response['error'] = 'Error en la consulta';
        }

        // -------------------------------------------------------------------
        // SECCIÓN: Cierre de conexión SQL
        // -------------------------------------------------------------------
        sqlsrv_close($conn);

    } else {
        $response['error'] = 'Error de conexión';
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// ---------------------------------------------------------------------------
// SECCIÓN: Emisión de respuesta JSON
// Se establece el Content-Type correcto antes de serializar la respuesta.
// ---------------------------------------------------------------------------
// Devolver respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
?>