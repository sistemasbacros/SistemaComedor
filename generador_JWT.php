<?php
// ==================================================
// GENERADOR DE JWT EN PHP
// Archivo de referencia para el sistema de autenticación previo
// ==================================================

/**
 * Genera un JSON Web Token (JWT)
 * 
 * @param array $payload Datos a incluir en el token
 * @param string $secret Clave secreta (debe estar en .env en producción)
 * @param int $expiresIn Tiempo de expiración en segundos (default: 1 hora)
 * @return string Token JWT
 */
function generarJWT($payload, $secret = 'tu_clave_secreta_super_segura', $expiresIn = 3600) {
    // Header
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    // Payload con timestamps
    $now = time();
    $payload['iat'] = $now;           // Emitido en
    $payload['exp'] = $now + $expiresIn; // Expira en
    
    // Codificar partes
    $headerEncoded = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));
    
    // Crear firma
    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $signatureEncoded = base64url_encode($signature);
    
    // Retornar token completo
    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Codifica en base64 con formato URL-safe
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Decodifica desde base64 URL-safe
 */
function base64url_decode($data) {
    $padding = 4 - strlen($data) % 4;
    if ($padding !== 4) {
        $data .= str_repeat('=', $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Verifica la validez de un JWT
 * 
 * @param string $token Token JWT a verificar
 * @param string $secret Clave secreta
 * @return array|false Array con payload si válido, false si no
 */
function verificarJWT($token, $secret = 'tu_clave_secreta_super_segura') {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
    
    // Verificar firma
    $signatureExpected = base64url_encode(
        hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true)
    );
    
    if ($signatureEncoded !== $signatureExpected) {
        return false;
    }
    
    // Decodificar payload
    $payload = json_decode(base64url_decode($payloadEncoded), true);
    
    // Verificar expiración
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

// ==================================================
// EJEMPLO DE USO EN SISTEMA DE AUTENTICACIÓN
// ==================================================

// Simular validación de usuario (reemplazar con consulta real a BD)
$usuario = 'jrodriguez';
$contrasena = 'password123';

// Validar credenciales contra BD (PSEUDOCÓDIGO)
// $usuario_bd = buscar_usuario_en_bd($usuario);
// if (password_verify($contrasena, $usuario_bd['hash'])) {

if (true) { // Simulación de autenticación exitosa
    
    // Datos del usuario autenticado
    $empleado = [
        'id' => '12345',
        'id_empleado' => 'E001',
        'nombre' => 'Josiana Rodriguez',
        'usuario' => 'jrodriguez',
        'email' => 'josiana@empresa.com',
        'area' => 'Cocina',
        'rol' => 'empleado'
    ];
    
    // Generar JWT con los datos del usuario
    $jwt_token = generarJWT($empleado);
    
    // Guardar en cookie (segura)
    setcookie('token', $jwt_token, [
        'expires' => time() + 3600,      // 1 hora
        'path' => '/Comedor/',
        'domain' => '',
        'secure' => false,                // true en HTTPS (producción)
        'httponly' => true,               // Protegido contra XSS
        'samesite' => 'Strict'            // Protegido contra CSRF
    ]);
    
    // O guardar en sesión
    $_SESSION['token'] = $jwt_token;
    
    // O retornar en respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Autenticación exitosa',
        'token' => $jwt_token,
        'user' => $empleado
    ]);
    
} else {
    // Autenticación fallida
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Credenciales inválidas'
    ]);
}

// ==================================================
// VERIFICACIÓN EN MENPEDIDOS1.PHP O CUALQUIER PÁGINA
// ==================================================

/*
// Obtener token desde cookie, header o sesión
$token = $_COOKIE['token'] ?? $_SESSION['token'] ?? null;

// O desde header Authorization
if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+([^\s]+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = $matches[1];
    }
}

// Verificar token
$secret = getenv('JWT_SECRET') ?: 'tu_clave_secreta_super_segura';
$payload = verificarJWT($token, $secret);

if ($payload) {
    // Token válido
    $_SESSION['user'] = $payload;
    $id_empleado = $payload['id_empleado'];
    $nombre = $payload['nombre'];
    // ...
} else {
    // Token inválido o expirado
    header('Location: /login.php');
    exit;
}
*/

// ==================================================
// PRUEBAS - Descomentar para ejecutar
// ==================================================

/*
// Generar token de prueba
$token = generarJWT([
    'id' => '12345',
    'nombre' => 'Josiana',
    'usuario' => 'jrodriguez'
], 'clave_secreta_test');

echo "Token generado:\n$token\n\n";

// Verificar token
$verificado = verificarJWT($token, 'clave_secreta_test');
echo "Token verificado:\n";
var_dump($verificado);

// Intentar verificar con clave incorrecta
echo "\nIntentando verificar con clave incorrecta...\n";
$invalido = verificarJWT($token, 'clave_incorrecta');
var_dump($invalido);
*/

?>
