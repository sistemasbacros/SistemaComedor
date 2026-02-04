<?php
/**
 * MAPEO DE ENDPOINTS FALTANTES
 * ============================
 * 
 * Este archivo mapea endpoints que el código PHP espera pero que no existen
 * en el backend, redirigiendo a endpoints equivalentes que sí funcionan.
 */

// Asegurar que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config_api.php';
require_once __DIR__ . '/api_client.php';

/**
 * Obtener consumos del usuario (mapeo de endpoint faltante)
 * Este es un wrapper que adapta /api/empleados/perfil para simular /api/consumos/mis-consumos
 */
function obtenerMisConsumos($fecha = null) {
    $api = getAPIClient();
    
    // El backend no tiene endpoint de consumos específico, 
    // pero podemos simular la respuesta usando el perfil del empleado
    $response = $api->get('empleados/perfil');
    
    if (!$response['success']) {
        return $response;
    }
    
    $empleado = $response['data'];
    
    // Simular estructura de respuesta para consumos
    $consumosData = [
        'empleado' => [
            'id_empleado' => $empleado['id_empleado'],
            'nombre' => $empleado['nombre'],
            'area' => $empleado['area'],
            'usuario' => $empleado['usuario']
        ],
        'fecha_consulta' => $fecha ?? date('Y-m-d'),
        'desglose' => [
            // Por ahora, datos simulados hasta que el backend tenga el endpoint real
            ['dia' => 'Lunes', 'tipo' => 'Desayuno', 'fecha' => $fecha ?? date('Y-m-d')],
            ['dia' => 'Lunes', 'tipo' => 'Comida', 'fecha' => $fecha ?? date('Y-m-d')]
        ],
        'totales' => [
            'desayunos' => 1,
            'comidas' => 1,
            'total' => 2
        ]
    ];
    
    return [
        'success' => true,
        'data' => $consumosData
    ];
}

/**
 * Obtener mis pedidos (endpoint que sí existe)
 */
function obtenerMisPedidos() {
    $api = getAPIClient();
    return $api->get('pedidos/mis-pedidos');
}

/**
 * Verificar límite de pedidos (endpoint que sí existe)
 */
function verificarLimitePedidos($fecha) {
    $api = getAPIClient();
    return $api->get('pedidos/verificar', ['fecha' => $fecha]);
}

/**
 * Crear nuevo pedido (endpoint que sí existe)
 */
function crearPedido($datos) {
    $api = getAPIClient();
    return $api->post('pedidos', $datos);
}

/**
 * Obtener semanas disponibles (endpoint que sí existe)
 */
function obtenerSemanasDisponibles() {
    $api = getAPIClient();
    return $api->get('pedidos/semanas-disponibles');
}

/**
 * Obtener perfil de usuario para pedidos (endpoint que sí existe)
 */
function obtenerPerfilPedidos() {
    $api = getAPIClient();
    return $api->get('pedidos/perfil');
}

/**
 * Obtener validaciones de cancelaciones (endpoint que sí existe)
 */
function obtenerValidacionesCancelaciones() {
    $api = getAPIClient();
    return $api->get('cancelaciones/validaciones');
}

/**
 * Crear nueva cancelación (endpoint que sí existe)
 */
function crearCancelacion($datos) {
    $api = getAPIClient();
    return $api->post('cancelaciones', $datos);
}

/**
 * Obtener mis cancelaciones (endpoint que sí existe)
 */
function obtenerMisCancelaciones() {
    $api = getAPIClient();
    return $api->get('cancelaciones/mis-cancelaciones');
}

/**
 * DEBUG: Verificar estado de todos los endpoints
 */
function verificarEndpoints() {
    $api = getAPIClient();
    $resultados = [];
    
    $endpoints = [
        'Perfil empleado' => ['empleados/perfil', 'GET'],
        'Validaciones cancelaciones' => ['cancelaciones/validaciones', 'GET'],
        'Mis pedidos' => ['pedidos/mis-pedidos', 'GET'],
        'Semanas disponibles' => ['pedidos/semanas-disponibles', 'GET'],
        'Perfil pedidos' => ['pedidos/perfil', 'GET'],
        'Mis cancelaciones' => ['cancelaciones/mis-cancelaciones', 'GET']
    ];
    
    foreach ($endpoints as $nombre => $config) {
        try {
            $response = $api->get($config[0]);
            $resultados[$nombre] = [
                'status' => $response['success'] ? 'OK' : 'ERROR',
                'endpoint' => $config[0],
                'method' => $config[1],
                'error' => $response['error'] ?? null
            ];
        } catch (Exception $e) {
            $resultados[$nombre] = [
                'status' => 'EXCEPCIÓN',
                'endpoint' => $config[0],
                'method' => $config[1],
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $resultados;
}