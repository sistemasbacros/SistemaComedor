<?php
/**
 * ==========================================
 * API CLIENT - Cliente HTTP para la API
 * ==========================================
 * Cliente reutilizable para hacer llamadas
 * a los endpoints de la API de Comedor
 */

class ApiClient {
    
    private $baseUrl;
    private $timeout;
    private $lastResponse;
    private $lastError;
    
    // ==========================================
    // CONSTRUCTOR
    // ==========================================
    
    /**
     * Constructor del cliente API
     * 
     * @param string $baseUrl - URL base de la API (se detecta automáticamente si no se proporciona)
     * @param int $timeout - Timeout en segundos (default: 10)
     */
    public function __construct($baseUrl = null, $timeout = 10) {
        // Detectar URL automáticamente si no se proporciona
        if ($baseUrl === null) {
            $enDocker = file_exists('/.dockerenv');
            $baseUrl = $enDocker 
                ? 'http://host.docker.internal:3000' 
                : 'http://localhost:3000';
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->lastResponse = null;
        $this->lastError = null;
    }
    
    // ==========================================
    // AGENDAR PEDIDOS
    // ==========================================
    
    /**
     * Agenda un pedido semanal
     * 
     * @param string $fechaSemana - Fecha del lunes (ej: 2026-01-26)
     * @param array $desayunos - Array con desayunos por día
     *   ['lunes' => 'Desayuno', 'martes' => '', ...]
     * @param array $comidas - Array con comidas por día
     *   ['lunes' => 'Comida', 'martes' => '', ...]
     * @return array - ['success' => bool, 'message' => string, ...]
     */
    public function agendarPedidos($fechaSemana, $desayunos, $comidas) {
        $payload = [
            'fecha_semana' => $fechaSemana,
            'desayunos' => $desayunos,
            'comidas' => $comidas
        ];
        
        return $this->post('/api/pedidos/agendar-pedidos', $payload);
    }
    
    // ==========================================
    // GET - Hacer petición GET
    // ==========================================
    
    /**
     * Hacer una petición GET a la API
     * 
     * @param string $endpoint - Endpoint (ej: /api/usuarios/123)
     * @return array - Respuesta decodificada o ['success' => false, 'error' => string]
     */
    public function get($endpoint) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        return $this->execute($ch);
    }
    
    // ==========================================
    // POST - Hacer petición POST
    // ==========================================
    
    /**
     * Hacer una petición POST a la API
     * 
     * @param string $endpoint - Endpoint (ej: /api/pedidos/agendar-pedidos)
     * @param array $data - Datos a enviar
     * @return array - Respuesta decodificada o ['success' => false, 'error' => string]
     */
    public function post($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        return $this->execute($ch);
    }
    
    // ==========================================
    // PUT - Hacer petición PUT
    // ==========================================
    
    /**
     * Hacer una petición PUT a la API
     * 
     * @param string $endpoint - Endpoint
     * @param array $data - Datos a enviar
     * @return array - Respuesta decodificada
     */
    public function put($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        return $this->execute($ch);
    }
    
    // ==========================================
    // DELETE - Hacer petición DELETE
    // ==========================================
    
    /**
     * Hacer una petición DELETE a la API
     * 
     * @param string $endpoint - Endpoint
     * @return array - Respuesta decodificada
     */
    public function delete($endpoint) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        
        return $this->execute($ch);
    }
    
    // ==========================================
    // EJECUTAR PETICIÓN
    // ==========================================
    
    /**
     * Ejecutar la petición curl y procesar respuesta
     * 
     * @param resource $ch - Resource de curl
     * @return array - Respuesta procesada
     */
    private function execute($ch) {
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Guardar respuesta para debug
        $this->lastResponse = $response;
        
        // Manejar errores de conexión
        if ($curlError) {
            $this->lastError = $curlError;
            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $curlError,
                'http_code' => 0
            ];
        }
        
        // Verificar código HTTP
        if ($httpCode < 200 || $httpCode >= 300) {
            $this->lastError = 'HTTP ' . $httpCode;
            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $httpCode,
                'http_code' => $httpCode
            ];
        }
        
        // Decodificar JSON
        $data = json_decode($response, true);
        
        if ($data === null) {
            $this->lastError = 'Respuesta inválida: ' . json_last_error_msg();
            return [
                'success' => false,
                'error' => 'Respuesta inválida: ' . json_last_error_msg(),
                'http_code' => $httpCode
            ];
        }
        
        return $data;
    }
    
    // ==========================================
    // OBTENER ÚLTIMA RESPUESTA
    // ==========================================
    
    /**
     * Obtener la última respuesta raw (para debug)
     * 
     * @return string|null
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }
    
    // ==========================================
    // OBTENER ÚLTIMO ERROR
    // ==========================================
    
    /**
     * Obtener el último error
     * 
     * @return string|null
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    // ==========================================
    // VERIFICAR DISPONIBILIDAD
    // ==========================================
    
    /**
     * Verifica si la API está disponible
     * 
     * @return bool
     */
    public function isAvailable() {
        $response = $this->get('/health');
        return isset($response['success']) && $response['success'];
    }
}

// ==========================================
// EJEMPLO DE USO
// ==========================================

/*
// Crear cliente (detecta entorno automáticamente)
$api = new ApiClient();

// O especificar URL manualmente
// $api = new ApiClient('http://localhost:3000');

// Agendar pedidos
$resultado = $api->agendarPedidos(
    '2026-01-26',
    [
        'lunes' => 'Desayuno',
        'martes' => 'Desayuno',
        'miercoles' => 'Desayuno',
        'jueves' => '',
        'viernes' => 'Desayuno'
    ],
    [
        'lunes' => 'Comida',
        'martes' => 'Comida',
        'miercoles' => '',
        'jueves' => 'Comida',
        'viernes' => ''
    ]
);

if ($resultado['success']) {
    echo "✓ " . $resultado['message'];
} else {
    echo "✗ " . $resultado['error'];
    echo "\nDebug: " . $api->getLastError();
}
*/

?>
