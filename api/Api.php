<?php
/**
 * ============================================================================
 * API UNIFICADA - Sistema de Comedor
 * ============================================================================
 *
 * Este archivo centraliza TODA la lógica de comunicación con la API.
 * Reemplaza: config_api.php, api_client.php, ApiClient.php, token_manager.php, endpoint_helpers.php
 *
 * USO:
 *   require_once 'Api.php';
 *
 *   // Login
 *   $result = Api::auth()->login('usuario', 'password');
 *
 *   // Obtener pedidos
 *   $pedidos = Api::pedidos()->misPedidos();
 *
 *   // Crear pedido
 *   Api::pedidos()->crear($fecha, $desayunos, $comidas);
 */

// ============================================================================
// CONFIGURACIÓN DE ENTORNO
// ============================================================================

class ApiConfig {

    private static $instance = null;
    private $entorno;
    private $config;

    private function __construct() {
        $this->entorno = $this->detectarEntorno();
        $this->config = $this->getConfigPorEntorno();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Detecta el entorno de ejecución (local, desarrollo, produccion)
     */
    private function detectarEntorno() {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $enDocker = file_exists('/.dockerenv');

        // Docker con localhost/IP local = producción
        if ($enDocker && $this->esHostLocal($host)) {
            return 'produccion';
        }

        // Localhost sin Docker = local
        if ($this->esHostLocal($host)) {
            return 'local';
        }

        // Servidor de desarrollo
        if (strpos($host, 'desarollo') !== false || strpos($host, 'dev') !== false) {
            return 'desarrollo';
        }

        return 'produccion';
    }

    private function esHostLocal($host) {
        return strpos($host, 'localhost') !== false
            || strpos($host, '127.0.0.1') !== false
            || strpos($host, '192.168.') !== false
            || strpos($host, '10.') !== false;
    }

    private function getConfigPorEntorno() {
        $configs = [
            'local' => [
                'api_url' => 'http://127.0.0.1:3000',
                'timeout' => 10,
                'debug' => true
            ],
            'desarrollo' => [
                'api_url' => 'http://desarollo-bacros:3000',
                'timeout' => 15,
                'debug' => true
            ],
            'produccion' => [
                'api_url' => 'http://host.docker.internal:3000',
                'timeout' => 20,
                'debug' => true
            ]
        ];

        // OVERRIDE: Descomentar para forzar un entorno específico
        // $this->entorno = 'produccion';

        return $configs[$this->entorno];
    }

    public function getApiUrl()  { return $this->config['api_url']; }
    public function getTimeout() { return $this->config['timeout']; }
    public function isDebug()    { return $this->config['debug']; }
    public function getEntorno() { return $this->entorno; }

    public function getAppUrl($path = '') {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = ltrim($path, '/');
        return $protocol . '://' . $host . ($path ? '/' . $path : '');
    }
}

// ============================================================================
// GESTIÓN DE TOKENS
// ============================================================================

class TokenManager {

    private static function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Obtiene el token JWT de la sesión
     */
    public static function getToken() {
        self::ensureSession();
        return $_SESSION['api_token'] ?? null;
    }

    /**
     * Verifica si hay un token válido (no expirado)
     */
    public static function hasValidToken() {
        $token = self::getToken();
        if (!$token) return false;

        $createdAt = $_SESSION['token_created_at'] ?? 0;
        $expiresIn = $_SESSION['token_expires_in'] ?? 86400;

        if ($createdAt > 0 && (time() - $createdAt) > $expiresIn) {
            self::clear();
            return false;
        }

        return true;
    }

    /**
     * Guarda el token en la sesión
     */
    public static function save($token, $expiresIn = 86400, $tokenType = 'Bearer') {
        self::ensureSession();
        $_SESSION['api_token'] = $token;
        $_SESSION['jwt_token'] = $token; // Compatibilidad legacy
        $_SESSION['token_type'] = $tokenType;
        $_SESSION['token_expires_in'] = $expiresIn;
        $_SESSION['token_created_at'] = time();
    }

