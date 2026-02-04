<?php
// Cargar configuración global de la API
require_once __DIR__ . '/config_api.php';
require_once __DIR__ . '/token_manager.php';  // ← NUEVO: Sistema unificado de tokens

/**
 * Helper para realizar peticiones a la API externa con JWT
 * Uso: require_once 'api_client.php';
 */

class APIClient {
    private $baseUrl;
    private $token;
    private $lastError = null;
    
    /**
     * Constructor
     * @param string $baseUrl URL base de la API (null = usar config global)
     */
    public function __construct($baseUrl = null) {
        // Usar configuración global si no se proporciona URL personalizada
        $this->baseUrl = rtrim($baseUrl ?? API_BASE_URL, '/');
        
        // UNIFICADO: Usar sistema centralizado de tokens
        $this->token = getValidToken();
        
        // Log en modo debug
        apiDebugLog('APIClient inicializado', [
            'base_url' => $this->baseUrl,
            'has_token' => !empty($this->token),
            'token_preview' => $this->token ? substr($this->token, 0, 20) . '...' : 'sin token'
        ]);
    }
    
    /**
     * Establecer token manualmente
     */
    public function setToken($token) {
        $this->token = $token;
        saveAPIToken($token);  // ← UNIFICADO: usar función centralizada
        return $this;
    }
    
    /**
     * Verificar si cliente está autenticado
     */
    public function isAuthenticated() {
        return hasValidToken();  // ← UNIFICADO: usar función centralizada
    }
    
    /**
     * Obtener el último error
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Verificar si el token está expirado
     */
    public function isTokenExpired() {
        if (!isset($_SESSION['token_created_at']) || !isset($_SESSION['token_expires_in'])) {
            return true;
        }
        
        $expiresAt = $_SESSION['token_created_at'] + $_SESSION['token_expires_in'];
        return time() >= $expiresAt;
    }
    
    /**
     * Realizar petición GET
     */
    public function get($endpoint, $params = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->request('GET', $url);
    }
    
    /**
     * Realizar petición POST
     */
    public function post($endpoint, $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        return $this->request('POST', $url, $data);
    }
    
    /**
     * Realizar petición PUT
     */
    public function put($endpoint, $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        return $this->request('PUT', $url, $data);
    }
    
    /**
     * Realizar petición DELETE
     */
    public function delete($endpoint, $data = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        return $this->request('DELETE', $url, $data);
    }
    
    /**
     * Función genérica para realizar peticiones HTTP
     */
    private function request($method, $url, $data = null) {
        // DEBUG: Mostrar URL exacta que se está consultando
        apiDebugLog("Petición HTTP $method", [
            'url_completa' => $url,
            'base_url_configurada' => $this->baseUrl,
            'tiene_token' => !empty($this->token)
        ]);
        
        $ch = curl_init($url);
        
        // Headers por defecto
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Agregar token de autorización si existe
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
            apiDebugLog('Token añadido a headers', ['token_preview' => substr($this->token, 0, 20) . '...']);
        } else {
            apiDebugLog('Sin token - petición sin autenticación');
        }
        
        // Configurar método y datos
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null && in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }
        
        // Configuración de cURL
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Ejecutar petición
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Manejo de errores
        if ($error) {
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $error,
                'http_code' => 0
            ];
        }
        
        // Decodificar respuesta JSON
        $responseData = json_decode($response, true);
        
        // Verificar si la respuesta es exitosa (2xx)
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $responseData,
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'error' => $responseData['error'] ?? $responseData['message'] ?? 'Error desconocido',
                'data' => $responseData,
                'http_code' => $httpCode
            ];
        }
    }
    
    /**
     * Login (caso especial sin token)
     */
    public function login($usuario, $contrasena) {
        // Guardar token temporal
        $oldToken = $this->token;
        $this->token = null; // No enviar token en login
        
        $result = $this->post('auth/login', [
            'usuario' => $usuario,
            'contrasena' => $contrasena
        ]);
        
        // Restaurar token si falló
        if (!$result['success']) {
            $this->token = $oldToken;
        } else {
            // Guardar token en sesión si login exitoso
            if (isset($result['data']['token'])) {
                $this->setToken($result['data']['token']);
                
                $_SESSION['token_type'] = $result['data']['token_type'] ?? 'Bearer';
                $_SESSION['token_expires_in'] = $result['data']['expires_in'] ?? 86400;
                $_SESSION['token_created_at'] = time();
            }
        }
        
        return $result;
    }
    
    /**
     * Verificar estado de autenticación
     */
    public function isAuthenticated() {
        return $this->token !== null && !$this->isTokenExpired();
    }
    
    /**
     * Cerrar sesión (limpia el token)
     */
    public function logout() {
        $this->token = null;
        unset($_SESSION['jwt_token']);
        unset($_SESSION['token_type']);
        unset($_SESSION['token_expires_in']);
        unset($_SESSION['token_created_at']);
    }
}

/**
 * Función helper global para obtener instancia de APIClient
 */
