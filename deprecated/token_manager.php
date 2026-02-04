<?php
/**
 * SISTEMA UNIFICADO DE GESTIÓN DE TOKENS
 * =====================================
 * 
 * Este archivo centraliza toda la gestión de tokens para evitar inconsistencias.
 * USAR SOLO EL TOKEN DE LA API EXTERNA.
 */

require_once __DIR__ . '/config_api.php';

/**
 * Obtener el token válido de la sesión
 * PRIORIDAD: api_token (token real de la API externa)
 */
function getValidToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // SOLO usar el token de la API externa
    return $_SESSION['api_token'] ?? null;
}

/**
 * Verificar si hay un token válido y no expirado
 */
function hasValidToken() {
    $token = getValidToken();
    
    if (!$token) {
        return false;
    }
    
    // Verificar expiración si existe info de expiración
    $created_at = $_SESSION['token_created_at'] ?? 0;
    $expires_in = $_SESSION['token_expires_in'] ?? 86400; // 24 horas por defecto
    
    if ($created_at > 0 && (time() - $created_at) > $expires_in) {
        // Token expirado - limpiar
        clearTokens();
        return false;
    }
    
    return true;
}

/**
 * Guardar token de la API externa en la sesión
 */
function saveAPIToken($token, $expires_in = 86400, $token_type = 'Bearer') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ÚNICA FUENTE DE VERDAD: token de la API externa
    $_SESSION['api_token'] = $token;
    $_SESSION['token_type'] = $token_type;
    $_SESSION['token_expires_in'] = $expires_in;
    $_SESSION['token_created_at'] = time();
    
    // Para compatibilidad con código legacy que busca jwt_token
    $_SESSION['jwt_token'] = $token;
    
    // Debug log
    apiDebugLog('Token API guardado correctamente', [
        'token_preview' => substr($token, 0, 30) . '...',
        'expires_in' => $expires_in,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Limpiar todos los tokens de la sesión
 */
function clearTokens() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    unset($_SESSION['api_token']);
    unset($_SESSION['jwt_token']);
    unset($_SESSION['token_type']);
    unset($_SESSION['token_expires_in']);
    unset($_SESSION['token_created_at']);
    
    apiDebugLog('Tokens limpiados de la sesión');
}

/**
 * Verificar autenticación completa del usuario
 * Esta función reemplaza las verificaciones manuales en cada archivo
 */
function isUserAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 1. Verificar datos básicos de sesión (más flexible)
    $hasUserData = !empty($_SESSION['user_name']) || !empty($_SESSION['nombre']);
    
    // 2. Verificar token válido
    $hasValidToken = hasValidToken();
    
    // 3. Verificar sesión no expirada (opcional - puedes ajustar el tiempo)
    $sessionValid = true;
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        $sessionTimeout = 8 * 3600; // 8 horas
        if (time() - $_SESSION['LAST_ACTIVITY'] > $sessionTimeout) {
            $sessionValid = false;
        }
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    
    $isAuthenticated = $hasUserData && $hasValidToken && $sessionValid;
    
    apiDebugLog('Verificación de autenticación', [
        'has_user_data' => $hasUserData,
        'has_valid_token' => $hasValidToken,
        'session_valid' => $sessionValid,
        'result' => $isAuthenticated,
        'user_name' => $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? 'NO DEFINIDO'
    ]);
    
    return $isAuthenticated;
}

/**
 * Redirigir al login si no está autenticado
 * Esta función reemplaza los header() manuales en cada archivo
 */
function requireAuthentication($redirectUrl = null) {
    if (!isUserAuthenticated()) {
        clearTokens();
        session_destroy();
        
        $loginUrl = $redirectUrl ?? getAppUrl('Admiin.php');
        header("Location: $loginUrl");
        exit;
    }
}

/**
 * Obtener información del usuario autenticado
 */
function getCurrentUser() {
    if (!isUserAuthenticated()) {
        return null;
    }
    
    return [
        'id_empleado' => $_SESSION['user_id'] ?? $_SESSION['id_empleado'] ?? null,
        'nombre' => $_SESSION['user_name'] ?? $_SESSION['nombre'] ?? '',
        'area' => $_SESSION['user_area'] ?? $_SESSION['area'] ?? '',
        'usuario' => $_SESSION['user_usuario'] ?? $_SESSION['usuario'] ?? ''
    ];
}

/**
 * Debug: Mostrar información completa de la sesión
 */
function getSessionDebugInfo() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = getValidToken();
    
    return [
        'session_id' => session_id(),
        'has_api_token' => !empty($_SESSION['api_token']),
        'has_jwt_token' => !empty($_SESSION['jwt_token']),
        'token_preview' => $token ? substr($token, 0, 30) . '...' : 'NO TOKEN',
        'token_created_at' => $_SESSION['token_created_at'] ?? 'NO DEFINIDO',
        'token_expires_in' => $_SESSION['token_expires_in'] ?? 'NO DEFINIDO',
        'user_authenticated' => $_SESSION['authenticated_from_login'] ?? false,
        'user_name' => $_SESSION['user_name'] ?? 'NO DEFINIDO',
        'user_area' => $_SESSION['user_area'] ?? 'NO DEFINIDO',
        'last_activity' => isset($_SESSION['LAST_ACTIVITY']) ? date('Y-m-d H:i:s', $_SESSION['LAST_ACTIVITY']) : 'NO DEFINIDO'
    ];
}