    /**
     * Limpia todos los tokens de la sesión
     */
    public static function clear() {
        self::ensureSession();
        unset($_SESSION['api_token'], $_SESSION['jwt_token'], $_SESSION['token_type'],
              $_SESSION['token_expires_in'], $_SESSION['token_created_at']);
    }

    /**
     * Verifica si el usuario está autenticado
     */
    public static function isAuthenticated() {
        self::ensureSession();
        $hasUserData = !empty($_SESSION['user_name']) || !empty($_SESSION['nombre']);
        return $hasUserData && self::hasValidToken();
    }

    /**
     * Redirige al login si no está autenticado
     */
    public static function requireAuth($loginUrl = 'Admiin.php') {
        if (!self::isAuthenticated()) {
            self::clear();
            header('Location: ' . ApiConfig::getInstance()->getAppUrl($loginUrl));
            exit;
        }
    }

    /**
     * Obtiene información del usuario actual
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) return null;

        self::ensureSession();
        return [
            'id_empleado' => $_SESSION['user_id'] ?? $_SESSION['id_empleado'] ?? null,
            'nombre' => $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? '',
            'area' => $_SESSION['user_area'] ?? $_SESSION['area'] ?? '',
            'usuario' => $_SESSION['user_usuario'] ?? $_SESSION['usuario'] ?? ''
        ];
    }
}

// ============================================================================
// CLIENTE HTTP BASE
// ============================================================================

class HttpClient {

    private $baseUrl;
    private $timeout;
    private $lastError;
    private $lastResponse;

    public function __construct() {
        $config = ApiConfig::getInstance();
        $this->baseUrl = rtrim($config->getApiUrl(), '/');
        $this->timeout = $config->getTimeout();
    }

    /**
     * Realiza una petición GET
     */
    public function get($endpoint, $params = []) {
        $url = $this->buildUrl($endpoint, $params);
        return $this->request('GET', $url);
    }

    /**
     * Realiza una petición POST
     */
    public function post($endpoint, $data = []) {
        $url = $this->buildUrl($endpoint);
        return $this->request('POST', $url, $data);
    }

    /**
     * Realiza una petición PUT
     */
    public function put($endpoint, $data = []) {
        $url = $this->buildUrl($endpoint);
        return $this->request('PUT', $url, $data);
    }

    /**
     * Realiza una petición DELETE
     */
    public function delete($endpoint, $data = []) {
        $url = $this->buildUrl($endpoint);
        return $this->request('DELETE', $url, $data);
    }

