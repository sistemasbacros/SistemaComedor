<?php
// ==================================================
// FUNCIÓN PARA GENERAR JWT EN ADMIIN.PHP
// Copiar y pegar esta función en Admiin.php
// ==================================================

/**
 * Genera un JSON Web Token (JWT) para usar en Menpedidos1.php
 * 
 * @param array $payload Datos del usuario a incluir
 * @return string Token JWT
 */
function generarJWTDesdeAdmiin($payload) {
    // Configuración del JWT
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    // Agregar timestamps
    $now = time();
    $payload['iat'] = $now;
    $payload['exp'] = $now + 86400; // 24 horas
    
    // Base64-encode header y payload
    $headerEncoded = base64_encode(json_encode($header));
    $payloadEncoded = base64_encode(json_encode($payload));
    
    // Limpiar caracteres de relleno
    $headerEncoded = rtrim(strtr($headerEncoded, '+/', '-_'), '=');
    $payloadEncoded = rtrim(strtr($payloadEncoded, '+/', '-_'), '=');
    
    // Crear firma (usar clave simple para desarrollo)
    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", 'comedor_jwt_secret_key_2026', true);
    $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // Retornar token completo
    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

// ==================================================
// CÓDIGO A AGREGAR EN ADMIIN.PHP DESPUÉS DE LOGIN EXITOSO
// ==================================================

// Alrededor de la línea 130-135, después de guardar la sesión, agregar:

/*

// ========== GENERAR JWT PARA MENPEDIDOS1.PHP ==========
$jwtPayload = [
    'id' => $row['Id_Empleado'],
    'id_empleado' => $row['Id_Empleado'],
    'empleado_id' => $row['Id_Empleado'],
    'nombre' => $row['Nombre'],
    'name' => $row['Nombre'],
    'usuario' => $row['Usuario'],
    'username' => $row['Usuario'],
    'email' => 'user@comedor.com', // O desde BD si lo tienes
    'area' => $row['Area'],
    'departamento' => $row['Area'],
    'rol' => 'empleado'
];

$jwtToken = generarJWTDesdeAdmiin($jwtPayload);

// Guardar JWT en sesión Y cookie
$_SESSION['token'] = $jwtToken;
$_SESSION['jwt_token'] = $jwtToken;

setcookie('token', $jwtToken, [
    'expires' => time() + 86400,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

error_log("========== JWT GENERADO ==========");
error_log("Token: " . substr($jwtToken, 0, 50) . "...");
error_log("===================================");

*/
?>
