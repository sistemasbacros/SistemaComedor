<?php
/**
 * Gestión del JWT en sesión PHP.
 * Guarda, valida, refresca y limpia el token de autenticación.
 */
class TokenManager {

    private static function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public static function getToken() {
        self::ensureSession();
        return $_SESSION['api_token'] ?? null;
    }

    public static function hasValidToken() {
        $token = self::getToken();
        if (!$token) return false;

        $createdAt = $_SESSION['token_created_at'] ?? 0;
        $expiresIn = $_SESSION['token_expires_in']  ?? 86400;

        if ($createdAt > 0 && (time() - $createdAt) > $expiresIn) {
            self::clear();
            return false;
        }
        return true;
    }

    public static function save($token, $expiresIn = 86400, $tokenType = 'Bearer') {
        self::ensureSession();
        $_SESSION['api_token']        = $token;
        $_SESSION['jwt_token']        = $token; // compatibilidad legacy
        $_SESSION['token_type']       = $tokenType;
        $_SESSION['token_expires_in'] = $expiresIn;
        $_SESSION['token_created_at'] = time();
    }

    public static function clear() {
        self::ensureSession();
        unset(
            $_SESSION['api_token'],
            $_SESSION['jwt_token'],
            $_SESSION['token_type'],
            $_SESSION['token_expires_in'],
            $_SESSION['token_created_at']
        );
    }

    public static function isAuthenticated() {
        self::ensureSession();
        $hasUserData = !empty($_SESSION['user_name']) || !empty($_SESSION['nombre']);
        return $hasUserData && self::hasValidToken();
    }

    public static function requireAuth($loginUrl = 'Admiin.php') {
        if (!self::isAuthenticated()) {
            self::clear();
            header('Location: ' . ApiConfig::getInstance()->getAppUrl($loginUrl));
            exit;
        }
    }

    public static function getCurrentUser() {
        if (!self::isAuthenticated()) return null;
        self::ensureSession();
        return [
            'id_empleado' => $_SESSION['user_id']      ?? $_SESSION['id_empleado'] ?? null,
            'nombre'      => $_SESSION['user_name']    ?? $_SESSION['nombre']      ?? '',
            'area'        => $_SESSION['user_area']    ?? $_SESSION['area']        ?? '',
            'usuario'     => $_SESSION['user_usuario'] ?? $_SESSION['usuario']     ?? '',
        ];
    }
}
