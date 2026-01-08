<?php
/**
 * TEST RÁPIDO: Verificar conexión con la API
 * Acceder a: http://desarollo-bacros/Comedor/test_api.php
 */

// Cargar configuración global
require_once __DIR__ . '/config_api.php';

header('Content-Type: application/json; charset=UTF-8');

// Usar configuración global para la URL
$apiUrl = getApiUrl('LOGIN');
$usuario = 'adrian.ibarra';
$contrasena = 'Adriiba1029';

echo "=== TEST DE CONEXIÓN CON API ===\n\n";

// Mostrar información del entorno
$envInfo = getApiEnvironmentInfo();
echo "📍 ENTORNO DETECTADO: " . strtoupper($envInfo['entorno']) . "\n";
echo "🌐 URL API: " . $envInfo['api_base_url'] . "\n";
echo "🌐 URL APP: " . $envInfo['app_base_url'] . "\n";
echo "⏱️  TIMEOUT: " . $envInfo['timeout'] . "s\n";
echo "🔧 DEBUG: " . ($envInfo['debug'] ? 'Activado' : 'Desactivado') . "\n\n";

// 1. Verificar si cURL está disponible
echo "1. Verificando cURL... ";
if (!function_exists('curl_init')) {
    echo "❌ ERROR: cURL no está instalado\n";
    exit;
}
echo "✅ OK\n\n";

// 2. Preparar petición
echo "2. Preparando petición a: $apiUrl\n";
$postData = json_encode([
    'usuario' => $usuario,
    'contrasena' => $contrasena
]);
echo "   Datos: " . $postData . "\n\n";

// 3. Hacer petición
echo "3. Enviando petición...\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlInfo = curl_getinfo($ch);
curl_close($ch);

$tiempo = round(($endTime - $startTime) * 1000, 2);

// 4. Mostrar resultados
echo "   Tiempo de respuesta: {$tiempo}ms\n\n";

if ($curlError) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "   " . $curlError . "\n\n";
    echo "POSIBLES CAUSAS:\n";
    echo "• La API no está corriendo en http://localhost:3000\n";
    echo "• Firewall bloqueando la conexión\n";
    echo "• Puerto 3000 ocupado por otra aplicación\n";
    exit;
}

echo "4. Respuesta de la API:\n";
echo "   Código HTTP: $httpCode\n";

if ($httpCode === 200) {
    echo "   Estado: ✅ ÉXITO\n\n";
    
    $apiResponse = json_decode($response, true);
    
    if (isset($apiResponse['token'])) {
        echo "5. Datos recibidos:\n";
        echo "   Token: " . substr($apiResponse['token'], 0, 50) . "...\n";
        echo "   Tipo: " . ($apiResponse['token_type'] ?? 'Bearer') . "\n";
        echo "   Expira en: " . ($apiResponse['expires_in'] ?? 0) . " segundos\n\n";
        
        if (isset($apiResponse['user_info'])) {
            echo "6. Información del usuario:\n";
            echo "   ID: " . ($apiResponse['user_info']['id_empleado'] ?? 'N/A') . "\n";
            echo "   Nombre: " . ($apiResponse['user_info']['nombre'] ?? 'N/A') . "\n";
            echo "   Área: " . ($apiResponse['user_info']['area'] ?? 'N/A') . "\n";
            echo "   Usuario: " . ($apiResponse['user_info']['usuario'] ?? 'N/A') . "\n\n";
        }
        
        echo "✅ INTEGRACIÓN FUNCIONANDO CORRECTAMENTE\n";
        echo "===========================================\n";
        echo "Puedes proceder a usar Admiin.php para login\n";
        
    } else {
        echo "⚠️ ADVERTENCIA: Respuesta sin token\n";
        echo "Respuesta completa:\n";
        print_r($apiResponse);
    }
    
} elseif ($httpCode === 401) {
    echo "   Estado: ❌ NO AUTORIZADO\n\n";
    
    $apiResponse = json_decode($response, true);
    echo "Error: " . ($apiResponse['error'] ?? $apiResponse['message'] ?? 'Credenciales incorrectas') . "\n";
    
} else {
    echo "   Estado: ❌ ERROR (Código $httpCode)\n\n";
    echo "Respuesta:\n";
    echo $response . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>