    private function buildUrl($endpoint, $params = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    private function request($method, $url, $data = null) {
        $ch = curl_init($url);

        // Headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        // Agregar token si existe
        $token = TokenManager::getToken();
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        // Configuración cURL
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        // Body para POST/PUT/DELETE
        if ($data !== null && in_array($method, ['POST', 'PUT', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Ejecutar
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $this->lastResponse = $response;

        // Error de conexión
        if ($error) {
            $this->lastError = $error;
            return $this->error('Error de conexión: ' . $error, 0);
        }

        // Decodificar respuesta
        $responseData = json_decode($response, true);

        // Respuesta exitosa (2xx)
        if ($httpCode >= 200 && $httpCode < 300) {
            return $this->success($responseData, $httpCode);
        }

        // Error HTTP
        $errorMsg = $responseData['error'] ?? $responseData['message'] ?? 'Error HTTP ' . $httpCode;
        return $this->error($errorMsg, $httpCode, $responseData);
    }

    private function success($data, $httpCode) {
        return [
            'success' => true,
            'data' => $data,
            'http_code' => $httpCode
        ];
    }

    private function error($message, $httpCode, $data = null) {
        return [
            'success' => false,
            'error' => $message,
            'data' => $data,
            'http_code' => $httpCode
        ];
    }

    public function getLastError()    { return $this->lastError; }
    public function getLastResponse() { return $this->lastResponse; }
}

// ============================================================================
// MÓDULOS DE API
// ============================================================================

/**
 * Módulo de Autenticación
 */
class AuthApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Iniciar sesión
     * @return array ['success' => bool, 'data' => ['token' => string, 'user' => array]]
     */
    public function login($usuario, $contrasena) {
        // Login usa URL base sin /api
        $baseUrl = ApiConfig::getInstance()->getApiUrl();
        $loginUrl = $baseUrl . '/auth/login';

        $ch = curl_init($loginUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'usuario' => $usuario,
                'contrasena' => $contrasena
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Error de conexión
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $curlError,
                'http_code' => 0
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($data['token'])) {
            // Guardar token automáticamente
            TokenManager::save(
                $data['token'],
                $data['expires_in'] ?? 86400,
                $data['token_type'] ?? 'Bearer'
            );

            return ['success' => true, 'data' => $data, 'http_code' => $httpCode];
        }

        return [
            'success' => false,
            'error' => $data['error'] ?? $data['message'] ?? 'Error de autenticación (HTTP ' . $httpCode . ')',
            'http_code' => $httpCode,
            'response' => $response
        ];
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        TokenManager::clear();
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
    }

    /**
     * Validar token actual
     */
    public function validarToken() {
        return $this->http->get('auth/validate');
    }

    /**
     * Refrescar token
     */
    public function refrescarToken() {
        $result = $this->http->post('auth/refresh');
        if ($result['success'] && isset($result['data']['token'])) {
            TokenManager::save($result['data']['token']);
        }
        return $result;
    }
}

/**
 * Módulo de Pedidos
 */
class PedidosApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Obtener perfil del usuario para pedidos
     */
    public function perfil() {
        return $this->http->get('api/pedidos/perfil');
    }

    /**
     * Verificar si puede hacer pedidos para una fecha
     */
    public function verificar($fecha) {
        return $this->http->get('api/pedidos/verificar', ['fecha' => $fecha]);
    }

    /**
     * Crear pedido semanal
     * @param string $fechaSemana Fecha del lunes (YYYY-MM-DD)
     * @param array $desayunos ['lunes' => 'Desayuno', 'martes' => '', ...]
     * @param array $comidas ['lunes' => 'Comida', 'martes' => '', ...]
     */
    public function crear($fechaSemana, $desayunos, $comidas) {
        return $this->http->post('api/pedidos', [
            'fecha_semana' => $fechaSemana,
            'desayunos' => $desayunos,
            'comidas' => $comidas
        ]);
    }

    /**
     * Agendar pedidos (alias de crear)
     */
    public function agendar($fechaSemana, $desayunos, $comidas) {
        return $this->http->post('api/pedidos/agendar-pedidos', [
            'fecha_semana' => $fechaSemana,
            'desayunos' => $desayunos,
            'comidas' => $comidas
        ]);
    }

    /**
     * Obtener mis pedidos
     */
    public function misPedidos() {
        return $this->http->get('api/pedidos/mis-pedidos');
    }

    /**
     * Obtener semanas disponibles
     */
    public function semanasDisponibles() {
        return $this->http->get('api/pedidos/semanas-disponibles');
    }
}

/**
 * Módulo de Consumos
 */
class ConsumosApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Obtener mis consumos semanales
     * @param string|null $fecha Fecha del lunes de la semana (YYYY-MM-DD)
     */
    public function misConsumos($fecha = null) {
        $params = $fecha ? ['fecha' => $fecha] : [];
        return $this->http->get('api/consumos/mis-consumos', $params);
    }

    /**
     * Obtener estadísticas de consumo
     */
    public function estadisticas() {
        return $this->http->get('api/estadisticas/mis-consumos');
    }
}

/**
 * Módulo de Cancelaciones
 */
class CancelacionesApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Obtener validaciones para cancelaciones
     */
    public function validaciones() {
        return $this->http->get('api/cancelaciones/validaciones');
    }

