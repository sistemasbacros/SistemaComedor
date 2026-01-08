<?php
/**
 * Configuración Global de la API
 * Este archivo centraliza toda la configuración de conexión a la API externa
 * y detecta automáticamente el entorno (local vs producción)
 */

// ========== DETECCIÓN AUTOMÁTICA DE ENTORNO ==========
function detectarEntorno() {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    
    // Si contiene localhost o 127.0.0.1, es entorno local
    if (strpos($host, 'localhost') !== false || 
        strpos($host, '127.0.0.1') !== false ||
        strpos($host, '192.168.') !== false) {
        return 'local';
    }
    
    // Si contiene "desarollo" o "dev", es entorno de desarrollo
    if (strpos($host, 'desarollo') !== false || 
        strpos($host, 'dev') !== false ||
        strpos($host, 'desarrollo') !== false) {
        return 'desarrollo';
    }
    
    // Cualquier otro caso es producción
    return 'produccion';
}

// ========== CONFIGURACIÓN POR ENTORNO ==========
$entorno = detectarEntorno();

// URLs base de la API según el entorno
$API_CONFIG = [
    'local' => [
        'base_url' => 'http://localhost:3000',   // API Backend (Node.js puerto 3000)
        'app_url' => 'http://localhost:8000',    // Frontend PHP (puerto 8000)
        'timeout' => 10,
        'connect_timeout' => 5,
        'debug' => true
    ],
    'desarrollo' => [
        'base_url' => 'http://desarollo-bacros:3000',        // API Backend (Node.js puerto 3000)
        'app_url' => 'http://desarollo-bacros/Comedor',      // Frontend PHP (puerto 80)
        'timeout' => 15,
        'connect_timeout' => 10,
        'debug' => true
    ],
    'produccion' => [
        'base_url' => 'https://api.bacrocorp.com',           // API Backend (cambiar URL)
        'app_url' => 'https://bacrocorp.com/Comedor',        // Frontend PHP (cambiar URL)
        'timeout' => 20,
        'connect_timeout' => 10,
        'debug' => false
    ]
];

// ========== OVERRIDE MANUAL (OPCIONAL) ==========
// Si necesitas forzar un entorno específico, descomenta y establece el valor:
// $entorno = 'local'; // Opciones: 'local', 'desarrollo', 'produccion'

// ========== CONSTANTES GLOBALES ==========
define('API_ENTORNO', $entorno);
define('API_BASE_URL', $API_CONFIG[$entorno]['base_url']);
define('APP_BASE_URL', $API_CONFIG[$entorno]['app_url']);
define('API_TIMEOUT', $API_CONFIG[$entorno]['timeout']);
define('API_CONNECT_TIMEOUT', $API_CONFIG[$entorno]['connect_timeout']);
define('API_DEBUG', $API_CONFIG[$entorno]['debug']);

// ========== ENDPOINTS DISPONIBLES ==========
// Definir todos los endpoints de la API
// NOTA: Todos los endpoints (excepto LOGIN) requieren JWT token en header Authorization
define('API_ENDPOINTS', [
    // Autenticación (NO requiere token)
    'LOGIN' => '/auth/login',                    // POST { usuario, contrasena } → { token, user_data }
    'LOGOUT' => '/auth/logout',                  // POST con token
    'VALIDATE_TOKEN' => '/auth/validate',        // GET con token
    'REFRESH_TOKEN' => '/auth/refresh',          // POST con token
    
    // Usuarios (requiere token JWT)
    'USUARIOS_LIST' => '/api/empleados',         // GET con token
    'USUARIOS_CREATE' => '/api/empleados',       // POST con token
    'USUARIOS_UPDATE' => '/api/empleados/:id',   // PUT con token
    'USUARIOS_DELETE' => '/api/empleados/:id',   // DELETE con token
    'USUARIO_PERFIL' => '/api/usuario/perfil',   // GET con token (datos del usuario autenticado)
    
    // Pedidos (requiere token JWT - el usuario se obtiene del token)
    'PEDIDOS_PERFIL' => '/api/pedidos/perfil',           // GET con token (datos del usuario autenticado)
    'PEDIDOS_VERIFICAR' => '/api/pedidos/verificar',     // GET con token + ?fecha=YYYY-MM-DD
    'PEDIDOS_CREAR' => '/api/pedidos',                   // POST con token (crear pedido semanal)
    'PEDIDOS_MIS_PEDIDOS' => '/api/pedidos/mis-pedidos', // GET con token (historial del usuario)
    'PEDIDOS_MIS_CONSUMOS' => '/api/pedidos/mis-consumos', // GET con token + ?fecha=YYYY-MM-DD (consulta consumos semanales)
    'PEDIDOS_SEMANAS_DISPONIBLES' => '/api/pedidos/semanas-disponibles', // GET con token (lista de semanas para consultar)
    
    // Estadísticas (requiere token JWT)
    'ESTADISTICAS_DASHBOARD' => '/api/estadisticas/dashboard',
    'ESTADISTICAS_CANCELACIONES' => '/api/estadisticas/cancelaciones',
    'ESTADISTICAS_CONSUMO' => '/api/estadisticas/consumo',
    'ESTADISTICAS_MIS_CONSUMOS' => '/api/estadisticas/mis-consumos', // Consumos del usuario autenticado
    
    // Menú (requiere token JWT)
    'MENU_LIST' => '/api/menu',
    'MENU_SEMANA' => '/api/menu/semana/:fecha',  // GET menú de una semana específica
    'MENU_CREATE' => '/api/menu',
    'MENU_UPDATE' => '/api/menu/:id',
    'MENU_DELETE' => '/api/menu/:id',
    
    // Compras (requiere token JWT)
    'COMPRAS_LIST' => '/api/compras',
    'COMPRAS_CREATE' => '/api/compras',
    'COMPRAS_UPDATE' => '/api/compras/:id',
    
    // Inventario (requiere token JWT)
    'INVENTARIO_LIST' => '/api/inventario',
    'INVENTARIO_UPDATE' => '/api/inventario/:id',
]);

