<?php
/**
 * ============================================================================
 * Api.php — Punto de entrada único
 * ============================================================================
 *
 * Incluye toda la infraestructura y los módulos.
 * Los archivos del frontend solo necesitan:
 *
 *   require_once __DIR__ . '/api/Api.php';
 *
 * USO:
 *   Api::auth()->login('usuario', 'password')
 *   Api::pedidos()->semanasDisponibles()
 *   Api::cocina()->entradas()->listar()
 *   Api::admin()->dashboard(['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31'])
 */

// Núcleo
require_once __DIR__ . '/ApiConfig.php';
require_once __DIR__ . '/TokenManager.php';
require_once __DIR__ . '/HttpClient.php';

// Módulos (orden idéntico a BACROS_Comedor.postman_collection.json)
require_once __DIR__ . '/modules/AuthApi.php';
require_once __DIR__ . '/modules/PedidosApi.php';
require_once __DIR__ . '/modules/ConsumosApi.php';
require_once __DIR__ . '/modules/CancelacionesApi.php';
require_once __DIR__ . '/modules/EmpleadosApi.php';
require_once __DIR__ . '/modules/EstadisticasApi.php';
require_once __DIR__ . '/modules/MenuApi.php';
require_once __DIR__ . '/modules/ReporteApi.php';
require_once __DIR__ . '/modules/AdminApi.php';
require_once __DIR__ . '/modules/CheckadorApi.php';
require_once __DIR__ . '/modules/CocinaApi.php';

// ============================================================================
// Clase principal — fachada estática
// ============================================================================

class Api {

    private static $auth;
    private static $pedidos;
    private static $consumos;
    private static $cancelaciones;
    private static $empleados;
    private static $estadisticas;
    private static $menu;
    private static $reporte;
    private static $admin;
    private static $checador;
    private static $cocina;

    // Módulos (orden idéntico a la colección Postman)
    public static function auth()          { return self::$auth          ??= new AuthApi(); }
    public static function pedidos()       { return self::$pedidos       ??= new PedidosApi(); }
    public static function consumos()      { return self::$consumos      ??= new ConsumosApi(); }
    public static function cancelaciones() { return self::$cancelaciones ??= new CancelacionesApi(); }
    public static function empleados()     { return self::$empleados     ??= new EmpleadosApi(); }
    public static function estadisticas()  { return self::$estadisticas  ??= new EstadisticasApi(); }
    public static function menu()          { return self::$menu          ??= new MenuApi(); }
    public static function reporte()       { return self::$reporte       ??= new ReporteApi(); }
    public static function admin()         { return self::$admin         ??= new AdminApi(); }
    public static function checador()      { return self::$checador      ??= new CheckadorApi(); }
    public static function cocina()        { return self::$cocina        ??= new CocinaApi(); }

    // Núcleo
    public static function config() { return ApiConfig::getInstance(); }
    public static function http()   { return new HttpClient(); }

    // Atajos de autenticación
    public static function isAuthenticated() { return TokenManager::isAuthenticated(); }
    public static function requireAuth()     { TokenManager::requireAuth(); }
    public static function getCurrentUser()  { return TokenManager::getCurrentUser(); }
    public static function getToken()        { return TokenManager::getToken(); }

    public static function info() {
        $config = ApiConfig::getInstance();
        return [
            'entorno'       => $config->getEntorno(),
            'api_url'       => $config->getApiUrl(),
            'timeout'       => $config->getTimeout(),
            'debug'         => $config->isDebug(),
            'authenticated' => TokenManager::isAuthenticated(),
            'has_token'     => TokenManager::hasValidToken(),
        ];
    }
}

// ============================================================================
// Funciones helper — compatibilidad con código legacy
// ============================================================================

function getAPIClient()                                  { return Api::http(); }
function getJwtToken()                                   { return TokenManager::getToken(); }
function setJwtToken($token)                             { TokenManager::save($token); }
function clearJwtToken()                                 { TokenManager::clear(); }
function isUserAuthenticated()                           { return TokenManager::isAuthenticated(); }
function requireAuthentication($loginUrl = 'Admiin.php') { TokenManager::requireAuth($loginUrl); }
function getCurrentUser()                                { return TokenManager::getCurrentUser(); }
function getValidToken()                                 { return TokenManager::getToken(); }
function hasValidToken()                                 { return TokenManager::hasValidToken(); }
function saveAPIToken($token, $expiresIn = 86400, $tokenType = 'Bearer') {
    TokenManager::save($token, $expiresIn, $tokenType);
}
function clearTokens() { TokenManager::clear(); }

function getApiHeaders($additionalHeaders = []) {
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    $token   = TokenManager::getToken();
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    foreach ($additionalHeaders as $key => $value) {
        $headers[] = $key . ': ' . $value;
    }
    return $headers;
}

function getAppUrl($path = '') {
    return ApiConfig::getInstance()->getAppUrl($path);
}

// Funciones específicas (compatibilidad con código existente)
function obtenerPerfilUsuario() {
    $result = Api::pedidos()->perfil();
    return $result['success'] ? $result['data'] : null;
}

function verificarPedidosExistentes($fecha) {
    $result = Api::pedidos()->verificar($fecha);
    return $result['success'] ? $result['data'] : [
        'total'         => 0,
        'puede_ordenar' => false,
        'mensaje'       => $result['error'] ?? 'Error al verificar',
    ];
}

function crearPedidoSemanal($fechaSemana, $desayunos, $comidas) {
    $result = Api::pedidos()->crear($fechaSemana, $desayunos, $comidas);
    return [
        'success' => $result['success'],
        'message' => $result['success']
            ? ($result['data']['message'] ?? 'Pedido registrado con éxito')
            : ($result['error']           ?? 'Error al registrar el pedido'),
    ];
}

function obtenerConsumosSemanales($fecha = null) {
    return Api::consumos()->misConsumos($fecha);
}

function obtenerSemanasDisponibles() {
    return Api::pedidos()->semanasDisponibles();
}

// Constantes de compatibilidad
if (!defined('API_BASE_URL')) define('API_BASE_URL', ApiConfig::getInstance()->getApiUrl());
if (!defined('APP_BASE_URL')) define('APP_BASE_URL', ApiConfig::getInstance()->getAppUrl());
if (!defined('API_ENTORNO'))  define('API_ENTORNO',  ApiConfig::getInstance()->getEntorno());
if (!defined('API_TIMEOUT'))  define('API_TIMEOUT',  ApiConfig::getInstance()->getTimeout());