function getAPIClient() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new APIClient();
    }
    
    return $instance;
}

// ========== FUNCIONES ESPECÍFICAS PARA PEDIDOS ==========

/**
 * Obtener perfil del usuario autenticado desde la API
 * @return array|null Datos del usuario o null si hay error
 */
function obtenerPerfilUsuario() {
    $api = getAPIClient();
    
    if (!$api->isAuthenticated()) {
        return null;
    }
    
    $response = $api->get('api/pedidos/perfil');
    
    if ($response['success']) {
        return $response['data'];
    }
    
    apiDebugLog('Error al obtener perfil de usuario', $response);
    return null;
}

/**
 * Verificar si el usuario puede realizar pedidos para una fecha
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @return array ['total' => int, 'puede_ordenar' => bool, 'mensaje' => string]
 */
function verificarPedidosExistentes($fecha) {
    $api = getAPIClient();
    
    if (!$api->isAuthenticated()) {
        return [
            'total' => 0,
            'puede_ordenar' => false,
            'mensaje' => 'No autenticado'
        ];
    }
    
    $response = $api->get('api/pedidos/verificar', ['fecha' => $fecha]);
    
    if ($response['success']) {
        return $response['data'];
    }
    
    apiDebugLog('Error al verificar pedidos', $response);
    return [
        'total' => 0,
        'puede_ordenar' => false,
        'mensaje' => $response['error'] ?? 'Error al verificar pedidos'
    ];
}

/**
 * Crear pedido semanal (desayunos y comidas)
 * @param string $fecha_semana Fecha del lunes de la semana (YYYY-MM-DD)
 * @param array $desayunos ['lunes' => 'Desayuno'|'', 'martes' => ...]
 * @param array $comidas ['lunes' => 'Comida'|'', 'martes' => ...]
 * @return array ['success' => bool, 'message' => string]
 */
function crearPedidoSemanal($fecha_semana, $desayunos, $comidas) {
    $api = getAPIClient();
    
    if (!$api->isAuthenticated()) {
        return [
            'success' => false,
            'message' => 'Sesión expirada. Por favor, inicia sesión nuevamente.'
        ];
    }
    
    $response = $api->post('api/pedidos', [
        'fecha_semana' => $fecha_semana,
        'desayunos' => $desayunos,
        'comidas' => $comidas
    ]);
    
    if ($response['success']) {
        return [
            'success' => true,
            'message' => $response['data']['message'] ?? 'Pedido registrado con éxito'
        ];
    }
    
    apiDebugLog('Error al crear pedido', $response);
    return [
        'success' => false,
        'message' => $response['error'] ?? 'Error al registrar el pedido'
    ];
}

/**
 * Validar que el usuario esté autenticado, sino redirigir a login
 * @param string $loginUrl URL de la página de login
 */
function requireAuthentication($loginUrl = 'Login2.php') {
    $api = getAPIClient();
    
    if (!$api->isAuthenticated()) {
        // Guardar URL actual para redirigir después del login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Obtener consumos semanales del usuario autenticado
 * @param string|null $fecha Fecha del lunes de la semana (YYYY-MM-DD). Si es null, usa la semana actual
 * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
 */
function obtenerConsumosSemanales($fecha = null) {
    $api = getAPIClient();
    
    if (!$api->isAuthenticated()) {
        return [
            'success' => false,
            'data' => null,
            'error' => 'No autenticado'
        ];
    }
    
    $params = [];
    if ($fecha !== null) {
        $params['fecha'] = $fecha;
    }
    
    error_log("=== Llamando a API consumos con fecha: " . ($fecha ?? 'null') . " ===");
    
    $response = $api->get('api/pedidos/mis-consumos', $params);
    
    error_log("Response RAW de API: " . print_r($response, true));
    
    if ($response['success']) {
        error_log("API SUCCESS - Data: " . print_r($response['data'], true));
        return [
            'success' => true,
            'data' => $response['data'],
            'error' => null
        ];
    }
    
    apiDebugLog('Error al obtener consumos semanales', $response);
    error_log("API ERROR: " . ($response['error'] ?? 'Error desconocido'));
    return [
        'success' => false,
        'data' => null,
        'error' => $response['error'] ?? 'Error al obtener consumos'
    ];
}

/**
 * Obtener lista de semanas disponibles para consultar
 * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
 */
function obtenerSemanasDisponibles() {
    $api = getAPIClient();
    
    if (!$api->isAuthenticated()) {
        return [
            'success' => false,
            'data' => null,
            'error' => 'No autenticado'
        ];
    }
    
    $response = $api->get('api/pedidos/semanas-disponibles');
    
    if ($response['success']) {
        return [
            'success' => true,
            'data' => $response['data'],
            'error' => null
        ];
    }
    
    apiDebugLog('Error al obtener semanas disponibles', $response);
    return [
        'success' => false,
        'data' => null,
        'error' => $response['error'] ?? 'Error al obtener semanas'
    ];
}

?>