// ========== FUNCIONES HELPER ==========

/**
 * Construye una URL completa del endpoint con parámetros
 * @param string $endpointKey Clave del endpoint (ej: 'LOGIN', 'USUARIOS_LIST')
 * @param array $params Parámetros para reemplazar en la URL (ej: ['id' => 123])
 * @return string URL completa
 */
function getApiUrl($endpointKey, $params = []) {
    if (!isset(API_ENDPOINTS[$endpointKey])) {
        throw new Exception("Endpoint no definido: {$endpointKey}");
    }
    
    $endpoint = API_ENDPOINTS[$endpointKey];
    
    // Reemplazar parámetros en la URL (ej: /api/empleados/:id)
    foreach ($params as $key => $value) {
        $endpoint = str_replace(":{$key}", $value, $endpoint);
    }
    
    return API_BASE_URL . $endpoint;
}

/**
 * Construye una URL de la aplicación web
 * @param string $path Ruta relativa (ej: 'MenUsuario.php', 'admicome4.php')
 * @return string URL completa de la aplicación
 */
function getAppUrl($path = '') {
    $path = ltrim($path, '/');
    return APP_BASE_URL . ($path ? '/' . $path : '');
}

/**
 * Registra mensajes de debug si está habilitado
 * @param string $message Mensaje a registrar
 * @param mixed $data Datos adicionales (opcional)
 */
function apiDebugLog($message, $data = null) {
    if (API_DEBUG) {
        $logMessage = "[API DEBUG - " . date('Y-m-d H:i:s') . "] " . $message;
        if ($data !== null) {
            $logMessage .= " | Data: " . json_encode($data);
        }
        error_log($logMessage);
    }
}

/**
 * Obtiene información del entorno actual
 * @return array Información del entorno
 */
function getApiEnvironmentInfo() {
    return [
        'entorno' => API_ENTORNO,
        'api_base_url' => API_BASE_URL,
        'app_base_url' => APP_BASE_URL,
        'timeout' => API_TIMEOUT,
        'debug' => API_DEBUG,
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    ];
}

/**
 * Obtiene el JWT token desde la sesión
 * @return string|null Token JWT o null si no existe
 */
function getJwtToken() {
    return $_SESSION['jwt_token'] ?? null;
}

/**
 * Guarda el JWT token en la sesión
 * @param string $token Token JWT
 */
function setJwtToken($token) {
    $_SESSION['jwt_token'] = $token;
}

/**
 * Elimina el JWT token de la sesión
 */
function clearJwtToken() {
    unset($_SESSION['jwt_token']);
}

/**
 * Crea headers HTTP con JWT token para peticiones a la API
 * @param array $additionalHeaders Headers adicionales (opcional)
 * @return array Headers completos con Authorization
 */
function getApiHeaders($additionalHeaders = []) {
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    // Agregar token JWT si existe
    $token = getJwtToken();
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    // Agregar headers adicionales
    foreach ($additionalHeaders as $key => $value) {
        $headers[] = $key . ': ' . $value;
    }
    
    return $headers;
}

// ========== LOG DE INICIO (SOLO EN DEBUG) ==========
if (API_DEBUG) {
    apiDebugLog('Configuración API cargada', getApiEnvironmentInfo());
}

// ========== VALIDACIÓN DE CONFIGURACIÓN ==========
// Verificar que la URL base esté configurada
if (empty(API_BASE_URL)) {
    throw new Exception('Error de configuración: API_BASE_URL no está definida');
}

?>
