<?php
/**
 * Auth
 *
 * POST /auth/login
 * GET  /auth/validate
 * POST /auth/refresh
 * GET  /auth/profile
 *
 * Frontend: Admiin.php → login
 */
class AuthApi {

    private $http;

    public function __construct() {
        $this->http = new HttpClient();
    }

    /**
     * POST /auth/login
     * Autentica con usuario + contraseña. Guarda el JWT en sesión.
     * Frontend: Admiin.php
     */
    public function login($usuario, $contrasena) {
        $url = ApiConfig::getInstance()->getApiUrl() . '/auth/login';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['usuario' => $usuario, 'contrasena' => $contrasena]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'Error de conexión: ' . $curlError, 'http_code' => 0];
        }

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($data['token'])) {
            TokenManager::save(
                $data['token'],
                $data['expires_in'] ?? 86400,
                $data['token_type'] ?? 'Bearer'
            );
            return ['success' => true, 'data' => $data, 'http_code' => $httpCode];
        }

        return [
            'success'   => false,
            'error'     => $data['error'] ?? $data['message'] ?? 'Error de autenticación (HTTP ' . $httpCode . ')',
            'http_code' => $httpCode,
            'response'  => $response,
        ];
    }

    /**
     * GET /auth/validate
     * Verifica si el token sigue siendo válido.
     * Llamado automáticamente; no requiere uso directo en el frontend.
     */
    public function validarToken() {
        return $this->http->get('auth/validate');
    }

    /**
     * POST /auth/refresh
     * Re-emite un JWT con expiración renovada (24h) sin requerir contraseña.
     * Llamado automáticamente; no requiere uso directo en el frontend.
     */
    public function refrescarToken() {
        $result = $this->http->post('auth/refresh');
        if ($result['success'] && isset($result['data']['token'])) {
            TokenManager::save($result['data']['token']);
        }
        return $result;
    }

    /**
     * GET /auth/profile
     * Perfil completo del usuario autenticado (desde BD, no solo del token).
     */
    public function perfil() {
        return $this->http->get('auth/profile');
    }

    /**
     * Cierra la sesión local. No hay endpoint de logout en el backend.
     */
    public function logout() {
        TokenManager::clear();
        if (session_status() !== PHP_SESSION_NONE) session_destroy();
    }
}