    /**
     * Crear una cancelación
     */
    public function crear($datos) {
        return $this->http->post('api/cancelaciones', $datos);
    }

    /**
     * Obtener mis cancelaciones
     */
    public function misCancelaciones() {
        return $this->http->get('api/cancelaciones/mis-cancelaciones');
    }
}

/**
 * Módulo de Empleados
 */
class EmpleadosApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Obtener perfil del empleado actual
     */
    public function perfil() {
        return $this->http->get('api/empleados/perfil');
    }

    /**
     * Listar empleados
     */
    public function listar() {
        return $this->http->get('api/empleados');
    }

    /**
     * Crear empleado
     */
    public function crear($datos) {
        return $this->http->post('api/empleados', $datos);
    }

    /**
     * Actualizar empleado
     */
    public function actualizar($id, $datos) {
        return $this->http->put("api/empleados/{$id}", $datos);
    }

    /**
     * Eliminar empleado
     */
    public function eliminar($id) {
        return $this->http->delete("api/empleados/{$id}");
    }
}

/**
 * Módulo de Menú
 */
class MenuApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Listar menús
     */
    public function listar() {
        return $this->http->get('api/menu');
    }

    /**
     * Obtener menú de una semana
     */
    public function semana($fecha) {
        return $this->http->get("api/menu/semana/{$fecha}");
    }

    /**
     * Crear menú
     */
    public function crear($datos) {
        return $this->http->post('api/menu', $datos);
    }

    /**
     * Actualizar menú
     */
    public function actualizar($id, $datos) {
        return $this->http->put("api/menu/{$id}", $datos);
    }

    /**
     * Eliminar menú
     */
    public function eliminar($id) {
        return $this->http->delete("api/menu/{$id}");
    }
}

/**
 * Módulo de Reportes
 */
class ReportesApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * Obtener reporte detallado
     */
    public function detallado($params = []) {
        return $this->http->get('reporte/reporte-detallado', $params);
    }

    /**
     * Dashboard de estadísticas
     */
    public function dashboard() {
        return $this->http->get('api/estadisticas/dashboard');
    }

    /**
     * Estadísticas de cancelaciones
     */
    public function cancelaciones() {
        return $this->http->get('api/estadisticas/cancelaciones');
    }

    /**
     * Estadísticas de consumo
     */
    public function consumo() {
        return $this->http->get('api/estadisticas/consumo');
    }
}

// ============================================================================
// CLASE PRINCIPAL - PUNTO DE ENTRADA ÚNICO
// ============================================================================

class Api {

    private static $auth;
    private static $pedidos;
    private static $consumos;
    private static $cancelaciones;
    private static $empleados;
    private static $menu;
    private static $reportes;

    // Módulos de API
    public static function auth()          { return self::$auth ??= new AuthApi(); }
    public static function pedidos()       { return self::$pedidos ??= new PedidosApi(); }
    public static function consumos()      { return self::$consumos ??= new ConsumosApi(); }
    public static function cancelaciones() { return self::$cancelaciones ??= new CancelacionesApi(); }
    public static function empleados()     { return self::$empleados ??= new EmpleadosApi(); }
    public static function menu()          { return self::$menu ??= new MenuApi(); }
    public static function reportes()      { return self::$reportes ??= new ReportesApi(); }

    // Acceso directo a utilidades comunes
    public static function config()      { return ApiConfig::getInstance(); }
    public static function token()       { return new TokenManager(); }

    // Atajos para funciones comunes de autenticación
    public static function isAuthenticated() { return TokenManager::isAuthenticated(); }
    public static function requireAuth()     { TokenManager::requireAuth(); }
    public static function getCurrentUser()  { return TokenManager::getCurrentUser(); }
    public static function getToken()        { return TokenManager::getToken(); }

    // Cliente HTTP directo (para casos especiales)
    public static function http() { return new HttpClient(); }

    /**
     * Información del entorno actual (para debug)
     */
    public static function info() {
        $config = ApiConfig::getInstance();
        return [
            'entorno' => $config->getEntorno(),
            'api_url' => $config->getApiUrl(),
            'timeout' => $config->getTimeout(),
            'debug' => $config->isDebug(),
            'authenticated' => TokenManager::isAuthenticated(),
            'has_token' => TokenManager::hasValidToken()
        ];
    }
}

// ============================================================================
// FUNCIONES HELPER GLOBALES (Compatibilidad con código legacy)
// ============================================================================

// Estas funciones mantienen compatibilidad con el código existente
// Se recomienda migrar gradualmente a usar Api::modulo()->metodo()

function getAPIClient() {
    return Api::http();
}

function getJwtToken() {
    return TokenManager::getToken();
}

function setJwtToken($token) {
    TokenManager::save($token);
}

function clearJwtToken() {
    TokenManager::clear();
}

function getApiHeaders($additionalHeaders = []) {
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    $token = TokenManager::getToken();
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    foreach ($additionalHeaders as $key => $value) {
        $headers[] = $key . ': ' . $value;
    }

    return $headers;
}

function getApiUrl($endpointKey, $params = []) {
    return ApiConfig::getInstance()->getApiUrl() . '/' . ltrim($endpointKey, '/');
}

function getAppUrl($path = '') {
    return ApiConfig::getInstance()->getAppUrl($path);
}

function isUserAuthenticated() {
    return TokenManager::isAuthenticated();
}

function requireAuthentication($loginUrl = 'Admiin.php') {
    TokenManager::requireAuth($loginUrl);
}

function getCurrentUser() {
    return TokenManager::getCurrentUser();
}

function getValidToken() {
    return TokenManager::getToken();
}

function hasValidToken() {
    return TokenManager::hasValidToken();
}

function saveAPIToken($token, $expiresIn = 86400, $tokenType = 'Bearer') {
    TokenManager::save($token, $expiresIn, $tokenType);
}

function clearTokens() {
    TokenManager::clear();
}

// Funciones específicas de pedidos (compatibilidad)
function obtenerPerfilUsuario() {
    $result = Api::pedidos()->perfil();
    return $result['success'] ? $result['data'] : null;
}

function verificarPedidosExistentes($fecha) {
    $result = Api::pedidos()->verificar($fecha);
    return $result['success'] ? $result['data'] : [
        'total' => 0,
        'puede_ordenar' => false,
        'mensaje' => $result['error'] ?? 'Error al verificar'
    ];
}

function crearPedidoSemanal($fechaSemana, $desayunos, $comidas) {
    $result = Api::pedidos()->crear($fechaSemana, $desayunos, $comidas);
    return [
        'success' => $result['success'],
        'message' => $result['success']
            ? ($result['data']['message'] ?? 'Pedido registrado con éxito')
            : ($result['error'] ?? 'Error al registrar el pedido')
    ];
}

function obtenerConsumosSemanales($fecha = null) {
    return Api::consumos()->misConsumos($fecha);
}

function obtenerSemanasDisponibles() {
    return Api::pedidos()->semanasDisponibles();
}

// Constantes de compatibilidad
if (!defined('API_BASE_URL')) {
    define('API_BASE_URL', ApiConfig::getInstance()->getApiUrl());
}
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', ApiConfig::getInstance()->getAppUrl());
}
if (!defined('API_ENTORNO')) {
    define('API_ENTORNO', ApiConfig::getInstance()->getEntorno());
}
if (!defined('API_TIMEOUT')) {
    define('API_TIMEOUT', ApiConfig::getInstance()->getTimeout());
}

?>
